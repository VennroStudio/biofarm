<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class BankIcon
{
    public function __construct(
        public ?string $card,
        public ?string $circle,
        public ?string $original,
    ) {}

    /**
     * @param array{
     *      card?: string|null,
     *      circle?: string|null,
     *      original?: string|null
     * } $icon
     */
    public static function fromArray(array $icon): self
    {
        return new self(
            card: $icon['card'] ?? null,
            circle: $icon['circle'] ?? null,
            original: $icon['original'] ?? null,
        );
    }
}
