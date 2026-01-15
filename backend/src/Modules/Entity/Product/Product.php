<?php

declare(strict_types=1);

namespace App\Modules\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: Product::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_SLUG', columns: ['slug'])]
#[ORM\Index(fields: ['categoryId'], name: 'IDX_CATEGORY')]
#[ORM\Index(fields: ['isActive'], name: 'IDX_ACTIVE')]
final class Product
{
    public const DB_NAME = 'biofarm_products';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50)]
    private string $categoryId;

    #[ORM\Column(type: 'integer')]
    private int $price;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $oldPrice = null;

    #[ORM\Column(type: 'string', length: 500)]
    private string $image;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $images = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $badge = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $weight;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 500)]
    private string $shortDescription;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $ingredients = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $features = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $wbLink = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $ozonLink = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    private function __construct(
        string $slug,
        string $name,
        string $categoryId,
        int $price,
        string $image,
        string $weight,
        string $description,
        string $shortDescription,
        ?int $oldPrice = null,
        ?array $images = null,
        ?string $badge = null,
        ?string $ingredients = null,
        ?array $features = null,
        ?string $wbLink = null,
        ?string $ozonLink = null,
        bool $isActive = true,
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->categoryId = $categoryId;
        $this->price = $price;
        $this->oldPrice = $oldPrice;
        $this->image = $image;
        $this->images = $images;
        $this->badge = $badge;
        $this->weight = $weight;
        $this->description = $description;
        $this->shortDescription = $shortDescription;
        $this->ingredients = $ingredients;
        $this->features = $features;
        $this->wbLink = $wbLink;
        $this->ozonLink = $ozonLink;
        $this->isActive = $isActive;
        $this->createdAt = time();
    }

    public static function create(
        string $slug,
        string $name,
        string $categoryId,
        int $price,
        string $image,
        string $weight,
        string $description,
        string $shortDescription,
        ?int $oldPrice = null,
        ?array $images = null,
        ?string $badge = null,
        ?string $ingredients = null,
        ?array $features = null,
        ?string $wbLink = null,
        ?string $ozonLink = null,
        bool $isActive = true,
    ): self {
        return new self(
            slug: $slug,
            name: $name,
            categoryId: $categoryId,
            price: $price,
            image: $image,
            weight: $weight,
            description: $description,
            shortDescription: $shortDescription,
            oldPrice: $oldPrice,
            images: $images,
            badge: $badge,
            ingredients: $ingredients,
            features: $features,
            wbLink: $wbLink,
            ozonLink: $ozonLink,
            isActive: $isActive,
        );
    }

    public function edit(
        string $name,
        string $categoryId,
        int $price,
        string $image,
        string $weight,
        string $description,
        string $shortDescription,
        ?int $oldPrice = null,
        ?array $images = null,
        ?string $badge = null,
        ?string $ingredients = null,
        ?array $features = null,
        ?string $wbLink = null,
        ?string $ozonLink = null,
        bool $isActive = true,
        ?string $slug = null,
    ): void {
        $this->name = $name;
        $this->categoryId = $categoryId;
        $this->price = $price;
        $this->oldPrice = $oldPrice;
        $this->image = $image;
        $this->images = $images;
        $this->badge = $badge;
        $this->weight = $weight;
        $this->description = $description;
        $this->shortDescription = $shortDescription;
        $this->ingredients = $ingredients;
        $this->features = $features;
        $this->wbLink = $wbLink;
        $this->ozonLink = $ozonLink;
        $this->isActive = $isActive;
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

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getOldPrice(): ?int
    {
        return $this->oldPrice;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function getWeight(): string
    {
        return $this->weight;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function getIngredients(): ?string
    {
        return $this->ingredients;
    }

    public function getFeatures(): ?array
    {
        return $this->features;
    }

    public function getWbLink(): ?string
    {
        return $this->wbLink;
    }

    public function getOzonLink(): ?string
    {
        return $this->ozonLink;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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
