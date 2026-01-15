<?php

declare(strict_types=1);

namespace App\Components\TidalGrab;

use Exception;
use Throwable;

class RateLimitException extends Exception
{
    public function __construct(string $message = 'RATE LIMIT EXCEEDED', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
