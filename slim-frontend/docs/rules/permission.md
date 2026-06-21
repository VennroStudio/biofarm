# Permission

**Расположение:**
- Backend enum: `src/Modules/{Module}/Permission/{Module}Permission.php`
- Backend service: `src/Modules/{Module}/Service/{Module}PermissionService.php`
- UI enum: `src/Modules/{Module}/Permission/{Module}UiPermission.php`
- UI service: `src/Modules/{Module}/Service/{Module}UiPermissionService.php`

---

## Состав Permission

Permission собирается только из тех блоков, которые нужны конкретному модулю.

- Backend Permission enum
- Backend PermissionService
- UI Permission enum
- UI PermissionService
- PermissionModel component
- Использование в Handler
- Использование в Action

Backend permission и UI permission не смешиваются.

Backend permission нужен для защиты write-сценариев в Handler.

UI permission нужен только для frontend map: какие элементы интерфейса показывать пользователю.

---

## Backend Permission enum

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Permission;

enum {Module}Permission: string
{
    case CREATE = '{module}.create';
    case UPDATE = '{module}.update';
    case DELETE = '{module}.delete';
}
```

---

## Backend PermissionService

Backend service остается guard-сервисом: проверяет доступ и выбрасывает `AccessDeniedException`.

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Service;

use App\Components\Exception\AccessDeniedException;
use App\Modules\{Module}\Permission\{Module}Permission;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;

final readonly class {Module}PermissionService
{
    /**
     * @throws AccessDeniedException
     */
    public function checkRole(UserRole $currentUserRole, {Module}Permission $action): void
    {
        if (!\in_array($currentUserRole, $this->getAllowedRolesForAction($action), true)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @throws AccessDeniedException
     */
    public function checkOwner(int $currentUserId, int $ownerId, {Module}Permission $action): void
    {
        if ($currentUserId !== $ownerId) {
            throw new AccessDeniedException();
        }

        if (!\in_array($action, $this->getAllowedActionsForOwner(), true)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @throws AccessDeniedException
     */
    public function checkOwnerOrRole(
        int $currentUserId,
        int $ownerId,
        UserRole $currentUserRole,
        {Module}Permission $action,
    ): void {
        if ($currentUserId === $ownerId) {
            $this->checkOwner($currentUserId, $ownerId, $action);
            return;
        }

        $this->checkRole($currentUserRole, $action);
    }

    /**
     * @return list<{Module}Permission>
     */
    private function getAllowedActionsForOwner(): array
    {
        return [
            {Module}Permission::UPDATE,
        ];
    }

    /**
     * @return list<UserRole>
     */
    private function getAllowedRolesForAction({Module}Permission $action): array
    {
        $adminRoles = [
            UserRole::ADMIN,
        ];

        return match ($action) {
            {Module}Permission::CREATE,
            {Module}Permission::UPDATE,
            {Module}Permission::DELETE => $adminRoles,
        };
    }
}
```

Если в модуле не нужны owner-проверки, `checkOwner()`, `checkOwnerOrRole()` и `getAllowedActionsForOwner()` не добавляются.

---

## UI Permission enum

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Permission;

enum {Module}UiPermission: string
{
    case CREATE_BUTTON = '{module}.create_button';
    case UPDATE_BUTTON = '{module}.update_button';
    case DELETE_BUTTON = '{module}.delete_button';
}
```

---

## UI PermissionService

UI service отдает frontend map. В нем нет `check()` и backend guard-логики.

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Service;

use App\Components\Permission\PermissionModel;
use App\Modules\{Module}\Permission\{Module}UiPermission;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;

final readonly class {Module}UiPermissionService
{
    /**
     * @return array<string, bool>
     */
    public function permissionsForRole(UserRole $currentUserRole): array
    {
        return PermissionModel::fromEnumClass(
            {Module}UiPermission::class,
            fn ({Module}UiPermission $action): bool => \in_array(
                $currentUserRole,
                $this->getAllowedRolesForAction($action),
                true
            )
        );
    }

    /**
     * @return list<UserRole>
     */
    private function getAllowedRolesForAction({Module}UiPermission $action): array
    {
        $adminRoles = [
            UserRole::ADMIN,
        ];

        return match ($action) {
            {Module}UiPermission::CREATE_BUTTON,
            {Module}UiPermission::UPDATE_BUTTON,
            {Module}UiPermission::DELETE_BUTTON => $adminRoles,
        };
    }
}
```

---

## PermissionModel component

Компонент нужен только для сборки frontend map из UI enum. Он не содержит бизнес-логики и не знает про роли.

**Расположение:** `src/Components/Permission/PermissionModel.php`

```php
<?php

declare(strict_types=1);

namespace App\Components\Permission;

use BackedEnum;

final readonly class PermissionModel
{
    /**
     * @template T of BackedEnum
     * @param class-string<T> $permissionClass
     * @param callable(T): bool $resolver
     * @return array<string, bool>
     */
    public static function fromEnumClass(string $permissionClass, callable $resolver): array
    {
        $permissions = [];

        foreach ($permissionClass::cases() as $permission) {
            $permissions[(string) $permission->value] = $resolver($permission);
        }

        return $permissions;
    }
}
```

Ответ API:

```json
{
  "{module}.create_button": true,
  "{module}.update_button": true,
  "{module}.delete_button": false
}
```

Frontend:

```ts
if (permissions['{module}.create_button']) {
  showCreateButton()
}

if (permissions['{module}.delete_button']) {
  showDeleteButton()
}
```

---

## Использование в Handler

Handler использует только backend permission.

`currentUserRole` в Command приходит как `int`. В Handler приводится через `UserRole::from()`.

```php
$this->permissionService->checkRole(
    currentUserRole: UserRole::from($command->currentUserRole),
    action: {Module}Permission::UPDATE,
);
```

```php
$this->permissionService->checkOwner(
    currentUserId: $command->currentUserId,
    ownerId: $entity->ownerId,
    action: {Module}Permission::UPDATE,
);
```

```php
$this->permissionService->checkOwnerOrRole(
    currentUserId: $command->currentUserId,
    ownerId: $entity->ownerId,
    currentUserRole: UserRole::from($command->currentUserRole),
    action: {Module}Permission::DELETE,
);
```

---

## Использование в Action

Permission endpoint описывается в [Action](action.md). В Permission показывается только отдача UI map.

```php
$identity = RequestIdentity::get($request);

return new JsonDataResponse(
    $this->uiPermissionService->permissionsForRole($identity->role)
);
```
