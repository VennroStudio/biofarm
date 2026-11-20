<?php

declare(strict_types=1);

namespace App\Components\Http\Response;

use JsonException;

final class JsonValidationsResponse extends JsonResponse
{
    /**
     * @param array<int, array{field: string, message: string}> $validations
     * @throws JsonException
     */
    public function __construct(array $validations, int $status = 422)
    {
        parent::__construct(['validations' => $validations], $status);
    }
}
