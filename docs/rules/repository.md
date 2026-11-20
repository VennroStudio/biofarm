# Repository — Репозиторий

Только для **write-операций**. Чтение для API-ответов выполняется через Query / Fetcher.

**Расположение:**
- Интерфейс: `src/Modules/{Module}/Entity/{Entity}/{Entity}Repository.php`
- Реализация: `src/Modules/{Module}/Entity/{Entity}/Persistence/Doctrine/Doctrine{Entity}Repository.php`
- Регистрация: `config/common/repositories.php`

---

## Состав Repository

Repository собирается только из методов, которые нужны конкретному write-сценарию.

- Интерфейс
- `add()`
- `remove()`
- `getById()`
- `findById()`
- Дополнительные `find...()` методы для write-сценариев
- Doctrine-реализация
- Регистрация в DI

---

## Интерфейс

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Entity\{Entity};

use App\Modules\{Module}\Entity\{Entity}\Fields\Enums\{Entity}Type;

interface {Entity}Repository
{
    public function add({Entity} ${entity}): void;

    public function remove({Entity} ${entity}): void;

    public function getById(int $id): {Entity};

    public function findById(int $id): ?{Entity};

    /**
     * @return list<{Entity}>
     */
    public function findByOwnerId(int $ownerId): array;

    /**
     * @return list<{Entity}>
     */
    public function findByOwnerIdAndType(int $ownerId, {Entity}Type $type): array;
}
```

---

## add()

```php
#[Override]
public function add({Entity} ${entity}): void
{
    $this->em->persist(${entity});
}
```

---

## remove()

```php
#[Override]
public function remove({Entity} ${entity}): void
{
    $this->em->remove(${entity});
}
```

---

## getById()

```php
#[Override]
public function getById(int $id): {Entity}
{
    if (!${entity} = $this->findById($id)) {
        throw new DomainExceptionModule(
            module: '{module}',
            message: 'error.{entity}_not_found',
            code: 1
        );
    }

    return ${entity};
}
```

---

## findById()

```php
#[Override]
public function findById(int $id): ?{Entity}
{
    return $this->repo->findOneBy(['id' => $id]);
}
```

---

## Дополнительные find-методы

Используются только для write-сценариев: синхронизация дочерних сущностей, проверка уникальности, массовое удаление связанных записей.

```php
/**
 * @return list<{Entity}>
 */
#[Override]
public function findByOwnerId(int $ownerId): array
{
    return $this->repo->findBy(['ownerId' => $ownerId]);
}
```

```php
/**
 * @return list<{Entity}>
 */
#[Override]
public function findByOwnerIdAndType(int $ownerId, {Entity}Type $type): array
{
    return $this->repo->findBy([
        'ownerId' => $ownerId,
        'type'    => $type,
    ]);
}
```

```php
#[Override]
public function findByEmail(string $email): ?{Entity}
{
    return $this->repo->findOneBy(['email' => $email]);
}
```

---

## Doctrine-реализация

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Entity\{Entity}\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\{Module}\Entity\{Entity}\Fields\Enums\{Entity}Type;
use App\Modules\{Module}\Entity\{Entity}\{Entity};
use App\Modules\{Module}\Entity\{Entity}\{Entity}Repository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class Doctrine{Entity}Repository implements {Entity}Repository
{
    /** @var EntityRepository<{Entity}> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $em->getRepository({Entity}::class);
    }

    #[Override]
    public function add({Entity} ${entity}): void
    {
        $this->em->persist(${entity});
    }

    #[Override]
    public function remove({Entity} ${entity}): void
    {
        $this->em->remove(${entity});
    }

    #[Override]
    public function getById(int $id): {Entity}
    {
        if (!${entity} = $this->findById($id)) {
            throw new DomainExceptionModule(
                module: '{module}',
                message: 'error.{entity}_not_found',
                code: 1
            );
        }

        return ${entity};
    }

    #[Override]
    public function findById(int $id): ?{Entity}
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    /**
     * @return list<{Entity}>
     */
    #[Override]
    public function findByOwnerId(int $ownerId): array
    {
        return $this->repo->findBy(['ownerId' => $ownerId]);
    }

    /**
     * @return list<{Entity}>
     */
    #[Override]
    public function findByOwnerIdAndType(int $ownerId, {Entity}Type $type): array
    {
        return $this->repo->findBy([
            'ownerId' => $ownerId,
            'type'    => $type,
        ]);
    }
}
```

---

## Регистрация

```php
use App\Modules\{Module}\Entity\{Entity}\{Entity}Repository;
use App\Modules\{Module}\Entity\{Entity}\Persistence\Doctrine\Doctrine{Entity}Repository;

use function DI\get;

return [
    {Entity}Repository::class => get(Doctrine{Entity}Repository::class),
];
```
