<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductAttributeValue;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_attribute_values')]
#[ORM\UniqueConstraint(name: 'uniq_product_attribute_values_product_value', columns: ['product_id', 'attribute_value_id'])]
#[ORM\Index(name: 'idx_product_attribute_values_product_id', columns: ['product_id'])]
#[ORM\Index(name: 'idx_product_attribute_values_value_id', columns: ['attribute_value_id'])]
class ProductAttributeValue
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(name: 'product_id', type: Types::INTEGER)]
    private(set) int $productId;

    #[ORM\Column(name: 'attribute_value_id', type: Types::INTEGER)]
    private(set) int $attributeValueId;

    #[ORM\Column(name: 'value_text', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $valueText;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0])]
    private(set) int $sortOrder;

    private function __construct(int $productId, int $attributeValueId, ?string $valueText, int $sortOrder)
    {
        $this->productId = $productId;
        $this->attributeValueId = $attributeValueId;
        $this->valueText = $valueText;
        $this->sortOrder = $sortOrder;
    }

    public static function create(int $productId, int $attributeValueId, ?string $valueText = null, int $sortOrder = 0): self
    {
        return new self($productId, $attributeValueId, $valueText, $sortOrder);
    }
}
