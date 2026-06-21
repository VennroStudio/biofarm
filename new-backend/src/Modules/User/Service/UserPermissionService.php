<?php

declare(strict_types=1);

namespace App\Modules\User\Service;

use App\Components\Exception\AccessDeniedException;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Permission\UserPermission;

final readonly class UserPermissionService
{
    /**
     * @throws AccessDeniedException
     */
    public function check(int $currentUserId, UserRole $currentUserRole, int $userId, UserPermission $action): void
    {
        if (!$this->hasAccess($currentUserId, $currentUserRole, $userId, $action)) {
            throw new AccessDeniedException();
        }
    }

    public function hasAccess(int $currentUserId, UserRole $currentUserRole, int $userId, UserPermission $action): bool
    {
        if ($currentUserId === $userId) {
            return true;
        }

        return \in_array($currentUserRole, $this->getAllowedRolesForAction($action), true);
    }

    private function getAllowedRolesForAction(UserPermission $action): array
    {
        $adminRoles = [
            UserRole::ADMIN,
        ];

        return match ($action) {
            UserPermission::UPDATE,
            UserPermission::DELETE => $adminRoles,
        };
    }
}
