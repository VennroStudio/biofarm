<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductCategory;

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
#[ORM\UniqueConstraint(name: 'uniq_categories_slug', columns: ['slug'])]
class ProductCategory
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $slug;

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
    private function __construct(string $slug, string $name)
    {
        $this->slug = $slug;
        $this->name = $name;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(string $slug, string $name): self
    {
        return new self($slug, $name);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function edit(string $slug, string $name): void
    {
        $this->assertNotDeleted();
        $this->slug = $slug;
        $this->name = $name;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markDeleted(): void
    {
        $this->assertNotDeleted();
        $this->deletedAt = UtcClock::now();
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    private function touch(): void
    {
        $this->updatedAt = UtcClock::now();
    }

    private function assertNotDeleted(): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.category_is_deleted',
                code: 3,
            );
        }
    }
}
