<?php

declare(strict_types=1);

namespace App\Modules\User\Service;

use Doctrine\DBAL\Connection;

final readonly class CheckoutAddressBook
{
    public function __construct(private Connection $db) {}

    /** The checkout transaction holds the user row lock while saving the address. */
    public function remember(int $userId, array $address, ?string $ozonMethod, ?int $destinationId): void
    {
        $base = 'FROM user_addresses WHERE user_id = ? AND deleted_at IS NULL';
        $city = mb_substr($address['city'], 0, 255);
        $postalCode = mb_substr($address['postal_code'], 0, 30);
        if ($ozonMethod === null) {
            $existing = $this->db->fetchOne(
                "SELECT id $base AND ozon_delivery_method IS NULL AND city = ? AND address = ? AND COALESCE(postal_code, '') = ? LIMIT 1",
                [$userId, $city, $address['address'], $postalCode],
            );
        } else {
            $existing = $this->db->fetchOne(
                "SELECT id $base AND ozon_delivery_method = ? AND ozon_destination_id = ? LIMIT 1",
                [$userId, $ozonMethod, $destinationId],
            );
        }
        if ($existing !== false) {
            return;
        }

        $default = $ozonMethod === null
            && $this->db->fetchOne("SELECT id $base AND is_default = 1 LIMIT 1", [$userId]) === false;
        $this->db->insert('user_addresses', [
            'user_id' => $userId,
            'label' => $ozonMethod === null ? 'Адрес' : 'Пункт выдачи Ozon',
            'name' => mb_substr($address['name'], 0, 255),
            'phone' => mb_substr($address['phone'], 0, 50),
            'email' => mb_substr($address['email'], 0, 255),
            'city' => $city,
            'address' => $address['address'],
            'postal_code' => $postalCode,
            'comment' => $address['comment'],
            'is_default' => (int)$default,
            'ozon_delivery_method' => $ozonMethod,
            'ozon_destination_id' => $destinationId,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }
}
