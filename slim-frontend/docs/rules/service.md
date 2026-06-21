# Service

**Расположение:** `src/Modules/{Module}/Service/{Name}Service.php`

---

## Назначение

Service - это вспомогательная логика модуля.

Он нужен, чтобы не превращать `Handler`, `Entity` и `Repository` в мусорный класс со всем подряд.

Service создается только когда в модуле есть отдельная задача:

- сложная валидация или нормализация данных;
- синхронизация связанных сущностей;
- генерация, хеширование, расчет;
- файловая логика конкретного модуля;
- координация нескольких Handler'ов подсущностей.

Не нужно создавать Service ради одного простого вызова.
Если логика спокойно помещается в Handler и не раздувает его, отдельный Service не нужен.

В этом файле описываются только сервисы модулей из `src/Modules/{Module}/Service/`.
Переиспользуемые сервисы из `src/Components/` здесь не описываются.

PermissionService описывается отдельно в [Permission](permission.md).

---

## ValidatorService

Используется, когда проверка данных состоит из нескольких условий, проверок списков или запросов.

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Service;

use App\Components\Exception\DomainExceptionModule;

final readonly class {Entity}ValidatorService
{
    /**
     * @param list<{Item}> $items
     */
    public function validate(array $items): void
    {
        foreach ($items as $item) {
            if ($item->name === '') {
                throw new DomainExceptionModule(
                    module: '{module}',
                    message: 'error.{entity}_name_required',
                    code: 1,
                );
            }
        }
    }
}
```

---

## SyncerService

Используется, когда основная сущность имеет связанные таблицы, которые нужно создать, обновить или удалить одним сценарием.

Пример: `{Entity}` и `{Entity}Image`, `{Entity}` и `{Entity}Option`, `{Entity}` и `{Entity}Price`.

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Service;

use App\Modules\{Module}\Command\{ChildEntity}\Create\Create{ChildEntity}Handler;
use App\Modules\{Module}\Command\{ChildEntity}\Delete\Delete{ChildEntity}Handler;
use App\Modules\{Module}\Command\{ChildEntity}\Update\Update{ChildEntity}Handler;
use App\Modules\{Module}\Entity\{ChildEntity}\{ChildEntity}Repository;

final readonly class {Entity}StructureSyncerService
{
    public function __construct(
        private {ChildEntity}Repository $repository,
        private Create{ChildEntity}Handler $createHandler,
        private Update{ChildEntity}Handler $updateHandler,
        private Delete{ChildEntity}Handler $deleteHandler,
    ) {}

    /**
     * @param list<{ChildEntity}Item> $items
     */
    public function sync(int $entityId, array $items): void
    {
        // create / update / delete linked rows
    }
}
```

SyncerService координирует сценарий.
Состояние сущностей меняется через Handler'ы и методы самих Entity.

---

## Технический Service

Используется для небольшой технической логики модуля: хеширование, генерация токена, расчет значения.

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Service;

final readonly class {Name}HasherService
{
    public function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
```

---

## ExternalApiService

Используется как вспомогательный сервис модуля для работы с внешними API, клиентами и интеграциями.

Если в модуле нет внешнего API или отдельной интеграционной логики, такой Service не нужен.

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Service;

final readonly class {Entity}ExternalApiService
{
    /**
     * @return array{path: string, originalName: string}
     */
    public function upload(int $entityId, string $tmpFilePath, string $originalName): array
    {
        $path = sprintf('{entity}/%d/%s', $entityId, basename($tmpFilePath));

        return [
            'path' => $path,
            'originalName' => $originalName,
        ];
    }

    public function delete(string $path): void
    {
        // delete file by module path
    }
}
```

Enum-директории описаны отдельно в [Enum](enum.md).

---

## Использование в Handler

```php
public function __construct(
    private {Entity}ValidatorService $validator,
    private {Entity}StructureSyncerService $structureSyncer,
) {}

public function handle(Update{Entity}Command $command): void
{
    $this->validator->validate($command->items);

    // update main entity

    $this->structureSyncer->sync($command->id, $command->items);
}
```
