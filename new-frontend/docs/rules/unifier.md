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
     * @param list<{Entity}Response> $entities
     * @param list<{OtherEntity}Response> $otherItems
     */
    public function __construct(
        public array $entities,
        public ?{Entity}Response $featuredEntity,
        public array $otherItems,
        public ?string $apiError,
    ) {}
}

final readonly class {Page}Unifier
{
    public function __construct(
        private {Entity}Api $entityApi,
        private {OtherEntity}Api $otherEntityApi,
    ) {}

    public function unify(): {Page}View
    {
        try {
            $entities = $this->entityApi->getEntities();
            $otherItems = $this->otherEntityApi->getOtherEntities();
        } catch (ApiException $exception) {
            return new {Page}View(
                entities: [],
                featuredEntity: null,
                otherItems: [],
                apiError: $exception->getMessage(),
            );
        }

        return new {Page}View(
            entities: $entities,
            featuredEntity: $entities[0] ?? null,
            otherItems: $otherItems,
            apiError: null,
        );
    }
}
```

---

## Правила

- Unifier вызывает module API.
- Unifier возвращает `{Page}View`.
- View object описывает Twig-контракт публичными readonly-полями.
- View object именуется по странице или смыслу: `{Page}View`, `{Page}CategoryView`, `PageMetaView`, `MetricView`.
- Unifier не читает HTTP request.
- Unifier не рендерит Twig.
- Unifier не возвращает Response.
