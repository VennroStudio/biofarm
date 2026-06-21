<?php

declare(strict_types=1);

namespace App\Modules\Blog\Service;

use App\Components\Exception\AccessDeniedException;
use App\Modules\Blog\Permission\BlogPermission;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;

final readonly class BlogPermissionService
{
    /**
     * @throws AccessDeniedException
     */
    public function checkRole(UserRole $currentUserRole, BlogPermission $action): void
    {
        if (!\in_array($currentUserRole, $this->getAllowedRolesForAction($action), true)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return list<UserRole>
     */
    private function getAllowedRolesForAction(BlogPermission $action): array
    {
        return match ($action) {
            BlogPermission::CREATE,
            BlogPermission::UPDATE,
            BlogPermission::DELETE => [
                UserRole::ADMIN,
                UserRole::EDITOR,
            ],
        };
    }
}
