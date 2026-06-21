<?php

declare(strict_types=1);

namespace App\Modules\Order\Service;

use App\Components\Exception\AccessDeniedException;
use App\Modules\Order\Permission\OrderPermission;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;

final readonly class OrderPermissionService
{
    /**
     * @throws AccessDeniedException
     */
    public function checkRole(UserRole $currentUserRole, OrderPermission $action): void
    {
        if (!\in_array($currentUserRole, $this->getAllowedRolesForAction($action), true)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return list<UserRole>
     */
    private function getAllowedRolesForAction(OrderPermission $action): array
    {
        return match ($action) {
            OrderPermission::CREATE,
            OrderPermission::UPDATE,
            OrderPermission::DELETE => [
                UserRole::ADMIN,
                UserRole::EDITOR,
            ],
        };
    }
}
