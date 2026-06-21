<?php

declare(strict_types=1);

namespace App\Components\Http\Response;

final class JsonErrorResponse extends JsonResponse
{
    public function __construct(int $code, string $message, ?array $payload = null, int $status = 409)
    {
        $body = [
            'error' => [
                'code'    => $code,
                'message' => $message,
            ],
        ];

        if ($payload !== null) {
            $body['payload'] = $payload;
        }

        parent::__construct($body, $status);
    }
}
