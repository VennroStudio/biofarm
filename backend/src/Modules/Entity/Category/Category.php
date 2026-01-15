<?php

declare(strict_types=1);

namespace App\Modules\Entity\Category;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: Category::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_SLUG', columns: ['slug'])]
final class Category
{
    public const DB_NAME = 'biofarm_categories';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    private function __construct(
        string $slug,
        string $name,
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->createdAt = time();
    }

    public static function create(
        string $slug,
        string $name,
    ): self {
        return new self(
            slug: $slug,
            name: $name,
        );
    }

    public function edit(
        string $name,
        ?string $slug = null,
    ): void {
        $this->name = $name;
        if ($slug !== null) {
            $this->slug = $slug;
        }
        $this->updatedAt = time();
    }

    public function getId(): int
    {
        if (null === $this->id) {
            throw new DomainException('Id not set');
        }
        return (int)$this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }
}
