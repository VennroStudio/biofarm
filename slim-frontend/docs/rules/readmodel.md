# ReadModel

ReadModel — DTO для чтения из БД. Fetcher возвращает ReadModel, Action/Unifier превращают его в HTTP-ответ.

**Расположение:**
- `src/Modules/{Module}/ReadModel/{Entity}/Interface/{Entity}ModelInterface.php`
- `src/Modules/{Module}/ReadModel/{Entity}/{Entity}Details.php`
- `src/Modules/{Module}/ReadModel/{Entity}/{Entity}IdName.php`
- `src/Modules/{Module}/ReadModel/{Entity}/{Entity}Summary.php`

---

## Имена

ReadModel называется по форме данных, а не по Fetcher'у.

- `{Entity}Details` — полная форма текущего API-ответа
- `{Entity}IdName` — `id`, `name`
- `{Entity}Preview` — короткая карточка
- `{Entity}Summary` — компактная вложенная форма
- `{Entity}ListItem` — строка списка
- `{Entity}For{Context}` — только если форма уникальна для одного контекста

Не создавать разные ReadModel с одинаковыми полями только из-за разных query-сценариев.

---

## Интерфейс

Интерфейс сущности наследует общий контракт `ReadModelInterface`.
Дополнительные getter'ы добавляются только если ReadModel используется во внутренней логике.

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\ReadModel\{Entity}\Interface;

use App\Components\ReadModel\ReadModelInterface;

interface {Entity}ModelInterface extends ReadModelInterface
{
    public function getParentId(): int;
}
```

Если дополнительных getter'ов нет, интерфейс остается пустым.

---

## Класс

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Module}\ReadModel\{Entity};

use App\Components\ReadModel\FromRowsTrait;
use App\Modules\{Module}\ReadModel\{Entity}\Interface\{Entity}ModelInterface;
use Override;

final readonly class {Entity}Details implements {Entity}ModelInterface
{
    use FromRowsTrait;

    public function __construct(
        public int $id,
        public string $name,
        public string $description,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function fields(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'description' => 'description',
        ];
    }

    /**
     * @param array{
     *     id: int,
     *     name: string,
     *     description: string
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            description: (string) $row['description'],
        );
    }

    #[Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
```

---

## fields()

`fields()` описывает `SELECT` для `ReadModelFields`.
Ключ — alias результата, значение — колонка или выражение.

```php
public static function fields(): array
{
    return [
        'id' => 'id',
        'entity_id' => 'entity_id',
        'related_name' => 'r.name',
    ];
}
```

Если Fetcher передает alias основной таблицы, простые колонки получат его автоматически.
Поля с `.` или выражениями не меняются.

---

## fromRow()

`fromRow()` приводит типы и маппит строку БД в объект.
Для `$row` всегда указывается PHPDoc array shape.

```php
/**
 * @param array{
 *     id: int,
 *     type: int,
 *     is_active: int|string|bool
 * } $row
 */
public static function fromRow(array $row): self
{
    return new self(
        id: (int) $row['id'],
        type: {Entity}Type::from((int) $row['type']),
        isActive: (bool) (int) $row['is_active'],
    );
}
```

---

## toArray()

`toArray()` готовит API-ответ.

- ключи ответа — `snake_case`
- enum — объект `{ id, label }`
- технические поля для связи можно оставить свойствами/getter'ами, но не отдавать в `toArray()`

```php
public function toArray(): array
{
    return [
        'id' => $this->id,
        'pricing_type' => [
            'id' => $this->pricingType->value,
            'label' => $this->pricingType->getLabel(),
        ],
        'is_cut' => $this->isCut,
    ];
}
```

---

## Компоненты

- `ReadModelInterface` — общий контракт: `fields()`, `fromRow()`, `fromRows()`, `getId()`, `toArray()`
- `FromRowsTrait` — стандартная реализация `fromRows()`
- `ReadModelFields` — формирует `SELECT column AS alias`
- `ReadModelArray` — превращает список ReadModel в список массивов в Action
- `ModelCountItemsResult` — `items + count` для пагинации

`*Item` с `Assert` или `fromRequest()` — это input DTO, а не ReadModel для чтения. Для новых read DTO не использовать `Item` как имя формы.
