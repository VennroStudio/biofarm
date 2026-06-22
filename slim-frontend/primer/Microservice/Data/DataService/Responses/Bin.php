<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class Bin
{
    public function __construct(
        public string $bin,
        public ?string $category,
        public ?string $type,
        public ?Bank $bank,
        public ?PaymentSystem $paymentSystem,
    ) {}

    /**
     * @param array{
     *     bin: string,
     *     category?: string|null,
     *     type?: string|null,
     *     bank?: array{
     *         id: int,
     *         name: string,
     *         color?: string|null,
     *         background?: string[]|null,
     *         logo?: array{card?: string|null, original?: string|null}|null,
     *         icon?: array{card?: string|null, circle?: string|null, original?: string|null}|null,
     *         phone?: string|null,
     *         site?: string|null,
     *         wiki?: string|null
     *     }|null,
     *     paymentSystem?: array{id: int, name: string, logo?: string|null, site?: string|null, wiki?: string|null}|null
     * } $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            bin: $item['bin'] ?? '',
            category: $item['category'] ?? null,
            type: $item['type'] ?? null,
            bank: isset($item['bank']) ? Bank::fromArray($item['bank']) : null,
            paymentSystem: isset($item['paymentSystem']) ? PaymentSystem::fromArray($item['paymentSystem']) : null,
        );
    }
}
