<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class LanguageIcons
{
    public function __construct(
        public ?string $circle,
    ) {}

    /**
     * @param array{circle?: string|null} $icons
     */
    public static function fromArray(array $icons): self
    {
        return new self(
            circle: $icons['circle'] ?? null,
        );
    }
}
