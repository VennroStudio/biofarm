<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class SpaceGroup
{
    public function __construct(
        public int $id,
        public string $name,
        public int $status,
    ) {}

    /**
     * @param array{
     *      id: int,
     *      name: string,
     *      status: int
     * } $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'],
            name: $item['name'] ?? '',
            status: $item['status'] ?? 0,
        );
    }
}
