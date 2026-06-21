<?php

declare(strict_types=1);

namespace App\Components\Http\Response;

final class JsonDataResponse extends JsonResponse
{
    public function __construct(mixed $data, int $status = 200)
    {
        parent::__construct(['data' => $data], $status);
    }
}
