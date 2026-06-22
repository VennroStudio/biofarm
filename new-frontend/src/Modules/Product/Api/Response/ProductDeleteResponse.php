<?php

declare(strict_types=1);

namespace App\Modules\Product\Api\Response;

use App\Components\Api\ApiPayload;

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
            id: ApiPayload::requireInt($item, 'id'),
            deleted: ApiPayload::optionalBool($item, 'deleted', true),
            message: ApiPayload::optionalString($item, 'message', 'Product deleted'),
        );
    }
}
