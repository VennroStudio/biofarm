<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\Product;

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\UniqueConstraint(name: 'uniq_products_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_products_category_id', columns: ['category_id'])]
#[ORM\Index(name: 'idx_products_is_active', columns: ['is_active'])]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $slug;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $name;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $h1;

    #[ORM\Column(name: 'seo_title', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $seoTitle;

    #[ORM\Column(name: 'seo_description', type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $seoDescription;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private(set) string $categoryId;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $price;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private(set) ?int $oldPrice;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private(set) string $image;

    #[ORM\Column(name: 'image_alt', type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $imageAlt;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private(set) ?array $images;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private(set) ?string $badge;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private(set) string $weight;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private(set) ?string $sku;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private(set) ?string $gtin;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => 'in_stock'])]
    private(set) string $availability;

    #[ORM\Column(type: Types::TEXT)]
    private(set) string $description;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $shortDescription;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private(set) ?string $ingredients;

    #[ORM\Column(name: 'usage_text', type: Types::TEXT, nullable: true)]
    private(set) ?string $usageText;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private(set) ?string $contraindications;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private(set) ?string $country;

    #[ORM\Column(name: 'shelf_life', type: Types::STRING, length: 100, nullable: true)]
    private(set) ?string $shelfLife;

    #[ORM\Column(name: 'storage_conditions', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $storageConditions;

    #[ORM\Column(name: 'bad_disclaimer', type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $badDisclaimer;

    #[ORM\Column(name: 'active_components_text', type: Types::TEXT, nullable: true)]
    private(set) ?string $activeComponentsText;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private(set) ?array $features;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $wbLink;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $ozonLink;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private(set) bool $isActive;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $publishedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $deletedAt = null;

    /**
     * @param list<string>|null $images
     * @param list<string>|null $features
     * @throws DateMalformedStringException
     */
    private function __construct(
        string $slug,
        string $name,
        string $categoryId,
        int $price,
        string $image,
        string $weight,
        string $description,
        ?string $shortDescription,
        ?int $oldPrice,
        ?array $images,
        ?string $badge,
        ?string $ingredients,
        ?string $usageText,
        ?string $contraindications,
        ?string $country,
        ?string $shelfLife,
        ?string $storageConditions,
        ?string $badDisclaimer,
        ?string $activeComponentsText,
        ?array $features,
        ?string $wbLink,
        ?string $ozonLink,
        bool $isActive,
        ?string $h1,
        ?string $seoTitle,
        ?string $seoDescription,
        ?string $imageAlt,
        ?string $sku,
        ?string $gtin,
        string $availability,
        ?DateTimeImmutable $publishedAt,
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->h1 = $h1;
        $this->seoTitle = $seoTitle;
        $this->seoDescription = $seoDescription;
        $this->categoryId = $categoryId;
        $this->price = $price;
        $this->oldPrice = $oldPrice;
        $this->image = $image;
        $this->imageAlt = $imageAlt;
        $this->images = $images;
        $this->badge = $badge;
        $this->weight = $weight;
        $this->sku = $sku;
        $this->gtin = $gtin;
        $this->availability = $availability;
        $this->description = $description;
        $this->shortDescription = $shortDescription;
        $this->ingredients = $ingredients;
        $this->usageText = $usageText;
        $this->contraindications = $contraindications;
        $this->country = $country;
        $this->shelfLife = $shelfLife;
        $this->storageConditions = $storageConditions;
        $this->badDisclaimer = $badDisclaimer;
        $this->activeComponentsText = $activeComponentsText;
        $this->features = $features;
        $this->wbLink = $wbLink;
        $this->ozonLink = $ozonLink;
        $this->isActive = $isActive;
        $this->createdAt = UtcClock::now();
        $this->publishedAt = $publishedAt;
    }

    /**
     * @param list<string>|null $images
     * @param list<string>|null $features
     * @throws DateMalformedStringException
     */
    public static function create(
        string $slug,
        string $name,
        string $categoryId,
        int $price,
        string $image,
        string $weight,
        string $description,
        ?string $shortDescription = null,
        ?int $oldPrice = null,
        ?array $images = null,
        ?string $badge = null,
        ?string $ingredients = null,
        ?string $usageText = null,
        ?string $contraindications = null,
        ?string $country = null,
        ?string $shelfLife = null,
        ?string $storageConditions = null,
        ?string $badDisclaimer = null,
        ?string $activeComponentsText = null,
        ?array $features = null,
        ?string $wbLink = null,
        ?string $ozonLink = null,
        bool $isActive = true,
        ?string $h1 = null,
        ?string $seoTitle = null,
        ?string $seoDescription = null,
        ?string $imageAlt = null,
        ?string $sku = null,
        ?string $gtin = null,
        string $availability = 'in_stock',
        ?DateTimeImmutable $publishedAt = null,
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
            usageText: $usageText,
            contraindications: $contraindications,
            country: $country,
            shelfLife: $shelfLife,
            storageConditions: $storageConditions,
            badDisclaimer: $badDisclaimer,
            activeComponentsText: $activeComponentsText,
            features: $features,
            wbLink: $wbLink,
            ozonLink: $ozonLink,
            isActive: $isActive,
            h1: $h1,
            seoTitle: $seoTitle,
            seoDescription: $seoDescription,
            imageAlt: $imageAlt,
            sku: $sku,
            gtin: $gtin,
            availability: $availability,
            publishedAt: $publishedAt,
        );
    }

    /**
     * @param list<string>|null $images
     * @param list<string>|null $features
     * @throws DateMalformedStringException
     */
    public function edit(
        string $slug,
        string $name,
        string $categoryId,
        int $price,
        string $image,
        string $weight,
        string $description,
        ?string $shortDescription,
        ?int $oldPrice,
        ?array $images,
        ?string $badge,
        ?string $ingredients,
        ?string $usageText,
        ?string $contraindications,
        ?string $country,
        ?string $shelfLife,
        ?string $storageConditions,
        ?string $badDisclaimer,
        ?string $activeComponentsText,
        ?array $features,
        ?string $wbLink,
        ?string $ozonLink,
        bool $isActive,
        ?string $h1 = null,
        ?string $seoTitle = null,
        ?string $seoDescription = null,
        ?string $imageAlt = null,
        ?string $sku = null,
        ?string $gtin = null,
        string $availability = 'in_stock',
        ?DateTimeImmutable $publishedAt = null,
    ): void {
        $this->assertNotDeleted();
        $this->slug = $slug;
        $this->name = $name;
        $this->h1 = $h1;
        $this->seoTitle = $seoTitle;
        $this->seoDescription = $seoDescription;
        $this->categoryId = $categoryId;
        $this->price = $price;
        $this->image = $image;
        $this->imageAlt = $imageAlt;
        $this->weight = $weight;
        $this->sku = $sku;
        $this->gtin = $gtin;
        $this->availability = $availability;
        $this->description = $description;
        $this->shortDescription = $shortDescription;
        $this->oldPrice = $oldPrice;
        $this->images = $images;
        $this->badge = $badge;
        $this->ingredients = $ingredients;
        $this->usageText = $usageText;
        $this->contraindications = $contraindications;
        $this->country = $country;
        $this->shelfLife = $shelfLife;
        $this->storageConditions = $storageConditions;
        $this->badDisclaimer = $badDisclaimer;
        $this->activeComponentsText = $activeComponentsText;
        $this->features = $features;
        $this->wbLink = $wbLink;
        $this->ozonLink = $ozonLink;
        $this->isActive = $isActive;
        $this->publishedAt = $publishedAt;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markDeleted(): void
    {
        $this->assertNotDeleted();
        $this->deletedAt = UtcClock::now();
        $this->isActive = false;
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
                message: 'error.product_is_deleted',
                code: 13,
            );
        }
    }
}
