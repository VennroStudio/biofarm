<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\Product\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProductCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.product_name_required')]
        public string $name,
        #[Assert\NotBlank(message: 'validation.product_category_required')]
        public string $categoryId,
        #[Assert\NotBlank(message: 'validation.product_price_required')]
        #[Assert\Positive(message: 'validation.product_price_positive')]
        public int $price,
        #[Assert\NotBlank(message: 'validation.product_image_required')]
        public string $image,
        #[Assert\NotBlank(message: 'validation.product_weight_required')]
        public string $weight,
        #[Assert\NotBlank(message: 'validation.product_description_required')]
        public string $description,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
        public ?string $slug = null,
        public ?string $shortDescription = null,
        public ?int $oldPrice = null,
        /** @var list<string>|null */
        public ?array $images = null,
        public ?string $badge = null,
        public ?string $ingredients = null,
        /** @var list<string>|null */
        public ?array $features = null,
        public ?string $wbLink = null,
        public ?string $ozonLink = null,
        public bool $isActive = true,
    ) {}
}
