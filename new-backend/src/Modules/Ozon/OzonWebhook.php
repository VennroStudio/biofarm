<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

use Doctrine\DBAL\Connection;

final readonly class OzonWebhook
{
    public function __construct(
        private Connection $db,
        private string $secret,
        private string $siteUrl,
        private string $sellerId,
    ) {}

    public function url(): ?string
    {
        return $this->enabled() && $this->siteUrl !== ''
            ? rtrim($this->siteUrl, '/') . '/webhooks/ozon/' . $this->secret
            : null;
    }

    public function accepts(string $secret): bool
    {
        return $this->enabled() && hash_equals($this->secret, $secret);
    }

    private function enabled(): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{32,128}$/D', $this->secret) === 1;
    }

    public function record(array $event): void
    {
        $seller = $event['seller_id'] ?? null;
        if ($seller !== null && OzonProduct::positiveId($seller) !== $this->sellerId) {
            return;
        }

        $rows = [$event];
        foreach (['data', 'stocks', 'items', 'products', 'changes'] as $key) {
            $nested = $event[$key] ?? null;
            if (is_array($nested)) {
                array_push($rows, ...(array_is_list($nested) ? $nested : [$nested]));
            }
        }
        $selectors = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = OzonProduct::positiveId($row['product_id'] ?? null);
            $sku = OzonProduct::positiveId($row['sku'] ?? null);
            if ($id !== null) {
                $selectors['id:' . $id] = true;
            }
            if ($id === null && $sku !== null) {
                $selectors['sku:' . $sku] = true;
            }
        }
        // A stock push without usable identifiers requests a single batched stock refresh.
        if ($selectors === [] || count($selectors) > 100) {
            $selectors = ['all' => true];
        }
        $this->db->transactional(function () use ($selectors): void {
            foreach (array_keys($selectors) as $selector) {
                $this->db->executeStatement(
                    'INSERT INTO ozon_stock_queue (selector, ready_at) VALUES (?, UTC_TIMESTAMP())
                     ON DUPLICATE KEY UPDATE revision = revision + 1, attempts = 0, ready_at = UTC_TIMESTAMP()',
                    [$selector],
                );
            }
        });
    }
}
