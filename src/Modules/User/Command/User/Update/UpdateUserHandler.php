<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\Update;

use App\Components\Cacher\Cacher;
use App\Components\Exception\AccessDeniedException;
use App\Components\Flusher\FlusherInterface;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Permission\UserPermission;
use App\Modules\User\Service\UserPermissionService;
use DateMalformedStringException;

final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private FlusherInterface $flusher,
        private UserPermissionService $userPermissionService,
        private Cacher $cacher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws AccessDeniedException
     */
    public function handle(UpdateUserCommand $command): void
    {
        $user = $this->userRepository->getById($command->userId);

        $this->userPermissionService->check(
            currentUserId: $command->currentUserId,
            currentUserRole: UserRole::from($command->currentUserRole),
            userId: $command->userId,
            action: UserPermission::UPDATE,
        );

        $user->edit(
            $command->lastName,
            $command->firstName,
        );

        $this->cacher->delete('user_identity_' . $command->userId);

        $this->flusher->flush();
    }
}
