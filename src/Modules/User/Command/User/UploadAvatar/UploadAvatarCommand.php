<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\UploadAvatar;

final readonly class UploadAvatarCommand
{
    public function __construct(
        public int $userId,
        public int $currentUserId,
        public int $currentUserRole,
        public string $tmpFilePath,
    ) {}
}
