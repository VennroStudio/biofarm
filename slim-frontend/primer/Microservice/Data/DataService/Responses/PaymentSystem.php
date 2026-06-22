<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class PaymentSystem
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $logo,
        public ?string $site,
        public ?string $wiki,
    ) {}

    /**
     * @param array{
     *      id: int,
     *      name: string,
     *      logo?: string|null,
     *      site?: string|null,
     *      wiki?: string|null
     * } $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'] ?? 0,
            name: $item['name'] ?? '',
            logo: $item['logo'] ?? null,
            site: $item['site'] ?? null,
            wiki: $item['wiki'] ?? null,
        );
    }

    /**
     * @param array<int, array{
     *      id: int,
     *      name: string,
     *      logo?: string|null,
     *      site?: string|null,
     *      wiki?: string|null
     * }> $items
     * @return PaymentSystem[]
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
