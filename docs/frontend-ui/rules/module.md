# Data Module / Repository / Model

Data module описывает сущность сайта внутри бэкенда.

**Расположение:**

```
src/Modules/{Entity}/
├── Model/
│   └── {Entity}.php
├── Repository/
│   └── {Entity}Repository.php
├── Handler/
│   └── {Scenario}/
│       └── {Scenario}Handler.php
└── Command/
    └── {Scenario}/
        ├── {Scenario}Command.php
        └── {Scenario}Handler.php
```

`Command` добавляется только для write-сценариев.
`Handler` без Command используется для сценарной обработки данных без отдельного Command-класса.

---

## Model

Model описывает данные, с которыми работает бэкенд.

```php
final readonly class {Entity}
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
    ) {}
}
```

---

## Repository

Repository читает и сохраняет сущность. Пока база не перенесена, он может возвращать пустые коллекции или временные данные.

```php
final readonly class {Entity}Repository
{
    /**
     * @return list<{Entity}>
     */
    public function findAll(): array
    {
        return [];
    }

    public function findById(int $id): ?{Entity}
    {
        unset($id);

        return null;
    }
}
```

---

## Правила

- Repository знает способ получения данных.
- Model знает форму сущности внутри бэкенда.
- Unifier превращает модели в view object для Twig.
- Handler вызывает repository/service для write-сценариев.
- Сырой массив наружу не отдавать, если есть стабильная модель или view object.
- Defaults использовать только для реально optional полей.
