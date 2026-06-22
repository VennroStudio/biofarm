<?php

declare(strict_types=1);

namespace App\Components\Api;

use RuntimeException;

final class ApiException extends RuntimeException
{
    public static function requestFailed(string $method, string $url, int $statusCode): self
    {
        return new self(\sprintf(
            'External API request failed: %s %s returned HTTP %d.',
            $method,
            $url,
            $statusCode,
        ));
    }
}
