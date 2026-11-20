# Unifier

Unifier собирает view object для Twig-страницы.

**Расположение:**

```
src/Http/Unifier/{Page}/{Page}Unifier.php
src/Http/View/{Page}/{Page}View.php
```

Общие view objects: `src/Http/View/{Name}View.php`.

---

## Пример

```php
final readonly class {Page}View
{
    /**
     * @param list<{Entity}View> $entities
     * @param list<{OtherEntity}View> $otherItems
     */
    public function __construct(
        public array $entities,
        public ?{Entity}View $featuredEntity,
        public array $otherItems,
    ) {}
}

final readonly class {Page}Unifier
{
    public function __construct(
        private {Entity}Repository $entityRepository,
        private {OtherEntity}Repository $otherEntityRepository,
    ) {}

    public function unify(): {Page}View
    {
        $entities = array_map(
            {Entity}View::fromModel(...),
            $this->entityRepository->findAll(),
        );
        $otherItems = array_map(
            {OtherEntity}View::fromModel(...),
            $this->otherEntityRepository->findAll(),
        );

        return new {Page}View(
            entities: $entities,
            featuredEntity: $entities[0] ?? null,
            otherItems: $otherItems,
        );
    }
}
```

---

## Правила

- Unifier вызывает repository/service или Handler, если нужна сценарная обработка данных.
- Unifier возвращает `{Page}View`.
- View object описывает Twig-контракт публичными readonly-полями.
- View object именуется по странице или смыслу: `{Page}View`, `{Page}CategoryView`, `PageMetaView`, `MetricView`.
- Unifier не читает HTTP request.
- Unifier не рендерит Twig.
- Unifier не возвращает Response.
