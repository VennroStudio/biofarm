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
        $first = $state['last_success_at'] === null;
        return [
            'first' => $first,
            'pending' => $first && ((bool) $state['requested'] || $state['started_at'] !== null),
            'lastSuccessAt' => $state['last_success_at'] === null ? null : str_replace(' ', 'T', $state['last_success_at']) . 'Z',
            'result' => $state['result'] === null ? null : json_decode($state['result'], true, flags: JSON_THROW_ON_ERROR),
            'error' => $first ? $state['error'] : $state['stock_error'],
            'stockReconciledAt' => $state['stock_reconciled_at'] ? str_replace(' ', 'T', $state['stock_reconciled_at']) . 'Z' : null,
            'queuedStocks' => (int)$this->db->fetchOne('SELECT COUNT(*) FROM ozon_stock_queue'),
        ];
    }

    public function stocks(): array
    {
        $rows = $this->db->fetchAllAssociative('SELECT p.name, l.ozon_id, l.stock, l.fbs_stock, l.fbs_warehouses, l.stock_checked_at
            FROM ozon_catalog_products l JOIN products p ON p.id=l.product_id WHERE l.archived=0 ORDER BY p.name, p.id');
        foreach ($rows as &$row) {
            $row['warehouses'] = json_decode($row['fbs_warehouses'] ?? '[]', true, flags: JSON_THROW_ON_ERROR);
            unset($row['fbs_warehouses']);
        }
        return $rows;
    }

    public function request(): void
    {
        if (!$this->connection->connected()) {
            throw new OzonException('Сначала подключите Ozon.');
        }
        $queued = $this->db->executeStatement(
            'UPDATE ozon_catalog_state SET requested = 1, error = NULL
             WHERE id = 1 AND last_success_at IS NULL AND started_at IS NULL',
        );
        if ($queued === 0 && $this->state()['last_success_at'] !== null) {
            throw new OzonException('Каталог уже импортирован. Остатки обновляются по уведомлениям Ozon.');
        }
    }

    /** Run the initial import and process queued stock notifications. */
    public function tick(DateTimeImmutable $clock): bool
    {
        $lock = 'ozon-catalog-' . sha1((string) $this->db->getDatabase());
        if ((int) $this->db->fetchOne('SELECT GET_LOCK(?, 0)', [$lock]) !== 1) {
            return true;
        }

        try {
            $state = $this->state();
            $now = $clock->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $connected = $this->connection->connected();
            $imported = $state['last_success_at'] !== null;

            $this->db->executeStatement('DELETE FROM ozon_stock_reservations WHERE expires_at <= UTC_TIMESTAMP()');
            $healthy = true;
            if ($connected && !$imported && ((bool) $state['requested'] || $state['started_at'] !== null)) {
                $healthy = $this->importCatalog($now);
                $imported = $healthy;
                $state = $this->state();
            }

            if ($connected && $imported) {
                if ($state['stock_reconciled_at'] === null || $state['stock_reconciled_at'] <= $clock->modify('-1 hour')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')) {
                    $this->db->executeStatement("INSERT IGNORE INTO ozon_stock_queue (selector, ready_at) VALUES ('all', ?)", [$now]);
                }
                $healthy = $this->stock($now);
            }
            $this->invalidateCache();
            return $healthy;
        } finally {
            $this->db->fetchOne('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }

    private function importCatalog(string $now): bool
    {
        $this->db->update('ozon_catalog_state', ['started_at' => $now], ['id' => 1]);
        try {
            $previous = $this->db->fetchFirstColumn('SELECT ozon_id FROM ozon_catalog_products WHERE archived = 0');
            $products = $this->source->catalog($this->connection->token(), $previous);
            $this->db->transactional(function () use ($products, $now): void {
                $result = $this->writer->apply($products, $now);
                $this->db->update('ozon_catalog_state', [
                    'requested' => 0,
                    'started_at' => null,
                    'last_success_at' => $now,
                    'stock_reconciled_at' => $now,
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
            $token = $this->connection->token();
            try {
                [$details, $warehouses] = $this->source->stockSnapshot($token, $ids);
            } catch (OzonException $exception) {
                if ($exception->getCode() !== 401) {
                    throw $exception;
                }
                [$details, $warehouses] = $this->source->stockSnapshot($this->connection->token($token), $ids);
            }
            $this->db->transactional(function () use ($events, $details, $now, $warehouses): void {
                foreach ($details as $id => $row) {
                    if ($this->writer->updateStock((string) $id, $row, $now, $warehouses[$id] ?? [])) {
                        $this->db->update('ozon_catalog_state', ['cache_pending' => 1], ['id' => 1]);
                    }
                }
                if (in_array('all', array_column($events, 'selector'), true)) {
                    $this->db->update('ozon_catalog_state', ['stock_reconciled_at' => $now, 'stock_error' => null], ['id' => 1]);
                }
                foreach ($events as $event) {
                    // Keep newer notifications that arrived while fetching the stock snapshot.
                    $this->db->delete('ozon_stock_queue', ['selector' => $event['selector'], 'revision' => $event['revision']]);
                }
                if (!$this->db->fetchOne('SELECT 1 FROM ozon_stock_queue WHERE attempts > 0 LIMIT 1')) {
                    $this->db->update('ozon_catalog_state', ['stock_error' => null], ['id' => 1]);
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
            $message = $exception instanceof OzonException ? $exception->getMessage() : 'Не удалось обновить остатки Ozon.';
            $this->db->update('ozon_catalog_state', ['stock_error' => mb_substr($message, 0, 500)], ['id' => 1]);
            if (min(array_column($events, 'attempts')) === 0) {
                $this->logFailure('stock_webhook', $message, $exception);
            }
            return false;
        }
    }

    private function stockIds(array $selectors): array
    {
        $links = $this->db->fetchAllAssociative('SELECT ozon_id, sku, fbo_sku, fbs_sku FROM ozon_catalog_products WHERE archived = 0');
        if (in_array('all', $selectors, true)) {
            return array_column($links, 'ozon_id');
        }
        $requested = array_fill_keys($selectors, true);
        $unmatched = $requested;
        $matched = [];
        foreach ($links as $row) {
            $keys = ['id:' . $row['ozon_id'], 'sku:' . $row['sku'], 'sku:' . $row['fbo_sku'], 'sku:' . $row['fbs_sku']];
            if (array_intersect_key($requested, array_fill_keys($keys, true)) !== []) {
                $matched[] = $row['ozon_id'];
            }
            foreach ($keys as $key) {
                unset($unmatched[$key]);
            }
        }
        foreach (array_keys($unmatched) as $key) {
            if (str_starts_with($key, 'sku:')) {
                // Notifications may use a SKU that was not present at import time.
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
