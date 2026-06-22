<?php

declare(strict_types=1);

namespace App\Modules\Review\Api;

use App\Components\Api\ApiClient;
use App\Components\Api\ApiPayload;
use App\Components\Api\ApiResponse;
use App\Modules\Review\Api\Response\ReviewResponse;

final readonly class ReviewApi
{
    public function __construct(
        private ApiClient $apiClient,
    ) {}

    /**
     * @return list<ReviewResponse>
     */
    public function getReviews(int $page = 1, int $limit = 10, ?int $productId = null, ?int $userId = null, ?int $rating = null): array
    {
        $query = [
            'page'  => $page,
            'limit' => $limit,
        ];

        if ($productId !== null) {
            $query['productId'] = $productId;
        }

        if ($userId !== null) {
            $query['userId'] = $userId;
        }

        if ($rating !== null) {
            $query['rating'] = $rating;
        }

        $payload = $this->apiClient->get('/reviews', $query);

        /** @var list<array{
         *     id?: int,
         *     productId?: int,
         *     userId?: int,
         *     rating?: int,
         *     title?: string,
         *     content?: string,
         *     date?: string
         * }> $items */
        $items = ApiPayload::extractDataList($payload);

        return ApiResponse::fromArrayList($items, ReviewResponse::fromArray(...));
    }

    /**
     * @return list<ReviewResponse>
     */
    public function getProductReviews(int $productId, int $page = 1, int $limit = 10): array
    {
        $payload = $this->apiClient->get('/reviews', [
            'productId' => $productId,
            'page'      => $page,
            'limit'     => $limit,
        ]);

        /** @var list<array{
         *     id?: int,
         *     productId?: int,
         *     userId?: int,
         *     rating?: int,
         *     title?: string,
         *     content?: string,
         *     date?: string
         * }> $items */
        $items = ApiPayload::extractDataList($payload);

        return ApiResponse::fromArrayList($items, ReviewResponse::fromArray(...));
    }
}
