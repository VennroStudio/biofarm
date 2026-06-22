# Slim Frontend Agent Rules

Правила для отдельного frontend-приложения на Slim, PHP, Twig и React islands.

Цель шаблона: рендерить SEO-страницы на сервере через PHP/Twig, получать данные из внешнего API, а React использовать только для локальной динамики внутри уже готового HTML.

Этот проект не является backend API. В нем нет БД, миграций, Doctrine, backend `Action`, backend `Query/Fetcher`, backend `ReadModel`, auth-логики и CRUD над своей БД.

---

## Главный принцип

Страница собирается так:

```
HTTP request
    -> Http/Web/{Page}/{Page}Controller
    -> Http/Unifier/{Page}/{Page}Unifier
    -> Modules/{Entity}/Api/{Entity}Api
    -> Components/Api/ApiClient
    -> external API
    -> Modules/{Entity}/Api/Response/{Entity}Response
    -> Twig page/section/component
    -> React island, если нужна динамика
```

`Modules/*` в этом frontend-шаблоне содержат только API-клиенты конкретных внешних сущностей и модели ответа API.

---

## Структура

```
src/
├── Components/
│   ├── Api/
│   │   ├── ApiClient.php
│   │   ├── ApiException.php
│   │   ├── ApiPayload.php
│   │   └── ApiResponse.php
│   ├── Http/Response/
│   └── Router/
├── Http/
│   ├── Web/
│       └── Home/
│           └── HomePageController.php
│   └── Unifier/
│       └── Home/
│           └── HomePageUnifier.php
├── Modules/
│   └── {Entity}/
│       └── Api/
│           ├── {Entity}Api.php
│           └── Response/
│               └── {Entity}Response.php

templates/
├── layouts/
├── pages/
│   └── home/
│       ├── index.html.twig
│       ├── sections/
│       └── partials/
├── components/
│   ├── product/
│   └── review/
└── shared/
    ├── layout/
    └── ui/
```

---

## Modules

В `src/Modules` лежат только API-модули внешних сущностей.

Для Fake E-commerce API используются модули:

- `Modules/Product`;
- `Modules/User`;
- `Modules/Order`;
- `Modules/Review`.

В модулях запрещены:

- `Query`;
- `Fetcher`;
- `ReadModel`;
- `Command`;
- page handlers;
- Twig;
- HTML;
- page-level сборка данных.

Пример:

```
src/Modules/Product/
└── Api/
    ├── ProductApi.php
    └── Response/
        └── ProductResponse.php
```

---

## API Layer

Общий клиент:

```
src/Components/Api/ApiClient.php
```

Module API:

```
src/Modules/{Entity}/Api/{Entity}Api.php
```

Правила:

- `API_BASE_URL` хранится в `.env`;
- timeout хранится в `API_TIMEOUT`;
- общий `ApiClient` знает только HTTP и возвращает decoded array;
- `ApiPayload::extractData()` достает объект из `data`, если backend/API заворачивает ответ;
- `ApiPayload::extractDataList()` достает список объектов из `data`;
- `ApiPayload::extractStringList()` используется для простых списков строк;
- `ApiResponse::fromArrayList()` собирает список моделей через `fromArray()` конкретной модели;
- module API знает endpoint-ы только своей сущности;
- module API возвращает `Api/Response` модели, а не сырой JSON;
- ошибки HTTP приводятся к `ApiException`;
- один общий `FakeEcommerceApi` не создавать.

---

## API Response Model

`Response` — это модель объекта, который пришел из backend/API.

Если backend возвращает товар, значит модель товара лежит здесь:

```
src/Modules/Product/Api/Response/ProductResponse.php
```

Правила:

- класс `final readonly`;
- поля публичные через constructor promotion;
- обязательный метод `fromArray()`;
- списки собирать через `ApiResponse::fromArrayList($items, ProductResponse::fromArray(...))`;
- nested-объекты ответа тоже называются как response-модели.

Пример:

```php
final readonly class ProductResponse
{
    public function __construct(
        public int $id,
        public string $title,
    ) {}

    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'] ?? 0,
            title: $item['title'] ?? '',
        );
    }
}
```

Если у ответа есть вложенный объект, он тоже должен быть response-моделью с тем же паттерном именования.

---

## Http/Unifier

`Http/Unifier/{Page}/{Page}Unifier` собирает данные конкретной Twig-страницы.

Использовать unifier, когда странице нужны:

- несколько API-модулей;
- товар + отзывы;
- метрики;
- категории;
- SEO/meta;
- любые вычисления для Twig.

Правила:

- вызывает module API;
- собирает page array для Twig;
- описывает форму page array через PHPDoc;
- не знает про HTTP request;
- не рендерит Twig;
- не возвращает response;
- не живет внутри `Modules`.

Если странице достаточно одного вызова API и нет вычислений, контроллер может вызвать module API напрямую.

Fetcher в этом frontend-шаблоне не нужен, пока он просто повторяет метод `Modules/{Entity}/Api/{Entity}Api`. Возвращать fetcher стоит только если появится отдельный переиспользуемый read-сценарий поверх API, который не должен жить в контроллере или unifier.

---

## ReadModel

В этом frontend-шаблоне `ReadModel` в модулях не используется.

Причина: если backend/API уже отдает нужную модель объекта, то `Api/Response/*Response` достаточно. Создавать второй слой `ReadModel`, который просто копирует те же поля, нельзя.

Page-specific данные для Twig собираются в `Http/Unifier` и описываются PHPDoc-ом. Отдельную DTO-модель создавать только если массив стал большим и начал повторяться между страницами.

---

## Http/Web Controller

Все web-контроллеры лежат здесь:

```
src/Http/Web/{Page}/{Page}Controller.php
```

Правила:

- контроллер реализует `RequestHandlerInterface`;
- принимает route/query/body параметры;
- вызывает unifier или module API;
- рендерит Twig;
- возвращает `HtmlResponse`;
- не содержит HTML строками;
- не содержит backend business logic.

В этом шаблоне нет `src/Http/Action`. Папку `Action` не создавать.

---

## Twig

Правила:

- layout: `templates/layouts`;
- страницы: `templates/pages/{page}`;
- секции страницы: `templates/pages/{page}/sections`;
- маленькие частичные шаблоны страницы: `templates/pages/{page}/partials`;
- переиспользуемые UI-компоненты: `templates/components`;
- общие header/footer/ui: `templates/shared`;
- не смешивать все partials/sections в одной папке;
- Twig получает готовые модели/объекты, а не сырой внешний JSON.

---

## React Islands

React используется только для интерактива внутри готового HTML.

Правила:

- PHP/Twig рендерит SEO HTML;
- React монтируется в элементы с `data-react-island`;
- React не должен быть единственным источником основного контента страницы;
- entrypoint: `assets/react/mount.tsx`;
- bundle: `public/build/mount.js`.

---

## Routing

Web routes лежат здесь:

```
config/routes/web.php
```

Правила:

- route указывает на `Http/Web/...Controller`;
- backend `v1` routes не создавать;
- API routes в этом frontend-шаблоне не нужны, если frontend только потребляет внешний API.

---

## Config / Env

Переменные окружения:

```
APP_ENV=dev
APP_DEBUG=1
API_BASE_URL=https://fakeapi.net
API_TIMEOUT=5
```

Правила:

- новые настройки добавлять в `config/common/*.php`;
- dev overrides класть в `config/dev/*.php`;
- секреты не коммитить;
- значения по умолчанию должны позволять запустить шаблон без ручной настройки.

---

## Что запрещено

- `src/Http/Action`;
- backend `v1` routes;
- Doctrine ORM;
- миграции;
- repository для БД;
- JWT/auth backend logic без внешнего API;
- mailer;
- S3/Yandex Disk storage;
- Redis cache;
- OpenAPI/Swagger backend-документация;
- тестовый module внутри `src/Modules`;
- module `Query/Fetcher`;
- module `ReadModel`;
- обращение к реальному backend проекта из тестового шаблона.

Для примеров использовать Fake API или другой явно тестовый внешний источник.
