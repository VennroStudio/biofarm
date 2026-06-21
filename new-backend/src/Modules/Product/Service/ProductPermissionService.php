<?php

declare(strict_types=1);

namespace App\Modules\Product\Service;

use App\Components\Exception\AccessDeniedException;
use App\Modules\Product\Permission\ProductPermission;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;

final readonly class ProductPermissionService
{
    /**
     * @throws AccessDeniedException
     */
    public function checkRole(UserRole $currentUserRole, ProductPermission $action): void
    {
        if (!\in_array($currentUserRole, $this->getAllowedRolesForAction($action), true)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return list<UserRole>
     */
    private function getAllowedRolesForAction(ProductPermission $action): array
    {
        return match ($action) {
            ProductPermission::CREATE,
            ProductPermission::UPDATE,
            ProductPermission::DELETE => [
                UserRole::ADMIN,
                UserRole::EDITOR,
            ],
        };
    }
}
