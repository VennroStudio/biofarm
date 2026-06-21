<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\ProductCategory\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProductCategoryCommand
{
    private const int NAME_MIN_LENGTH = 2;
    private const int NAME_MAX_LENGTH = 255;

    public function __construct(
        #[Assert\NotBlank(message: 'validation.category_name_required')]
        #[Assert\Length(
            min: self::NAME_MIN_LENGTH,
            max: self::NAME_MAX_LENGTH,
            minMessage: 'validation.category_name_too_short',
            maxMessage: 'validation.category_name_too_long',
        )]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
        public ?string $slug = null,
    ) {}
}
