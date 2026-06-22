<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class City
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $area,
        public ?string $region,
        public ?float $latitude,
        public ?float $longitude,
        public ?int $timezone,
        public ?int $population,
        public ?int $foundationYear,
    ) {}

    /**
     * @param array{
     *      id: int,
     *      name: string,
     *      area?: string|null,
     *      region?: string|null,
     *      latitude?: float|null,
     *      longitude?: float|null,
     *      timezone?: int|null,
     *      population?: int|null,
     *      foundationYear?: int|null
     *} $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'] ?? 0,
            name: $item['name'] ?? '',
            area: $item['area'] ?? null,
            region: $item['region'] ?? null,
            latitude: $item['latitude'] ?? null,
            longitude: $item['longitude'] ?? null,
            timezone: $item['timezone'] ?? null,
            population: $item['population'] ?? null,
            foundationYear: $item['foundationYear'] ?? null,
        );
    }

    /**
     * @param array<int, array{
     *       id: int,
     *       name: string,
     *       area?: string|null,
     *       region?: string|null,
     *       latitude?: float|null,
     *       longitude?: float|null,
     *       timezone?: int|null,
     *       population?: int|null,
     *       foundationYear?: int|null
     *  }> $items
     * @return City[]
     */
    public static function fromArrayList(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = self::fromArray($item);
        }
        return $result;
    }
}
