<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\DeleteAvatar;

use App\Components\Cacher\Cacher;
use App\Components\Exception\AccessDeniedException;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\Storage\StorageInterface;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Permission\UserPermission;
use App\Modules\User\Service\UserPermissionService;
use DateMalformedStringException;

final readonly class DeleteAvatarHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPermissionService $userPermissionService,
        private StorageInterface $storage,
        private FlusherInterface $flusher,
        private Cacher $cacher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws AccessDeniedException
     */
    public function handle(DeleteAvatarCommand $command): void
    {
        $user = $this->userRepository->getById($command->userId);

        $this->userPermissionService->check(
            currentUserId: $command->currentUserId,
            currentUserRole: UserRole::from($command->currentUserRole),
            userId: $command->userId,
            action: UserPermission::UPDATE,
        );

        $this->deleteFromStorage($user->avatar);

        $user->setAvatar(null);

        $this->cacher->delete('user_identity_' . $command->userId);

        $this->flusher->flush();
    }

    private function deleteFromStorage(?string $currentAvatarPath): void
    {
        if ($currentAvatarPath === null || $currentAvatarPath === '') {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.avatar_not_found',
                code: 12,
            );
        }

        $this->storage->delete($currentAvatarPath);
    }
}
