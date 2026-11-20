<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\DeleteAvatar;

final readonly class DeleteAvatarCommand
{
    public function __construct(
        public int $userId,
        public int $currentUserId,
        public int $currentUserRole,
    ) {}
}
