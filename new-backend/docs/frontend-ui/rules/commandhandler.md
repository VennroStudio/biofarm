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
- подготовка данных перед сохранением;
- обработка данных перед выводом на странице, если это бизнес-сценарий, а не простая сборка view object.

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
- подготовить данные перед сохранением;
- вызвать один или несколько методов repository/service;
- вернуть модель или подготовленный результат сценария.

```php
final readonly class Create{Entity}Handler
{
    public function __construct(
        private {Entity}Repository $repository,
    ) {}

    public function handle(Create{Entity}Command $command): {Entity}
    {
        $entity = new {Entity}(
            id: 0,
            name: trim($command->name),
            description: $command->description,
        );

        return $this->repository->save($entity);
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
        private {Entity}Repository $repository,
    ) {}

    /**
     * @return list<{Entity}>
     */
    public function handle(int $limit): array
    {
        return array_slice(
            $this->repository->findAll(),
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
        private {Entity}Repository $repository,
    ) {}

    public function handle(Delete{Entity}Command $command): void
    {
        $this->repository->delete($command->id);
    }
}
```

---

## Правила

- Command не читает HTTP request.
- Handler не рендерит Twig.
- Handler не собирает view object страницы.
- Handler использует repository/service внутри бэкенда.
- Unifier может вызвать Handler, если странице нужна сценарная обработка данных.
