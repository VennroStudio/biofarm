<?php

declare(strict_types=1);

namespace App\Components\Http\Response;

final class JsonDataItemsResponse extends JsonResponse
{
    public function __construct(int $count, array $items, int $status = 200)
    {
        parent::__construct([
            'data' => [
                'count' => $count,
                'items' => $items,
            ],
        ], $status);
    }
}
