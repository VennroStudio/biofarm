<?php

declare(strict_types=1);

namespace App\Components\Integration\Bitrix24;

use RuntimeException;

final class Bitrix24Exception extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $httpStatus = null,
        private readonly ?string $responseBody = null,
    ) {
        parent::__construct($message);
    }

    public function httpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function responseBody(): ?string
    {
        return $this->responseBody;
    }
}
