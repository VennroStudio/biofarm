<?php

declare(strict_types=1);

namespace App\Modules\Review\Api\Response;

final readonly class ReviewResponse
{
    public function __construct(
        public int $id,
        public int $productId,
        public int $userId,
        public int $rating,
        public string $title,
        public string $content,
        public string $date,
    ) {}

    /**
     * @param array{
     *     id?: int,
     *     productId?: int,
     *     userId?: int,
     *     rating?: int,
     *     title?: string,
     *     content?: string,
     *     date?: string
     * } $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'] ?? 0,
            productId: $item['productId'] ?? 0,
            userId: $item['userId'] ?? 0,
            rating: $item['rating'] ?? 0,
            title: $item['title'] ?? '',
            content: $item['content'] ?? '',
            date: $item['date'] ?? '',
        );
    }
}
