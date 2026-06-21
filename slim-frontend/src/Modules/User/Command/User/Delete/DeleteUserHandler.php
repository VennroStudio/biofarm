<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\Delete;

use App\Components\Cacher\Cacher;
use App\Components\Exception\AccessDeniedException;
use App\Components\Flusher\FlusherInterface;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use App\Modules\User\Entity\UserToken\UserTokenRepository;
use App\Modules\User\Permission\UserPermission;
use App\Modules\User\Service\UserPermissionService;
use DateMalformedStringException;

final readonly class DeleteUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserTokenRepository $userTokenRepository,
        private FlusherInterface $flusher,
        private UserPermissionService $userPermissionService,
        private Cacher $cacher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws AccessDeniedException
     */
    public function handle(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->getById($command->userId);

        $this->userPermissionService->check(
            currentUserId: $command->currentUserId,
            currentUserRole: UserRole::from($command->currentUserRole),
            userId: $command->userId,
            action: UserPermission::DELETE,
        );

        $user->markDeleted();
        $this->revokeRefreshTokens($command->userId);

        $this->cacher->delete('user_identity_' . $command->userId);

        $this->flusher->flush();
    }

    /**
     * @throws DateMalformedStringException
     */
    private function revokeRefreshTokens(int $userId): void
    {
        foreach ($this->userTokenRepository->findByUserIdAndType($userId, UserTokenType::REFRESH) as $token) {
            $token->revoke();
            $this->cacher->delete('user_token_' . $token->tokenHash);
        }
    }
}
