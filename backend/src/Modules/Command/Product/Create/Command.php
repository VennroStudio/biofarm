<?php

declare(strict_types=1);

namespace App\Modules\Command\Product\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $slug,
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        public string $categoryId,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $price,
        #[Assert\NotBlank]
        public string $image,
        #[Assert\NotBlank]
        public string $weight,
        #[Assert\NotBlank]
        public string $description,
        public ?string $shortDescription = null,
        public ?int $oldPrice = null,
        /** @var string[]|null */
        public ?array $images = null,
        public ?string $badge = null,
        public ?string $ingredients = null,
        /** @var string[]|null */
        public ?array $features = null,
        public ?string $wbLink = null,
        public ?string $ozonLink = null,
        public bool $isActive = true,
    ) {}
}
