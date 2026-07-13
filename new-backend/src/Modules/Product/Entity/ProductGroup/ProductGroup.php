<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductGroup;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_groups')]
class ProductGroup
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $name;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $deletedAt = null;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(string $name)
    {
        $this->name = $name;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(string $name): self
    {
        return new self($name);
    }
}
