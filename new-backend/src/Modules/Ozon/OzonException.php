<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

use RuntimeException;
use Throwable;

final class OzonException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
        private readonly ?string $customerMessage = null,
        public readonly bool $quoteUnavailable = false,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getCustomerMessage(): string
    {
        return $this->customerMessage ?? $this->getMessage();
    }
}
