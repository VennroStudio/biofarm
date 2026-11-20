<?php

declare(strict_types=1);

namespace App\Components\Exception;

use Exception;
use Throwable;

final class AccessDeniedException extends Exception
{
    public function __construct(string $message = 'Access denied', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
