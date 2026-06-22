<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class Language
{
    public function __construct(
        public int $id,
        public string $nameOwn,
        public string $name,
        public string $fullName,
        public bool $isRTL,
        public string $code,
        public ?LanguageIcons $icons,
    ) {}

    /**
     * @param array{
     *     id: int,
     *     nameOwn: string,
     *     name: string,
     *     fullName: string,
     *     isRTL: bool,
     *     code: string,
     *     icons?: array{circle?: string|null}|null
     * } $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'] ?? 0,
            nameOwn: $item['nameOwn'] ?? '',
            name: $item['name'] ?? '',
            fullName: $item['fullName'] ?? '',
            isRTL: $item['isRTL'] ?? false,
            code: $item['code'] ?? '',
            icons: isset($item['icons']) ? LanguageIcons::fromArray($item['icons']) : null,
        );
    }

    /**
     * @param array<int, array{
     *     id: int,
     *     nameOwn: string,
     *     name: string,
     *     fullName: string,
     *     isRTL: bool,
     *     code: string,
     *     icons?: array{circle?: string|null}|null
     * }> $items
     * @return Language[]
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
