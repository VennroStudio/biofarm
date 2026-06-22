<?php

declare(strict_types=1);

namespace App\Components\Api;

use RuntimeException;
use Throwable;

final class ApiException extends RuntimeException
{
    public static function requestFailed(string $method, int $statusCode): self
    {
        return new self(\sprintf(
            'External API request failed: %s returned HTTP %d.',
            $method,
            $statusCode,
        ));
    }

    public static function transportFailed(string $method, Throwable $exception): self
    {
        return new self(\sprintf(
            'External API transport failed during %s request.',
            $method,
        ), 0, $exception);
    }

    public static function invalidResponse(Throwable $exception): self
    {
        return new self('External API returned an invalid response.', 0, $exception);
    }

    public static function invalidPayload(string $message): self
    {
        return new self('External API payload is invalid: ' . $message);
    }
}
