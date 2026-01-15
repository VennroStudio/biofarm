<?php

declare(strict_types=1);

namespace App\Modules\Command\Playlist\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $unionId,
        #[Assert\NotBlank]
        public int $userId,
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        public string $url,
        public bool $isFollowed = false,
        /**
         * @var array{
         *     lang: string,
         *     photo_host: string,
         *     photo_file_id: string,
         *     name: string,
         *     description: string,
         * }[]
         */
        public array $translates = [],
    ) {}
}
