<?php

declare(strict_types=1);

namespace App\Modules\Command\Artist\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        public ?int $unionId,
        public ?string $communityName,
        public ?string $description,
        public ?int $categoryId,
        /** @var string[] */
        public array $links,
        public bool $isAutomatic = false
    ) {}
}
