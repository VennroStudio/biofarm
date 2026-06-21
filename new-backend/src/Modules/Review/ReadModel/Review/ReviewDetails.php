<?php

declare(strict_types=1);

namespace App\Modules\Review\ReadModel\Review;

use App\Components\ReadModel\FromRowsTrait;
use App\Modules\Review\ReadModel\Review\Interface\ReviewModelInterface;
use Override;

final readonly class ReviewDetails implements ReviewModelInterface
{
    use FromRowsTrait;

    /**
     * @param list<string>|null $images
     */
    public function __construct(
        public string $id,
        public int $productId,
        public ?string $userId,
        public string $userName,
        public int $rating,
        public string $text,
        public ?array $images,
        public string $source,
        public bool $isApproved,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fields(): array
    {
        return [
            'id'          => 'id',
            'product_id'  => 'product_id',
            'user_id'     => 'user_id',
            'user_name'   => 'user_name',
            'rating'      => 'rating',
            'text'        => 'text',
            'images'      => 'images',
            'source'      => 'source',
            'is_approved' => 'is_approved',
            'created_at'  => 'created_at',
            'updated_at'  => 'updated_at',
        ];
    }

    /**
     * @param array{
     *     id: string,
     *     product_id: int,
     *     user_id: string|null,
     *     user_name: string,
     *     rating: int,
     *     text: string,
     *     images: list<string>|string|null,
     *     source: string,
     *     is_approved: int|string|bool,
     *     created_at: string,
     *     updated_at: string|null
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: $row['id'],
            productId: (int)$row['product_id'],
            userId: $row['user_id'],
            userName: $row['user_name'],
            rating: (int)$row['rating'],
            text: $row['text'],
            images: self::jsonList($row['images']),
            source: $row['source'],
            isApproved: (bool)(int)$row['is_approved'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );
    }

    #[Override]
    public function getId(): string
    {
        return $this->id;
    }

    #[Override]
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'product_id'  => $this->productId,
            'user_id'     => $this->userId,
            'user_name'   => $this->userName,
            'rating'      => $this->rating,
            'text'        => $this->text,
            'images'      => $this->images,
            'source'      => $this->source,
            'is_approved' => $this->isApproved,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }

    /**
     * @return list<string>|null
     */
    private static function jsonList(array|string|null $value): ?array
    {
        if ($value === null || \is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return \is_array($decoded) ? $decoded : null;
    }
}
