<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductPurposeRelation;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_purpose_relations')]
#[ORM\UniqueConstraint(name: 'uniq_product_purpose_relations_product_purpose', columns: ['product_id', 'purpose_id'])]
#[ORM\Index(name: 'idx_product_purpose_relations_product_id', columns: ['product_id'])]
#[ORM\Index(name: 'idx_product_purpose_relations_purpose_id', columns: ['purpose_id'])]
class ProductPurposeRelation
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(name: 'product_id', type: Types::INTEGER)]
    private(set) int $productId;

    #[ORM\Column(name: 'purpose_id', type: Types::INTEGER)]
    private(set) int $purposeId;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0])]
    private(set) int $sortOrder;

    private function __construct(int $productId, int $purposeId, int $sortOrder)
    {
        $this->productId = $productId;
        $this->purposeId = $purposeId;
        $this->sortOrder = $sortOrder;
    }

    public static function create(int $productId, int $purposeId, int $sortOrder = 0): self
    {
        return new self($productId, $purposeId, $sortOrder);
    }
}
