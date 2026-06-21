<?php

declare(strict_types=1);

namespace App\Components\Http\Response;

final class JsonDataSuccessResponse extends JsonResponse
{
    public function __construct(int $success = 1, int $status = 201)
    {
        parent::__construct([
            'data' => [
                'success' => $success,
            ],
        ], $status);
    }
}
