<?php

declare(strict_types=1);

namespace App\Modules\User\Api;

use App\Components\Api\ApiClient;
use App\Components\Api\ApiPayload;
use App\Components\Api\ApiResponse;
use App\Modules\User\Api\Response\UserResponse;

final readonly class UserApi
{
    public function __construct(
        private ApiClient $apiClient,
    ) {}

    /**
     * @return list<UserResponse>
     */
    public function getUsers(int $page = 1, int $limit = 10): array
    {
        $payload = $this->apiClient->get('/users', [
            'page'  => $page,
            'limit' => $limit,
        ]);

        /** @var list<array{
         *     id?: int,
         *     email?: string,
         *     username?: string,
         *     name?: array{firstname?: string, lastname?: string},
         *     address?: array{street?: string, city?: string, zipcode?: string, country?: string},
         *     phone?: string,
         *     orders?: list<int>
         * }> $items */
        $items = ApiPayload::extractDataList($payload);

        return ApiResponse::fromArrayList($items, UserResponse::fromArray(...));
    }

    public function getUser(int $id): UserResponse
    {
        /** @var array{
         *     id?: int,
         *     email?: string,
         *     username?: string,
         *     name?: array{firstname?: string, lastname?: string},
         *     address?: array{street?: string, city?: string, zipcode?: string, country?: string},
         *     phone?: string,
         *     orders?: list<int>
         * } $item */
        $item = $this->apiClient->get('/users/' . $id);

        return UserResponse::fromArray($item);
    }
}
