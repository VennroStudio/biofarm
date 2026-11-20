# Enum

**Расположение:** `src/Modules/{Module}/Entity/{Entity}/Fields/Enums/{EnumName}.php`

---

## Состав Enum

Enum собирается только из тех блоков, которые нужны конкретному сценарию.

- Namespace и imports
- Backing type (`int`, `string` или unit enum без значения)
- Cases
- `EnumInterface` и `getLabel()`
- `RoleAwareEnumInterface` и `casesForRole()`
- Использование в Entity
- `getPath()` для enum-директорий

---

## Enum-справочник

Используется для enum-полей Entity, API-справочников и значений с человекочитаемым названием.

### Заголовок

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Entity\{Entity}\Fields\Enums;

use App\Components\Enum\EnumInterface;

enum {EnumName}: int implements EnumInterface
{
}
```

### Cases

```php
case ACTIVE = 1;
case INACTIVE = 2;
case ARCHIVED = 3;
```

### getLabel()

```php
public function getLabel(): string
{
    return match ($this) {
        self::ACTIVE => 'Активен',
        self::INACTIVE => 'Неактивен',
        self::ARCHIVED => 'В архиве',
    };
}
```

### Ограничение по роли

Используется только если набор значений зависит от роли пользователя.

```php
use App\Components\Enum\RoleAwareEnumInterface;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;

/**
 * @return list<self>
 */
public static function casesForRole(UserRole $role): array
{
    return match ($role) {
        UserRole::ADMIN => self::cases(),
        default => [self::ACTIVE, self::INACTIVE],
    };
}
```

### Использование в Entity

Правила Entity описаны отдельно в [Entity](entity.md). В Enum показывается только подключение enum-поля.

```php
#[ORM\Column(type: Types::INTEGER, enumType: {EnumName}::class)]
private(set) {EnumName} $status;
```

### Использование в Action

Enum-справочник для frontend описывается отдельно в [Action](action.md). В Action используется `EnumModel::fromEnumClass({EnumName}::class)` или `EnumModel::fromEnumClassForRole({EnumName}::class, $identity->role)`.

### Полный пример enum-справочника

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Entity\{Entity}\Fields\Enums;

use App\Components\Enum\EnumInterface;
use App\Components\Enum\RoleAwareEnumInterface;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;

enum {EnumName}: int implements EnumInterface, RoleAwareEnumInterface
{
    case ACTIVE = 1;
    case INACTIVE = 2;
    case ARCHIVED = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активен',
            self::INACTIVE => 'Неактивен',
            self::ARCHIVED => 'В архиве',
        };
    }

    /**
     * @return list<self>
     */
    public static function casesForRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::ADMIN => self::cases(),
            default => [self::ACTIVE, self::INACTIVE],
        };
    }
}
```

---

## Enum-директория

Используется для централизованного хранения путей загрузки файлов.

### Простая директория

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Entity\{Entity}\Fields\Enums;

enum {Entity}Directory: string
{
    case IMAGE = '{entity}/';
}
```

### Директория с динамическим путем

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Entity\{Entity}\Fields\Enums;

enum {Entity}Directory
{
    case FILES;

    public function getPath(int $entityId): string
    {
        return match ($this) {
            self::FILES => "{entity}/{$entityId}/files",
        };
    }
}
```

### Использование

```php
$path = {Entity}Directory::IMAGE->value;
```

```php
$path = {Entity}Directory::FILES->getPath($entityId);
```
