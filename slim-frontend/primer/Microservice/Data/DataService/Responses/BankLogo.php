<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class BankLogo
{
    public function __construct(
        public ?string $card,
        public ?string $original,
    ) {}

    /**
     * @param array{
     *      card?: string|null,
     *      original?: string|null
     * } $logo
     */
    public static function fromArray(array $logo): self
    {
        return new self(
            card: $logo['card'] ?? null,
            original: $logo['original'] ?? null,
        );
    }
}
