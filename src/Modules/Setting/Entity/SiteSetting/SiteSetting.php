<?php

declare(strict_types=1);

namespace App\Modules\Setting\Entity\SiteSetting;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'site_settings')]
class SiteSetting
{
    #[ORM\Id]
    #[ORM\Column(name: '`key`', type: Types::STRING, length: 100)]
    private(set) string $key;

    /** @var array<array-key, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> */
    #[ORM\Column(type: Types::JSON)]
    private(set) array $value;

    /**
     * @param array<array-key, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $value
     */
    private function __construct(string $key, array $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @param array<array-key, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $value
     */
    public static function create(string $key, array $value): self
    {
        return new self($key, $value);
    }

    /**
     * @param array<array-key, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $value
     */
    public function changeValue(array $value): void
    {
        $this->value = $value;
    }
}
