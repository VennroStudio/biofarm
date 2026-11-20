# Unifier — сборка HTTP-ответа

Unifier преобразует ReadModel в массив ответа API и подмешивает данные, которые не должны собираться внутри Action.

**Расположение:**
- Unifier: `src/Http/Unifier/{Module}/{Entity}Unifier.php`
- Интерфейс: `src/Components/Http/Unifier/UnifierInterface.php`
- Helper: `src/Components/Http/Unifier/UnifierHelper.php`

---

## Состав Unifier

Unifier собирается только из тех блоков, которые нужны конкретному ответу.

- `final readonly class`
- `UnifierInterface`
- зависимости через конструктор
- `unifyOne()`
- `unify()`
- `map()`
- вызовы Unifier'ов связанных сущностей
- приватные mapper'ы вложенных частей
- приватные методы преобразования и расчета

Unifier используется для ответов сущностей. Для `select`, `enum`, `permission map` и `success response` он не нужен.

Для каждой сущности модуля, которая отдается во вложенном HTTP-ответе, создается свой Unifier.

Форма ответа описывается ReadModel/DTO. Unifier не должен собирать произвольный массив руками и не должен принимать список полей, которые нужно удалить.

Если для вложенного ответа нужна другая форма, создается отдельный ReadModel/DTO под эту форму.
Если форма переиспользуемая, ReadModel называется по набору данных: `{Entity}IdName`, `{Entity}Details`, `{Entity}Preview`, `{Entity}Summary`, `{Entity}ListItem`.
`{Entity}For{Context}` используется только для действительно уникального контекста.

Технические поля для маппинга (`orderId`, `materialId`, `entityId`) могут оставаться свойствами или getter'ами ReadModel/DTO. Но если они не нужны frontend, они не включаются в `toArray()`.

---

## Общий паттерн

Поток всегда один:

1. `Action` вызывает `unifyOne()` или `unify()`
2. `unifyOne()` приводит один объект к общему потоку через `unify()`
3. `unify()` только проходит по списку и вызывает `map()`
4. `map()` собирает HTTP-ответ для одного ReadModel
5. связанные сущности передаются в свои Unifier'ы
6. приватные методы считают значения и собирают точечные вложенные части

Простой Unifier отличается только тем, что `unify()` сразу вызывает `map()`, а `map()` возвращает `toArray()`.

Сложный Unifier отличается только тем, что `map()` получает связанные ReadModel через Fetcher и передает их в соответствующие Unifier'ы.

---

## Скелет

```php
<?php

declare(strict_types=1);

namespace App\Http\Unifier\{Module};

use App\Components\Http\Unifier\UnifierHelper;
use App\Components\Http\Unifier\UnifierInterface;
use App\Modules\{Module}\ReadModel\{Entity}\Interface\{Entity}ModelInterface;
use Override;

final readonly class {Entity}Unifier implements UnifierInterface
{
    #[Override]
    public function unifyOne(?int $userId, ?object $item): array
    {
        if (!$item instanceof {Entity}ModelInterface) {
            return [];
        }

        return $this->unify($userId, [$item])[0] ?? [];
    }

    /**
     * @param list<{Entity}ModelInterface> $items
     * @return list<array<string, mixed>>
     */
    #[Override]
    public function unify(?int $userId, array $items): array
    {
        if ($items === []) {
            return [];
        }

        return array_map($this->map(...), $items);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function map(object $item): array
    {
        /** @var {Entity}ModelInterface $item */
        $data = $item->toArray();

        return UnifierHelper::withTimestamps($data, $item);
    }
}
```

Если у ответа нет дат, `UnifierHelper::withTimestamps()` не нужен.

---

## Связанные данные

Если ответу нужны связанные данные, они получаются в `map()` и передаются в Unifier соответствующей сущности.

Fetcher должен возвращать ReadModel/DTO в нужной форме ответа. Если нужен короткий вложенный объект, это должен быть отдельный ReadModel/DTO, а не ручное удаление полей в Unifier.

Если Fetcher поддерживает разные ReadModel для одного сценария чтения, Unifier передает нужный DTO вторым аргументом.

Если поле нужно только для группировки или связи, Unifier обращается к свойству/getter'у, а не к ключу массива из `toArray()`.

```php
#[Override]
public function map(object $item): array
{
    /** @var {Entity}ModelInterface $item */
    $data = $item->toArray();
    $entityId = $item->getId();

    $items = $this->itemFetcher->fetch(
        new {Entity}ItemFindBy{Entity}IdQuery($entityId),
        {Entity}ItemSummary::class,
    );

    $data['items'] = $this->itemUnifier->unify(null, $items);

    return UnifierHelper::withTimestamps($data, $item);
}
```

---

## Unifier вложенной сущности

Вложенная сущность сама отвечает за свой формат ответа.

```php
#[Override]
public function map(object $item): array
{
    /** @var {Entity}ItemModelInterface $item */
    $data = $item->toArray();
    $children = $this->childFetcher->fetch(
        new {Entity}ChildFindByItemIdQuery($item->getId())
    );

    $data['children'] = $this->childUnifier->unify(null, $children);

    return $data;
}
```

Так родительский Unifier не знает детали сборки вложенной сущности.

---

## Приватные mapper'ы

Приватные mapper'ы используются только внутри Unifier своей сущности, если для вложенной части нет отдельной сущности/Unifier или нужна маленькая локальная трансформация.

```php
/**
 * @param list<{Entity}ItemBy{Entity}Id> $items
 * @return list<array<string, mixed>>
 */
private function mapItems(array $items): array
{
    return array_map(function ({Entity}ItemBy{Entity}Id $item): array {
        $data = $item->toArray();
        $children = $this->childFetcher->fetch(
            new {Entity}ChildFindByItemIdQuery($item->id)
        );

        $data['children'] = $this->childUnifier->unify(null, $children);

        return $data;
    }, $items);
}
```

Такой mapper вызывается только из `map()` этого же Unifier:

```php
$data['items'] = $this->mapItems($items);
```

---

## Преобразование полей

Unifier может менять поля только для HTTP-ответа: например, заменить путь файла на публичный URL.

```php
$data = UnifierHelper::transformField(
    $data,
    'path',
    $this->s3Transformer->buildUrl(...),
);
```

---

## Helper

`UnifierHelper` используется для маленьких повторяющихся преобразований.

```php
UnifierHelper::withTimestamps($data, $item);
UnifierHelper::transformField($data, 'path', $this->s3Transformer->buildUrl(...));
```

`UnifierHelper::toArrayWithout()` допустим только для legacy-кода. Для нового Unifier, если нужно убрать поле из ответа, создается отдельный ReadModel/DTO с правильным `toArray()`.

---

## Использование в Action

Action только вызывает Unifier и не знает, как собирается вложенная структура.

```php
return new JsonDataResponse(
    $this->unifier->unifyOne(null, $item)
);
```

```php
return new JsonDataItemsResponse(
    count: $result->count,
    items: $this->unifier->unify(null, $result->items),
);
```
