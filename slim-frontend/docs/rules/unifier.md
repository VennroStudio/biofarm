# Unifier

Unifier собирает данные Twig-страницы.

**Расположение:**

```
src/Http/Unifier/{Page}/{Page}Unifier.php
```

---

## Пример

```php
final readonly class {Page}Unifier
{
    public function __construct(
        private {Entity}Api $entityApi,
        private {OtherEntity}Api $otherEntityApi,
    ) {}

    /**
     * @return array{
     *     entities: list<{Entity}Response>,
     *     featuredEntity: {Entity}Response|null,
     *     otherItems: list<{OtherEntity}Response>,
     *     apiError: string|null
     * }
     */
    public function unify(): array
    {
        try {
            $entities = $this->entityApi->getEntities();
            $otherItems = $this->otherEntityApi->getOtherEntities();
        } catch (ApiException $exception) {
            return [
                'entities' => [],
                'featuredEntity' => null,
                'otherItems' => [],
                'apiError' => $exception->getMessage(),
            ];
        }

        return [
            'entities' => $entities,
            'featuredEntity' => $entities[0] ?? null,
            'otherItems' => $otherItems,
            'apiError' => null,
        ];
    }
}
```

---

## Правила

- Unifier вызывает module API.
- Unifier собирает page array.
- Page array описывается PHPDoc array shape.
- Unifier не читает HTTP request.
- Unifier не рендерит Twig.
- Unifier не возвращает Response.
