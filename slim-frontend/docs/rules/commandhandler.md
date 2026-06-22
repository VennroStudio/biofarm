# Command / Handler

Handler содержит сценарную бизнес-логику.
Command нужен, когда входные данные сценария должны быть явной моделью.

**Расположение с Command:**

```
src/Modules/{Entity}/Command/{Scenario}/
├── {Scenario}Command.php
└── {Scenario}Handler.php
```

**Расположение без Command:**

```
src/Modules/{Entity}/Handler/{Scenario}/
└── {Scenario}Handler.php
```

---

## Когда нужен

- create;
- update;
- delete;
- submit;
- login/logout через внешний auth API;
- подготовка данных перед сохранением;
- обработка данных перед выводом на странице, если это бизнес-сценарий, а не простая сборка page array.

---

## Command

Command содержит входные данные сценария.

```php
final readonly class Create{Entity}Command
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
```

Для частичного update поля nullable.

```php
final readonly class Update{Entity}Command
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $description = null,
    ) {}
}
```

---

## Handler

Handler может:

- нормализовать входные данные;
- рассчитать производные значения;
- отфильтровать или сгруппировать данные;
- собрать payload для внешнего API;
- вызвать один или несколько методов `{Entity}Api`;
- вернуть Response-модель или подготовленный результат сценария.

```php
final readonly class Create{Entity}Handler
{
    public function __construct(
        private {Entity}Api $api,
    ) {}

    public function handle(Create{Entity}Command $command): {Entity}Response
    {
        $name = trim($command->name);

        return $this->api->createEntity(
            name: $name,
            description: $command->description,
        );
    }
}
```

---

## Перед выводом на странице

Если нужно только собрать данные для Twig — это делает Unifier.

Если перед выводом нужна сценарная обработка данных, Unifier вызывает Handler.

```php
final readonly class Prepare{Entity}PreviewHandler
{
    public function __construct(
        private {Entity}Api $api,
    ) {}

    /**
     * @return list<{Entity}Response>
     */
    public function handle(int $limit): array
    {
        return array_slice(
            $this->api->getEntities(),
            0,
            $limit,
        );
    }
}
```

---

## Delete

```php
final readonly class Delete{Entity}Handler
{
    public function __construct(
        private {Entity}Api $api,
    ) {}

    public function handle(Delete{Entity}Command $command): Delete{Entity}Response
    {
        return $this->api->deleteEntity($command->id);
    }
}
```

---

## Правила

- Command не читает HTTP request.
- Handler не рендерит Twig.
- Handler не собирает page array.
- HTTP-запрос к внешнему API выполняется через `{Entity}Api`.
- Unifier может вызвать Handler, если странице нужна сценарная обработка данных.
