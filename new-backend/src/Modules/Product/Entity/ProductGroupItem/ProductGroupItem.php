<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductGroupItem;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_group_items')]
#[ORM\UniqueConstraint(name: 'uniq_product_group_items_product', columns: ['product_id'])]
#[ORM\UniqueConstraint(name: 'uniq_product_group_items_group_product', columns: ['group_id', 'product_id'])]
#[ORM\Index(name: 'idx_product_group_items_group_id', columns: ['group_id'])]
class ProductGroupItem
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(name: 'group_id', type: Types::INTEGER)]
    private(set) int $groupId;

    #[ORM\Column(name: 'product_id', type: Types::INTEGER)]
    private(set) int $productId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(int $groupId, int $productId)
    {
        $this->groupId = $groupId;
        $this->productId = $productId;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(int $groupId, int $productId): self
    {
        return new self($groupId, $productId);
    }
}
