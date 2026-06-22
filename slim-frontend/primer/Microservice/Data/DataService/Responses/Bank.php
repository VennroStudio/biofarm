<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class Bank
{
    /**
     * @param string[]|null $background
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?array $background,
        public ?string $color,
        public ?BankIcon $icon,
        public ?BankLogo $logo,
        public ?string $phone,
        public ?string $site,
        public ?string $wiki,
    ) {}

    /**
     * @param array{
     *      id: int,
     *      name: string,
     *      background?: string[]|null,
     *      color?: string|null,
     *      icon?: array{
     *          card?: string|null,
     *          circle?: string|null,
     *          original?: string|null
     *      }|null,
     *      logo?: array{
     *          card?: string|null,
     *          original?: string|null
     *      }|null,
     *      phone?: string|null,
     *      site?: string|null,
     *      wiki?: string|null
     * } $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'] ?? 0,
            name: $item['name'] ?? '',
            background: $item['background'] ?? null,
            color: $item['color'] ?? null,
            icon: isset($item['icon']) ? BankIcon::fromArray($item['icon']) : null,
            logo: isset($item['logo']) ? BankLogo::fromArray($item['logo']) : null,
            phone: $item['phone'] ?? null,
            site: $item['site'] ?? null,
            wiki: $item['wiki'] ?? null,
        );
    }

    /**
     * @param array<int, array{
     *      id: int,
     *      name: string,
     *      background?: string[]|null,
     *      color?: string|null,
     *      icon?: array{
     *          card?: string|null,
     *          circle?: string|null,
     *          original?: string|null
     *      }|null,
     *      logo?: array{
     *          card?: string|null,
     *          original?: string|null
     *      }|null,
     *      phone?: string|null,
     *      site?: string|null,
     *      wiki?: string|null
     * }> $items
     * @return Bank[]
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
