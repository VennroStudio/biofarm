<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductImage;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_images')]
#[ORM\Index(name: 'idx_product_images_product_id', columns: ['product_id'])]
#[ORM\Index(name: 'idx_product_images_is_main', columns: ['is_main'])]
#[ORM\Index(name: 'idx_product_images_sort_order', columns: ['sort_order'])]
class ProductImage
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(name: 'product_id', type: Types::INTEGER)]
    private(set) int $productId;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private(set) string $path;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $alt;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $title;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0])]
    private(set) int $sortOrder;

    #[ORM\Column(name: 'is_main', type: Types::BOOLEAN, options: ['default' => false])]
    private(set) bool $isMain;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private(set) ?int $width;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private(set) ?int $height;

    #[ORM\Column(name: 'mime_type', type: Types::STRING, length: 100, nullable: true)]
    private(set) ?string $mimeType;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private(set) ?int $size;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(
        int $productId,
        string $path,
        ?string $alt,
        ?string $title,
        int $sortOrder,
        bool $isMain,
        ?int $width,
        ?int $height,
        ?string $mimeType,
        ?int $size,
    ) {
        $this->productId = $productId;
        $this->path = $path;
        $this->alt = $alt;
        $this->title = $title;
        $this->sortOrder = $sortOrder;
        $this->isMain = $isMain;
        $this->width = $width;
        $this->height = $height;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        int $productId,
        string $path,
        ?string $alt = null,
        ?string $title = null,
        int $sortOrder = 0,
        bool $isMain = false,
        ?int $width = null,
        ?int $height = null,
        ?string $mimeType = null,
        ?int $size = null,
    ): self {
        return new self($productId, $path, $alt, $title, $sortOrder, $isMain, $width, $height, $mimeType, $size);
    }
}
