<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class Space
{
    /**
     * @param City[] $cities
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $group,
        public ?SpaceMain $main,
        public array $cities,
    ) {}

    /**
     * @param array{
     *      id: int,
     *      name: string,
     *      group: int,
     *      main?: array{id: int, name: string}|null,
     *      cities?: list<array{id: int, name: string, area?: string|null, region?: string|null, latitude?: float|null, longitude?: float|null, timezone?: int|null, population?: int|null, foundationYear?: int|null}>|null
     * } $item
     */
    public static function fromArray(array $item): self
    {
        $main = isset($item['main']) ? SpaceMain::fromArray($item['main']) : null;

        $cities = [];
        if (isset($item['cities'])) {
            foreach ($item['cities'] as $c) {
                $cities[] = City::fromArray($c);
            }
        }

        return new self(
            id: $item['id'],
            name: $item['name'] ?? '',
            group: $item['group'],
            main: $main,
            cities: $cities,
        );
    }

    /**
     * @return string[]
     */
    public function getAllCityIds(): array
    {
        return array_map(static fn (City $c): string => (string)$c->id, $this->cities);
    }
}
