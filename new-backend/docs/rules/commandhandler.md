# Command / Handler

**Расположение:** `src/Modules/{Module}/Command/{Entity}/{Action}/`
- `{Action}{Entity}Command.php`
- `{Action}{Entity}Handler.php`

---

## Состав Command / Handler

Command / Handler собирается только из тех блоков, которые нужны конкретному write-сценарию.

- Command
- Handler
- Проверка прав
- Работа с Entity через Repository
- Вызов Service / Syncer
- Вызов Handler'ов связанных сущностей
- Инвалидация query-кеша через `Cacher`
- `flush()`
- Возврат результата

---

## Command

### HTTP-команда

Используется для данных из Action. Валидация описывается через `Assert`.

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Command\{Entity}\Update;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Update{Entity}Command
{
    private const int NAME_MIN_LENGTH = 2;
    private const int NAME_MAX_LENGTH = 120;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,

        #[Assert\NotBlank]
        public int $currentUserRole,

        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $id,

        #[Assert\NotBlank(message: 'validation.name_required')]
        #[Assert\Length(
            min: self::NAME_MIN_LENGTH,
            max: self::NAME_MAX_LENGTH,
            minMessage: 'validation.name_too_short',
            maxMessage: 'validation.name_too_long',
        )]
        public string $name,

        public ?string $description = null,
    ) {}
}
```

### Внутренняя команда

Используется при вызове одного Handler'а из другого Handler'а или Service. `Assert` не нужен.

```php
final readonly class Create{ChildEntity}Command
{
    public function __construct(
        public int $entityId,
        public string $name,
        public int $sort,
    ) {}
}
```

---

## Handler

### Заголовок

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Command\{Entity}\Update;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Modules\{Module}\Entity\{Entity}\{Entity}Repository;
use App\Modules\{Module}\Permission\{Module}Permission;
use App\Modules\{Module}\Service\{Module}PermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;

final readonly class Update{Entity}Handler
{
}
```

### Зависимости

```php
public function __construct(
    private {Entity}Repository $repository,
    private {Module}PermissionService $permissionService,
    private Cacher $cacher,
    private FlusherInterface $flusher,
) {}
```

Правила `PermissionService` описаны отдельно в [Permission](permission.md). В Handler показывается только использование.

Правила query-кеша описаны отдельно в [Cache](cache.md). В Handler показывается только использование `Cacher`.
Метод удаления зависит от хранения кеша: прямой key удаляется через `delete()`, tagged cache - через `deleteTag()`.

Если проверка только по роли, ее можно делать до загрузки Entity. Если проверка зависит от владельца или состояния Entity, сначала загружается Entity, потом выполняется проверка прав.

### handle()

```php
public function handle(Update{Entity}Command $command): void
{
    $this->permissionService->checkRole(
        currentUserRole: UserRole::from($command->currentUserRole),
        action: {Module}Permission::UPDATE,
    );

    $entity = $this->repository->getById($command->id);

    $entity->edit(
        name: $command->name,
        description: $command->description,
    );

    $this->cacher->delete('{entity}_by_id_' . $command->id);

    $this->flusher->flush();
}
```

---

## Create Handler

```php
final readonly class Create{Entity}Handler
{
    public function __construct(
        private {Entity}Repository $repository,
        private {Module}PermissionService $permissionService,
        private FlusherInterface $flusher,
    ) {}

    public function handle(Create{Entity}Command $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: {Module}Permission::CREATE,
        );

        $entity = {Entity}::create(
            name: $command->name,
            description: $command->description,
        );

        $this->repository->add($entity);
        $this->flusher->flush();
    }
}
```

Если после `create()` нужен ID для связанных сущностей, сначала выполняется `flush()`, потом вызывается Syncer / Handler связанных сущностей.

Если создание затрагивает закешированный список или связанный query-cache контекст, инвалидация выполняется после получения ID.

В сложной структуре допустимо несколько `flush()`: сначала для получения ID основной сущности, затем после создания связанных сущностей, затем после зависимых связей второго уровня.

---

## Update Handler

```php
final readonly class Update{Entity}Handler
{
    public function __construct(
        private {Entity}Repository $repository,
        private {Module}PermissionService $permissionService,
        private {Entity}StructureSyncerService $structureSyncer,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    public function handle(Update{Entity}Command $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: {Module}Permission::UPDATE,
        );

        $entity = $this->repository->getById($command->id);

        $entity->edit(
            name: $command->name,
            description: $command->description,
        );

        $this->structureSyncer->sync($command->id, $command->children);

        $this->cacher->delete('{entity}_by_id_' . $command->id);

        $this->flusher->flush();
    }
}
```

Правила Service / Syncer описаны отдельно в [Service](service.md). В Handler показывается только использование.

---

## Delete Handler

```php
final readonly class Delete{Entity}Handler
{
    public function __construct(
        private {Entity}Repository $repository,
        private Delete{ChildEntity}Handler $deleteChildEntityHandler,
        private {ChildEntity}Repository $childRepository,
        private {Module}PermissionService $permissionService,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    public function handle(Delete{Entity}Command $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: {Module}Permission::DELETE,
        );

        $entity = $this->repository->getById($command->id);

        foreach ($this->childRepository->findByEntityId($command->id) as $child) {
            $this->deleteChildEntityHandler->handle(
                new Delete{ChildEntity}Command(id: (int) $child->id)
            );
        }

        $this->repository->remove($entity);
        $this->cacher->delete('{entity}_by_id_' . $command->id);

        $this->flusher->flush();
    }
}
```

---

## Handler связанных сущностей

Если связанная таблица является частью сценария основной сущности, у нее все равно должны быть свои Entity / Repository / Command / Handler.

Пример: `{Entity}` и `{Entity}Image`, `{Entity}` и `{Entity}Option`, `{Entity}` и `{Entity}Price`.

Внутренние Handler'ы связанных сущностей могут быть без `PermissionService` и без `FlusherInterface`, если они вызываются из родительского Handler'а или SyncerService. В этом случае `flush()` выполняет верхний сценарий.

Каждый Handler сам удаляет cache tags своей Entity. Родительский Handler не чистит кеш за связанные Handler'ы.

```php
final readonly class Create{ChildEntity}Handler
{
    public function __construct(
        private {ChildEntity}Repository $repository,
        private Cacher $cacher,
    ) {}

    public function handle(Create{ChildEntity}Command $command): {ChildEntity}
    {
        $child = {ChildEntity}::create(
            entityId: $command->entityId,
            name: $command->name,
        );

        $this->repository->add($child);
        $this->cacher->delete('{child_entity}_by_entity_id_' . $command->entityId);

        return $child;
    }
}
```

```php
final readonly class Create{Entity}Handler
{
    public function __construct(
        private {Entity}Repository $repository,
        private {Entity}StructureSyncerService $structureSyncer,
        private FlusherInterface $flusher,
    ) {}

    public function handle(Create{Entity}Command $command): void
    {
        $entity = {Entity}::create(name: $command->name);

        $this->repository->add($entity);
        $this->flusher->flush();

        $this->structureSyncer->sync((int) $entity->id, $command->children);
        $this->flusher->flush();
    }
}
```

Handler основной сущности не должен вручную менять состояние связанной Entity, если для этого есть отдельный Handler.

---

## Handler без записи

Handler может читать данные, выполнять проверки, вызывать другие Handler'ы и возвращать DTO.

Такой Handler может быть без Repository и без Flusher, если сам не меняет состояние Entity.

```php
final readonly class LoginHandler
{
    public function __construct(
        private {Entity}FindByEmailFetcher $fetcher,
        private Create{Entity}TokenHandler $createTokenHandler,
        private PasswordHasherService $passwordHasher,
    ) {}

    public function handle(LoginCommand $command): TokenPairResult
    {
        $entity = $this->fetcher->fetch(new {Entity}FindByEmailQuery($command->email));

        if (!$this->passwordHasher->verify($command->password, $entity->passwordHash)) {
            throw new DomainExceptionModule(
                module: '{module}',
                message: 'error.invalid_credentials',
                code: 1
            );
        }

        return $this->createTokenHandler->handle(
            new Create{Entity}TokenCommand(entityId: $entity->id)
        );
    }
}
```

Mailer / external Handler тоже может быть без Repository и без Flusher.

```php
final readonly class Send{Entity}NotificationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
    ) {}

    public function handle(Send{Entity}NotificationCommand $command): void
    {
        $email = new Email()
            ->to($command->email)
            ->html($this->twig->render('{template}.html.twig', [
                'name' => $command->name,
            ]));

        $this->mailer->send($email);
    }
}
```

---

## Возврат результата

По умолчанию Handler возвращает `void`.

DTO, значение или созданная Entity возвращается только если результат нужен Action, другому Handler'у или SyncerService.

```php
public function handle(Upload{Entity}ImageCommand $command): string
{
    // ...

    return $imageUrl;
}
```

```php
public function handle(LoginCommand $command): TokenPairResult
{
    // ...

    return new TokenPairResult(
        accessToken: $accessToken,
        refreshToken: $refreshToken,
    );
}
```

```php
public function handle(Create{ChildEntity}Command $command): {ChildEntity}
{
    // ...

    return $child;
}
```
