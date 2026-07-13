<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductComponent;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_components')]
#[ORM\UniqueConstraint(name: 'uniq_product_components_product_component', columns: ['product_id', 'component_id'])]
#[ORM\Index(name: 'idx_product_components_product_id', columns: ['product_id'])]
#[ORM\Index(name: 'idx_product_components_component_id', columns: ['component_id'])]
class ProductComponent
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(name: 'product_id', type: Types::INTEGER)]
    private(set) int $productId;

    #[ORM\Column(name: 'component_id', type: Types::INTEGER)]
    private(set) int $componentId;

    #[ORM\Column(name: 'amount_text', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $amountText;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0])]
    private(set) int $sortOrder;

    private function __construct(
        int $productId,
        int $componentId,
        ?string $amountText,
        int $sortOrder,
    ) {
        $this->productId = $productId;
        $this->componentId = $componentId;
        $this->amountText = $amountText;
        $this->sortOrder = $sortOrder;
    }

    public static function create(
        int $productId,
        int $componentId,
        ?string $amountText = null,
        int $sortOrder = 0,
    ): self {
        return new self($productId, $componentId, $amountText, $sortOrder);
    }
}
