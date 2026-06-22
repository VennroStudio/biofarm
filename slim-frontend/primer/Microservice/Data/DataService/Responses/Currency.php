<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class Currency
{
    public function __construct(
        public int $id,
        public string $icon,
        public string $alphabeticCode,
        public string $numericCode,
        public string $symbol,
        public ?string $name,
        public ?float $rate,
        public float $diffValue,
        public float $diffPercent,
        public ?string $hash = null,
        public ?float $currentRateSale = null,
        public ?float $currentRatePurchase = null,
    ) {}

    /**
     * @param array{
     *     id: int,
     *     icon?: string|null,
     *     alphabeticCode?: string|null,
     *     numericCode?: string|null,
     *     symbol?: string|null,
     *     name?: string|null,
     *     rate?: float|null,
     *     diffValue?: float|int|null,
     *     diffPercent?: float|int|null,
     *     hash?: string|null,
     *     currentRateSale?: float|null,
     *     currentRatePurchase?: float|null
     * } $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'],
            icon: $item['icon'] ?? '',
            alphabeticCode: $item['alphabeticCode'] ?? '',
            numericCode: $item['numericCode'] ?? '',
            symbol: $item['symbol'] ?? '',
            name: $item['name'] ?? null,
            rate: $item['rate'] ?? null,
            diffValue: (float)($item['diffValue'] ?? 0),
            diffPercent: (float)($item['diffPercent'] ?? 0),
            hash: $item['hash'] ?? null,
            currentRateSale: $item['currentRateSale'] ?? null,
            currentRatePurchase: $item['currentRatePurchase'] ?? null,
        );
    }

    /**
     * @param list<array{
     *     id: int,
     *     icon?: string|null,
     *     alphabeticCode?: string|null,
     *     numericCode?: string|null,
     *     symbol?: string|null,
     *     name?: string|null,
     *     rate?: float|null,
     *     diffValue?: float|int|null,
     *     diffPercent?: float|int|null,
     *     hash?: string|null,
     *     currentRateSale?: float|null,
     *     currentRatePurchase?: float|null
     * }> $items
     * @return Currency[]
     */
    public static function fromArrayList(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = self::fromArray($item);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'icon' => $this->icon,
            'alphabeticCode' => $this->alphabeticCode,
            'numericCode' => $this->numericCode,
            'symbol' => $this->symbol,
            'name' => $this->name,
            'rate' => $this->rate,
            'diffValue' => $this->diffValue,
            'diffPercent' => $this->diffPercent,
        ];
    }
}
