<?php

declare(strict_types=1);

namespace App\Modules\User\Api\Response;

use App\Components\Api\ApiPayload;

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
        $name = ApiPayload::optionalArray($item, 'name');
        $address = ApiPayload::optionalArray($item, 'address');

        return new self(
            id: ApiPayload::requireInt($item, 'id'),
            email: ApiPayload::requireString($item, 'email'),
            username: ApiPayload::requireString($item, 'username'),
            firstname: ApiPayload::requireString($name, 'firstname'),
            lastname: ApiPayload::requireString($name, 'lastname'),
            street: ApiPayload::requireString($address, 'street'),
            city: ApiPayload::requireString($address, 'city'),
            zipcode: ApiPayload::requireString($address, 'zipcode'),
            country: ApiPayload::requireString($address, 'country'),
            phone: ApiPayload::requireString($item, 'phone'),
            orders: ApiPayload::optionalIntList($item, 'orders'),
        );
    }
}
