<?php

declare(strict_types=1);

namespace App\Components\Exception;

use DomainException;
use Throwable;

final class DomainExceptionModule extends DomainException
{
    public function __construct(
        private readonly string $module,
        string $message = '',
        int $code = 0,
        private readonly ?array $payload = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }
}
