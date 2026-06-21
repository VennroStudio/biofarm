<?php

declare(strict_types=1);

namespace App\Modules\Review\Entity\Review;

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'reviews')]
#[ORM\Index(name: 'idx_reviews_product_id', columns: ['product_id'])]
#[ORM\Index(name: 'idx_reviews_is_approved', columns: ['is_approved'])]
class Review
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 50)]
    private(set) string $id;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $productId;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private(set) ?string $userId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $userName;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $rating;

    #[ORM\Column(type: Types::TEXT)]
    private(set) string $text;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private(set) ?array $images;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private(set) string $source;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private(set) bool $isApproved;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $deletedAt = null;

    /**
     * @param list<string>|null $images
     * @throws DateMalformedStringException
     */
    private function __construct(
        string $id,
        int $productId,
        string $userName,
        int $rating,
        string $text,
        string $source,
        ?string $userId,
        ?array $images,
        bool $isApproved,
    ) {
        $this->id = $id;
        $this->productId = $productId;
        $this->userName = $userName;
        $this->rating = $rating;
        $this->text = $text;
        $this->source = $source;
        $this->userId = $userId;
        $this->images = $images;
        $this->isApproved = $isApproved;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @param list<string>|null $images
     * @throws DateMalformedStringException
     */
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
        return new self($id, $productId, $userName, $rating, $text, $source, $userId, $images, $isApproved);
    }

    /**
     * @param list<string>|null $images
     * @throws DateMalformedStringException
     */
    public function edit(
        int $productId,
        string $userName,
        int $rating,
        string $text,
        string $source,
        ?string $userId,
        ?array $images,
    ): void {
        $this->assertNotDeleted();
        $this->productId = $productId;
        $this->userName = $userName;
        $this->rating = $rating;
        $this->text = $text;
        $this->source = $source;
        $this->userId = $userId;
        $this->images = $images;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function approve(): void
    {
        $this->assertNotDeleted();
        $this->isApproved = true;
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
                module: 'review',
                message: 'error.review_is_deleted',
                code: 2,
            );
        }
    }
}
