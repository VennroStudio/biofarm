<?php

declare(strict_types=1);

namespace App\Modules\User\Api\Response;

final readonly class UserResponse
{
    /**
     * @param list<int> $orders
     */
    public function __construct(
        public int $id,
        public string $email,
        public string $username,
        public string $firstname,
        public string $lastname,
        public string $street,
        public string $city,
        public string $zipcode,
        public string $country,
        public string $phone,
        public array $orders,
    ) {}

    /**
     * @param array{
     *     id?: int,
     *     email?: string,
     *     username?: string,
     *     name?: array{firstname?: string, lastname?: string},
     *     address?: array{street?: string, city?: string, zipcode?: string, country?: string},
     *     phone?: string,
     *     orders?: list<int>
     * } $item
     */
    public static function fromArray(array $item): self
    {
        $name = $item['name'] ?? [];
        $address = $item['address'] ?? [];

        return new self(
            id: $item['id'] ?? 0,
            email: $item['email'] ?? '',
            username: $item['username'] ?? '',
            firstname: $name['firstname'] ?? '',
            lastname: $name['lastname'] ?? '',
            street: $address['street'] ?? '',
            city: $address['city'] ?? '',
            zipcode: $address['zipcode'] ?? '',
            country: $address['country'] ?? '',
            phone: $item['phone'] ?? '',
            orders: $item['orders'] ?? [],
        );
    }
}
