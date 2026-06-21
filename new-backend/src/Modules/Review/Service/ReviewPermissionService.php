<?php

declare(strict_types=1);

namespace App\Modules\Review\Service;

use App\Components\Exception\AccessDeniedException;
use App\Modules\Review\Permission\ReviewPermission;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;

final readonly class ReviewPermissionService
{
    /**
     * @throws AccessDeniedException
     */
    public function checkRole(UserRole $currentUserRole, ReviewPermission $action): void
    {
        if (!\in_array($currentUserRole, $this->getAllowedRolesForAction($action), true)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return list<UserRole>
     */
    private function getAllowedRolesForAction(ReviewPermission $action): array
    {
        return match ($action) {
            ReviewPermission::CREATE,
            ReviewPermission::UPDATE,
            ReviewPermission::DELETE => [UserRole::ADMIN, UserRole::EDITOR],
        };
    }
}
