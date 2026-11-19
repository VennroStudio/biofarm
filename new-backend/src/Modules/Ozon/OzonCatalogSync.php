<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

use App\Components\Cacher\Cacher;
use App\Components\Integration\IntegrationErrorLogger;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Throwable;

final readonly class OzonCatalogSync
{
    public function __construct(
        private Connection $db,
        private OzonConnection $connection,
        private OzonCatalogSource $source,
        private OzonCatalogWriter $writer,
        private Cacher $cache,
        private IntegrationErrorLogger $errors,
    ) {}

    public function status(): array
    {
        $state = $this->state();
        return [
            'first' => $state['last_success_at'] === null,
            'pending' => (bool) $state['requested'] || $state['started_at'] !== null,
            'lastSuccessAt' => $state['last_success_at'] === null ? null : str_replace(' ', 'T', $state['last_success_at']) . 'Z',
            'result' => $state['result'] === null ? null : json_decode($state['result'], true, flags: JSON_THROW_ON_ERROR),
            'error' => $state['error'],
        ];
    }

    public function request(): void
    {
        if (!$this->connection->connected()) {
            throw new OzonException('Сначала подключите Ozon.');
        }
        $this->db->executeStatement(
            'UPDATE ozon_catalog_state SET requested = 1, error = NULL WHERE id = 1 AND started_at IS NULL',
        );
    }

    /** Run pending work. $clock is the date and time in the configured schedule timezone. */
    public function tick(DateTimeImmutable $clock): bool
    {
        $lock = 'ozon-catalog-' . sha1((string) $this->db->getDatabase());
        if ((int) $this->db->fetchOne('SELECT GET_LOCK(?, 0)', [$lock]) !== 1) {
            return true;
        }

        try {
            $state = $this->state();
            $now = $clock->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $today = $clock->format('Y-m-d');
            $daily = $state['last_success_at'] !== null && $clock->format('H:i') >= '03:00';
            $due = $daily && $state['daily_date'] !== $today
                && ($state['retry_after'] === null || $state['retry_after'] <= $now);

            $healthy = true;
            if ($this->connection->connected()
                && ((bool) $state['requested'] || $state['started_at'] !== null || $due)) {
                $healthy = $this->sync($state['last_success_at'] === null, $daily ? $today : null, $now);
            }

            if ($this->connection->connected() && $this->state()['last_success_at'] !== null) {
                $healthy = $this->stock($now) && $healthy;
            }
            $this->invalidateCache();
            return $healthy;
        } finally {
            $this->db->fetchOne('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }

    private function sync(bool $first, ?string $dailyDate, string $now): bool
    {
        $this->db->update('ozon_catalog_state', ['started_at' => $now], ['id' => 1]);
        try {
            $previous = $this->db->fetchFirstColumn('SELECT ozon_id FROM ozon_catalog_products WHERE archived = 0');
            $products = $this->source->catalog($this->connection->token(), $previous);
            $this->db->transactional(function () use ($products, $first, $dailyDate, $now): void {
                $result = $this->writer->apply($products, $first, $now);
                $this->db->update('ozon_catalog_state', [
                    'requested' => 0,
                    'started_at' => null,
                    'last_success_at' => $now,
                    'daily_date' => $dailyDate,
                    'retry_after' => null,
                    'cache_pending' => 1,
                    'result' => json_encode($result, JSON_THROW_ON_ERROR),
                    'error' => null,
                ], ['id' => 1]);
            });
            return true;
        } catch (Throwable $exception) {
            $message = $exception instanceof OzonException
                ? $exception->getMessage()
                : 'Не удалось сохранить каталог Ozon. Проверьте журнал приложения.';
            $this->db->update('ozon_catalog_state', [
                'requested' => 0,
                'started_at' => null,
                'retry_after' => gmdate('Y-m-d H:i:s', time() + 900),
                'error' => $message,
            ], ['id' => 1]);
            $this->logFailure('catalog_sync', $message, $exception);
            return false;
        }
    }

    private function stock(string $now): bool
    {
        $events = $this->db->fetchAllAssociative(
            'SELECT selector, revision, attempts FROM ozon_stock_queue WHERE ready_at <= ? ORDER BY ready_at, selector LIMIT 100',
            [$now],
        );
        if ($events === []) {
            return true;
        }

        try {
            $ids = $this->stockIds(array_column($events, 'selector'));
            $details = $ids === [] ? [] : $this->source->details($this->connection->token(), $ids);
            $this->db->transactional(function () use ($events, $details, $now): void {
                foreach ($details as $id => $row) {
                    if ($this->writer->updateStock((string) $id, $row, $now)) {
                        $this->db->update('ozon_catalog_state', ['cache_pending' => 1], ['id' => 1]);
                    }
                }
                foreach ($events as $event) {
                    // Keep newer notifications that arrived while fetching the stock snapshot.
                    $this->db->delete('ozon_stock_queue', ['selector' => $event['selector'], 'revision' => $event['revision']]);
                }
            });
            return true;
        } catch (Throwable $exception) {
            foreach ($events as $event) {
                $attempts = (int) $event['attempts'];
                $this->db->update('ozon_stock_queue', [
                    'attempts' => $attempts + 1,
                    'ready_at' => gmdate('Y-m-d H:i:s', time() + min(3600, 60 * (2 ** min(6, $attempts)))),
                ], ['selector' => $event['selector'], 'revision' => $event['revision']]);
            }
            if (min(array_column($events, 'attempts')) === 0) {
                $message = $exception instanceof OzonException ? $exception->getMessage() : 'Не удалось обновить остатки Ozon.';
                $this->logFailure('stock_webhook', $message, $exception);
            }
            return false;
        }
    }

    private function stockIds(array $selectors): array
    {
        $links = $this->db->fetchAllAssociative('SELECT ozon_id, sku, fbo_sku FROM ozon_catalog_products WHERE archived = 0');
        if (in_array('all', $selectors, true)) {
            return array_column($links, 'ozon_id');
        }
        $requested = array_fill_keys($selectors, true);
        $unmatched = $requested;
        $matched = [];
        foreach ($links as $row) {
            $keys = ['id:' . $row['ozon_id'], 'sku:' . $row['sku'], 'sku:' . $row['fbo_sku']];
            if (array_intersect_key($requested, array_fill_keys($keys, true)) !== []) {
                $matched[] = $row['ozon_id'];
            }
            foreach ($keys as $key) {
                unset($unmatched[$key]);
            }
        }
        foreach (array_keys($unmatched) as $key) {
            if (str_starts_with($key, 'sku:')) {
                // Ozon may issue a new SKU before the next catalog sync.
                return array_column($links, 'ozon_id');
            }
        }
        return $matched;
    }

    private function invalidateCache(): void
    {
        if (!(bool) $this->state()['cache_pending']) {
            return;
        }
        foreach (['products', 'categories', 'attributes', 'attribute_values'] as $tag) {
            $this->cache->deleteTag($tag);
        }
        $this->db->update('ozon_catalog_state', ['cache_pending' => 0], ['id' => 1]);
    }

    private function state(): array
    {
        $state = $this->db->fetchAssociative('SELECT * FROM ozon_catalog_state WHERE id = 1');
        if ($state === false) {
            throw new OzonException('Не применена миграция синхронизации Ozon.');
        }
        return $state;
    }

    private function logFailure(string $operation, string $message, Throwable $exception): void
    {
        $this->errors->log('ozon', 'catalog', $operation, $message, context: ['exception' => $exception::class]);
    }
}
