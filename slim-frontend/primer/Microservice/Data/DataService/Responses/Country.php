<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class Country
{
    /**
     * @param CountryPhone[]|null $phones
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $fullName,
        public ?string $nameOwn,
        public bool $isActual,
        public ?string $code,
        public ?CountryIcon $icons,
        public ?array $phones,
    ) {}

    /**
     * @param array{
     *      id: int,
     *      name: string,
     *      fullName?: string|null,
     *      nameOwn?: string|null,
     *      isActual: bool,
     *      code?: string|null,
     *      icons?: array{circle?: string|null}|null,
     *      phones?: array<int, array{code: string, length: int, mask: string, isMain: bool}>|null
     *} $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'] ?? 0,
            name: $item['name'] ?? '',
            fullName: $item['fullName'] ?? null,
            nameOwn: $item['nameOwn'] ?? null,
            isActual: $item['isActual'] ?? false,
            code: $item['code'] ?? null,
            icons: isset($item['icons']) ? CountryIcon::fromArray($item['icons']) : null,
            phones: isset($item['phones']) ? CountryPhone::fromArrayList($item['phones']) : null,
        );
    }

    /**
     * @param array<int, array{
     *       id: int,
     *       name: string,
     *       fullName?: string|null,
     *       nameOwn?: string|null,
     *       isActual: bool,
     *       code?: string|null,
     *       icons?: array{circle?: string|null}|null,
     *       phones?: array<int, array{code: string, length: int, mask: string, isMain: bool}>|null
     *  }> $items
     * @return Country[]
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
