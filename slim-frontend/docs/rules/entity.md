# Entity — Доменная сущность

**Расположение:** `src/Modules/{Module}/Entity/{Entity}/{Entity}.php`

---

## Состав Entity

Entity собирается только из тех блоков, которые нужны конкретной сущности.

- Заголовок класса
- `private(set)` поля
- `private` конструктор
- Статическая фабрика `create()`
- Метод редактирования `edit()`
- Дополнительные доменные методы
- Вспомогательные приватные методы

---

## Заголовок класса

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Entity\{Entity};

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '{table_name}')]
#[ORM\Index(name: 'idx_{table_name}_owner_id', columns: ['owner_id'])]
class {Entity}
{
}
```

---

## Поля

### ID

```php
#[ORM\Id]
#[ORM\Column(type: Types::INTEGER)]
#[ORM\GeneratedValue(strategy: 'AUTO')]
private(set) ?int $id = null;
```

### Простые поля

```php
#[ORM\Column(type: Types::INTEGER)]
private(set) int $ownerId;

#[ORM\Column(type: Types::STRING, length: 255)]
private(set) string $name;

#[ORM\Column(type: Types::TEXT, nullable: true)]
private(set) ?string $description;

#[ORM\Column(type: Types::BOOLEAN)]
private(set) bool $enabled;
```

### Decimal-поля

```php
#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
private(set) string $price;
```

### Enum-поля

Enum-класс описывается отдельно в [Enum](enum.md). В Entity показывается только использование поля.

```php
use App\Modules\{Module}\Entity\{Entity}\Fields\Enums\{Entity}Status;

#[ORM\Column(type: Types::INTEGER, enumType: {Entity}Status::class)]
private(set) {Entity}Status $status;
```

### Даты

```php
use App\Components\Clock\UtcClock;
use DateTimeImmutable;

#[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
private(set) DateTimeImmutable $createdAt;

#[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
private(set) ?DateTimeImmutable $updatedAt = null;

#[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
private(set) ?DateTimeImmutable $deletedAt = null;
```

---

## Конструктор

```php
/**
 * @throws DateMalformedStringException
 */
private function __construct(
    int $ownerId,
    string $name,
    ?string $description,
    {Entity}Status $status,
    string $price,
    bool $enabled,
) {
    $this->ownerId = $ownerId;
    $this->name = $name;
    $this->description = $description;
    $this->status = $status;
    $this->price = $price;
    $this->enabled = $enabled;
    $this->createdAt = UtcClock::now();
}
```

---

## create()

```php
/**
 * @throws DateMalformedStringException
 */
public static function create(
    int $ownerId,
    string $name,
    ?string $description,
    {Entity}Status $status,
    string $price,
    bool $enabled = true,
): self {
    return new self(
        ownerId: $ownerId,
        name: $name,
        description: $description,
        status: $status,
        price: $price,
        enabled: $enabled,
    );
}
```

---

## edit()

```php
/**
 * @throws DateMalformedStringException
 */
public function edit(
    string $name,
    ?string $description,
    string $price,
    bool $enabled,
): void {
    $this->assertNotDeleted();

    $this->name = $name;
    $this->description = $description;
    $this->price = $price;
    $this->enabled = $enabled;
    $this->touch();
}
```

---

## Дополнительные методы

```php
/**
 * @throws DateMalformedStringException
 */
public function changeStatus({Entity}Status $status): void
{
    $this->assertNotDeleted();

    if ($this->status === $status) {
        return;
    }

    $this->status = $status;
    $this->touch();
}

/**
 * @throws DateMalformedStringException
 */
public function markDeleted(): void
{
    $this->assertNotDeleted();

    $this->deletedAt = UtcClock::now();
    $this->touch();
}
```

---

## Вспомогательные методы

```php
/**
 * @throws DateMalformedStringException
 */
private function touch(): void
{
    $this->updatedAt = UtcClock::now();
}

private function assertNotDeleted(): void
{
    if ($this->deletedAt !== null) {
        throw new DomainExceptionModule(
            module: '{module}',
            message: 'error.{entity}_is_deleted',
            code: 1
        );
    }
}
```

---

## Полный пример

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\Entity\{Entity};

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use App\Modules\{Module}\Entity\{Entity}\Fields\Enums\{Entity}Status;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '{table_name}')]
#[ORM\Index(name: 'idx_{table_name}_owner_id', columns: ['owner_id'])]
#[ORM\Index(name: 'idx_{table_name}_status', columns: ['status'])]
#[ORM\Index(name: 'idx_{table_name}_created_at', columns: ['created_at'])]
class {Entity}
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $ownerId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private(set) ?string $description;

    #[ORM\Column(type: Types::INTEGER, enumType: {Entity}Status::class)]
    private(set) {Entity}Status $status;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private(set) string $price;

    #[ORM\Column(type: Types::BOOLEAN)]
    private(set) bool $enabled;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $deletedAt = null;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(
        int $ownerId,
        string $name,
        ?string $description,
        {Entity}Status $status,
        string $price,
        bool $enabled,
    ) {
        $this->ownerId = $ownerId;
        $this->name = $name;
        $this->description = $description;
        $this->status = $status;
        $this->price = $price;
        $this->enabled = $enabled;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        int $ownerId,
        string $name,
        ?string $description,
        {Entity}Status $status,
        string $price,
        bool $enabled = true,
    ): self {
        return new self(
            ownerId: $ownerId,
            name: $name,
            description: $description,
            status: $status,
            price: $price,
            enabled: $enabled,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function edit(
        string $name,
        ?string $description,
        string $price,
        bool $enabled,
    ): void {
        $this->assertNotDeleted();

        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->enabled = $enabled;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function changeStatus({Entity}Status $status): void
    {
        $this->assertNotDeleted();

        if ($this->status === $status) {
            return;
        }

        $this->status = $status;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markDeleted(): void
    {
        $this->assertNotDeleted();

        $this->deletedAt = UtcClock::now();
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    private function touch(): void
    {
        $this->updatedAt = UtcClock::now();
    }

    private function assertNotDeleted(): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainExceptionModule(
                module: '{module}',
                message: 'error.{entity}_is_deleted',
                code: 1
            );
        }
    }
}
```
