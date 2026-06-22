<?php

declare(strict_types=1);

namespace App\Modules\Review\Api\Response;

use App\Components\Api\ApiPayload;

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
            id: ApiPayload::requireInt($item, 'id'),
            productId: ApiPayload::requireInt($item, 'productId'),
            userId: ApiPayload::requireInt($item, 'userId'),
            rating: ApiPayload::requireInt($item, 'rating'),
            title: ApiPayload::requireString($item, 'title'),
            content: ApiPayload::requireString($item, 'content'),
            date: ApiPayload::requireString($item, 'date'),
        );
    }
}
