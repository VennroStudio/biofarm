<?php

declare(strict_types=1);

namespace App\Modules\Entity\Settings;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: Settings::DB_NAME)]
final class Settings
{
    public const DB_NAME = 'biofarm_settings';

    #[ORM\Id]
    #[ORM\Column(name: '`key`', type: 'string', length: 50, unique: true)]
    private string $key;

    #[ORM\Column(type: 'text')]
    private string $value;

    #[ORM\Column(type: 'integer')]
    private int $updatedAt;

    private function __construct(
        string $key,
        string $value,
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->updatedAt = time();
    }

    public static function create(
        string $key,
        string $value,
    ): self {
        return new self(
            key: $key,
            value: $value,
        );
    }

    public function updateValue(string $value): void
    {
        $this->value = $value;
        $this->updatedAt = time();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getIntValue(): int
    {
        return (int)$this->value;
    }

    public function getBoolValue(): bool
    {
        return $this->value === '1' || $this->value === 'true';
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }
}
