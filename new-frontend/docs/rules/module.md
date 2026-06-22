# Module / API / Response

Module описывает внешнюю сущность API.

**Расположение:**

```
src/Modules/{Entity}/
├── Api/
│   ├── {Entity}Api.php
│   └── Response/
│       ├── {Entity}Response.php
│       └── {Scenario}Response.php
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

## API

`{Entity}Api` вызывает внешний API через `ApiClient` и возвращает только Response-модели.

```php
final readonly class {Entity}Api
{
    public function __construct(
        private ApiClient $apiClient,
    ) {}
}
```

### Список

```php
/**
 * @return list<{Entity}Response>
 */
public function getEntities(int $page = 1, int $limit = 10): array
{
    $payload = $this->apiClient->get('/entities', [
        'page' => $page,
        'limit' => $limit,
    ]);

    /** @var list<array{id?: int, name?: string}> $items */
    $items = ApiPayload::extractDataList($payload);

    return ApiResponse::fromArrayList($items, {Entity}Response::fromArray(...));
}
```

### Один объект

```php
public function getEntity(int $id): {Entity}Response
{
    /** @var array{id?: int, name?: string} $item */
    $item = ApiPayload::extractData(
        $this->apiClient->get('/entities/' . $id)
    );

    return {Entity}Response::fromArray($item);
}
```

### Запись

```php
public function createEntity(string $name): {Entity}Response
{
    /** @var array{id?: int, name?: string} $item */
    $item = ApiPayload::extractData(
        $this->apiClient->post('/entities/create', [
            'name' => $name,
        ])
    );

    return {Entity}Response::fromArray($item);
}
```

---

## Response

Response описывает объект из внешнего API.

```php
final readonly class {Entity}Response
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
    ) {}

    /**
     * @param array{
     *     id?: int,
     *     name?: string,
     *     description?: string|null
     * } $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: ApiPayload::requireInt($item, 'id'),
            name: ApiPayload::requireString($item, 'name'),
            description: ApiPayload::optionalString($item, 'description'),
        );
    }
}
```

---

## Правила

- `ApiClient` знает HTTP.
- `{Entity}Api` знает endpoints своей сущности.
- `Response` знает форму ответа своей сущности.
- `ApiPayload` достает `data`.
- `ApiResponse::fromArrayList()` собирает списки.
- Required поля ответа читать через строгие helper-методы `ApiPayload::require*()`.
- Defaults использовать только для реально optional полей.
- Сырой JSON наружу не отдавать.
