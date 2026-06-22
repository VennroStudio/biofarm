<?php

declare(strict_types=1);

namespace App\Modules\Product\Api\Response;

final readonly class ProductDeleteResponse
{
    public function __construct(
        public int $id,
        public bool $deleted,
        public string $message,
    ) {}

    /**
     * @param array{id?: int, deleted?: bool, message?: string} $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'] ?? 0,
            deleted: $item['deleted'] ?? true,
            message: $item['message'] ?? 'Product deleted',
        );
    }
}
