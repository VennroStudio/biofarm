<?php

declare(strict_types=1);

namespace App\Modules\Entity\Review;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: Review::DB_NAME)]
#[ORM\Index(fields: ['productId'], name: 'IDX_PRODUCT')]
#[ORM\Index(fields: ['isApproved'], name: 'IDX_APPROVED')]
final class Review
{
    public const DB_NAME = 'biofarm_reviews';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $id;

    #[ORM\Column(type: 'bigint')]
    private int $productId;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $userName;

    #[ORM\Column(type: 'integer')]
    private int $rating; // 1-5

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $images = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $source; // site, wildberries, ozon

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isApproved = false;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    private function __construct(
        string $id,
        int $productId,
        string $userName,
        int $rating,
        string $text,
        string $source,
        ?string $userId = null,
        ?array $images = null,
        bool $isApproved = false,
    ) {
        $this->id = $id;
        $this->productId = $productId;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->rating = $rating;
        $this->text = $text;
        $this->images = $images;
        $this->source = $source;
        $this->isApproved = $isApproved;
        $this->createdAt = time();
    }

    public static function create(
        string $id,
        int $productId,
        string $userName,
        int $rating,
        string $text,
        string $source,
        ?string $userId = null,
        ?array $images = null,
        bool $isApproved = false,
    ): self {
        return new self(
            id: $id,
            productId: $productId,
            userName: $userName,
            rating: $rating,
            text: $text,
            source: $source,
            userId: $userId,
            images: $images,
            isApproved: $isApproved,
        );
    }

    public function approve(): void
    {
        $this->isApproved = true;
        $this->updatedAt = time();
    }

    public function edit(
        int $productId,
        string $userName,
        int $rating,
        string $text,
        string $source,
        ?string $userId = null,
        ?array $images = null,
    ): void {
        $this->productId = $productId;
        $this->userName = $userName;
        $this->rating = $rating;
        $this->text = $text;
        $this->source = $source;
        if ($userId !== null) {
            $this->userId = $userId;
        }
        if ($images !== null) {
            $this->images = $images;
        }
        $this->updatedAt = time();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
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
