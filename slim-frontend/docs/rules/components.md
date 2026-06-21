# Components — Инфраструктурные компоненты

Инфраструктурные, модуле-независимые утилиты. Не содержат бизнес-логики, не зависят от модулей.

**Расположение:** `src/Components/{ComponentName}/`

---

## Существующие компоненты

| Компонент | Назначение | Ключевые классы |
|-----------|------------|-----------------|
| `Auth` | JWT-токены | `JwtTokenService`, `AccessTokenPayload`, `RefreshTokenPayload` |
| `Cacher` | Кеширование | `Cacher`, `RedisCacher` |
| `Clock` | Время UTC | `UtcClock` — `now()`, `fromString()`, `fromTimestamp()` |
| `Exception` | Кастомные исключения | `DomainExceptionModule`, `AuthenticationException`, `AccessDeniedException`, `NotFoundException` |
| `Flusher` | Сброс в БД | `FlusherInterface` + `DoctrineFlusher` |
| `Frontend` | URL фронтенда | `FrontendUrlGenerator`, `FrontendUrlTwigExtension` |
| `Http` | HTTP-слой | `HttpErrorHandler`, middleware, Response-классы, `Authenticate`, `RequestIdentity`, `UnifierInterface` |
| `ReadModel` | Общие DTO | `ModelCountItemsResult`, `FromRowsTrait` |
| `Router` | Маршрутизация | `Route`, `StaticRouteGroup` |
| `Serializer` | Денормализация | `Denormalizer` — `denormalize()` / `denormalizeStrict()` |
| `Storage` | Файлы | `S3Storage`, `FileUploaderService`, `PhotoFileValidator`, `S3Transformer` |
| `Translation` | Переводы компонентов | `errors.{locale}.php` |
| `Translator` | Переводы в Twig | `TranslatorTwigExtension` — функция `trans()` |
| `Validator` | Валидация | `Validator`, `ValidationException` |

---

## Правила создания компонента

**Создавать когда:**
- Функциональность нужна нескольким модулям
- Это инфраструктура, не бизнес-логика (файлы, email, кеш, внешние API и т.д.)

**Не создавать когда:**
- Логика нужна одному модулю → Service внутри модуля
- Это бизнес-правило → логика в Entity или Handler

**Правила реализации:**
- Классы — `final readonly class`; stateless/static хелперы (`UtcClock`, `RequestIdentity`) — `final class`
- Twig-расширения — `final class extends AbstractExtension` (не `readonly`)
- Если есть абстракция — интерфейс в корне + реализация в `Persistence/{Driver}/`
- Зависимости через конструктор, все импорты через `use`

**Структура с интерфейсом:**
```
src/Components/{Name}/
├── {Name}Interface.php
└── Persistence/{Driver}/
    └── {Driver}{Name}.php
```

---

## Паттерны

**Middleware — обработчик исключений:**

```php
final readonly class {ExceptionType}Handler implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch ({ExceptionType} $exception) {
            $this->logger->warning($exception->getMessage());
            return new Json{Type}Response(...);
        }
    }
}
```

**Response-класс:**

```php
final class Json{Name}Response extends JsonResponse
{
    public function __construct(/* параметры */)
    {
        parent::__construct(['data' => [...]], $status);
    }
}
```

**Переводы компонентов** — при выбросе `DomainExceptionModule` из слоя компонентов указывать `module: 'components'`:

```php
throw new DomainExceptionModule(module: 'components', message: 'error.file_too_large', code: 15);
```