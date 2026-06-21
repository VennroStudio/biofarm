<?php

declare(strict_types=1);

namespace App\Components\Exception;

use RuntimeException;
use Throwable;

final class AuthenticationException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid or expired token.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
