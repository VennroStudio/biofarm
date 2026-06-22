<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class SpaceMain
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    /**
     * @param array{
     *      id: int,
     *      name: string
     * } $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'],
            name: $item['name'] ?? '',
        );
    }
}
