<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class CurrencyRate
{
    public function __construct(
        public ?float $rateSale,
        public ?float $ratePurchase,
        public int $time,
    ) {}

    /**
     * @param array{rateSale?: float|null, ratePurchase?: float|null, time: int} $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            rateSale: $item['rateSale'] ?? null,
            ratePurchase: $item['ratePurchase'] ?? null,
            time: $item['time'],
        );
    }
}
