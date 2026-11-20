<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

use Doctrine\DBAL\Connection;

final readonly class OzonPickupDirectory
{
    public function __construct(private Connection $db, private OzonDelivery $delivery) {}

    public function search(string $query): array
    {
        $words = preg_split('/[\s,]+/u', trim($query), 6, PREG_SPLIT_NO_EMPTY);
        if (mb_strlen(trim($query)) < 3 || mb_strlen($query) > 120 || !$words) {
            return [];
        }
        $conditions = implode(' AND ', array_fill(0, count($words), 'LOCATE(?, address) > 0'));
        $rows = $this->db->fetchAllAssociative(
            "SELECT id, city, address FROM ozon_pickup_points WHERE $conditions
             ORDER BY (city = ?) DESC, city, address, id LIMIT 50",
            [...$words, trim($query)],
        );
        foreach ($rows as &$point) {
            $point['id'] = (int)$point['id'];
        }
        return $rows;
    }

    public function refresh(bool $onlyIfDue = false): int
    {
        $lock = 'ozon-pickup-' . sha1((string)$this->db->getDatabase());
        if ((int)$this->db->fetchOne('SELECT GET_LOCK(?, 0)', [$lock]) !== 1) {
            return 0;
        }
        try {
            if ($onlyIfDue && $this->db->fetchOne('SELECT 1 FROM ozon_catalog_state WHERE id=1 AND points_refreshed_at > UTC_TIMESTAMP() - INTERVAL 1 DAY')) {
                return 0;
            }
            $published = gmdate('Y-m-d H:i:s');
            $delivery = $this->delivery;
            $count = 0;
            $batch = [];
            foreach ($delivery->pickupPointIds() as $id) {
                $batch[$id] = $id;
                if (count($batch) === 100) {
                    $count += $this->save($delivery, array_values($batch), $published);
                    $batch = [];
                    usleep(200_000);
                }
            }
            if ($batch !== []) {
                $count += $this->save($delivery, array_values($batch), $published);
            }
            $previous = $this->db->fetchOne('SELECT COUNT(*) FROM ozon_pickup_points WHERE refreshed_at < ?', [$published]);
            if ($count === 0 || (int)$previous > $count) {
                throw new OzonException('Справочник Ozon выглядит неполным. Ранее загруженные пункты выдачи сохранены.');
            }
            $this->db->executeStatement('DELETE FROM ozon_pickup_points WHERE refreshed_at < ?', [$published]);
            $this->db->update('ozon_catalog_state', ['points_refreshed_at' => $published], ['id' => 1]);
            return $count;
        } finally {
            $this->db->fetchOne('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }

    private function save(OzonDelivery $delivery, array $ids, string $refreshed): int
    {
        $points = $delivery->pickupPointDetails($ids);
        if ($points === []) {
            return 0;
        }
        $values = [];
        foreach ($points as $id => $point) {
            array_push($values, $id, mb_substr($point['address'], 0, 500), mb_substr($point['address_details']['city'] ?? '', 0, 255), $refreshed);
        }
        $placeholders = implode(',', array_fill(0, count($points), '(?, ?, ?, ?)'));
        $this->db->executeStatement("INSERT INTO ozon_pickup_points (id, address, city, refreshed_at) VALUES $placeholders
            ON DUPLICATE KEY UPDATE address=VALUES(address), city=VALUES(city), refreshed_at=VALUES(refreshed_at)", $values);
        return count($points);
    }
}
