<?php

declare(strict_types=1);

namespace App\Components\Http\Form;

use RuntimeException;

final class FormValidationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
    ) {
        parent::__construct($message);
    }

    public static function invalidCsrfToken(): self
    {
        return new self('Form token is invalid. Refresh the page and try again.', 419);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
