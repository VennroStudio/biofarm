# Slim Frontend Agent Rules

Правила для отдельного frontend-приложения на Slim, PHP, Twig и React islands.

Цель шаблона: рендерить SEO-страницы на сервере через PHP/Twig, получать данные из внешнего API, а React использовать только для локальной динамики внутри уже готового HTML.

Этот проект не является backend API. В нем нет БД, миграций, Doctrine, backend `Action`, backend `Query/Fetcher`, backend `ReadModel`, auth-логики и CRUD над своей БД.

---

## Главный принцип

Чтение страницы:

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

Запись во внешний API:

```
HTTP request/form/action
    -> Http/Web/{Page}/{Page}Controller
    -> Modules/{Entity}/Command/{Scenario}/{Scenario}Handler
    -> Modules/{Entity}/Api/{Entity}Api
    -> external API
    -> Modules/{Entity}/Api/Response/{Result}Response
```

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
│   │   └── Home/
│   │       └── HomePageController.php
│   └── Unifier/
│       └── Home/
│           └── HomePageUnifier.php
└── Modules/
    └── {Entity}/
        ├── Api/
        │   ├── {Entity}Api.php
        │   └── Response/
        │       └── {Entity}Response.php
        └── Command/
            └── {Scenario}/
                ├── {Scenario}Command.php
                └── {Scenario}Handler.php

templates/
├── layouts/
├── pages/
├── sections/
├── components/
└── shared/
```

---

## Modules

В `src/Modules` лежат API-модули внешних сущностей и write-сценарии для этих API.

Для Fake E-commerce API используются модули:

- `Modules/Product`;
- `Modules/User`;
- `Modules/Order`;
- `Modules/Review`.

В модулях запрещены:

- `Query`;
- `Fetcher`;
- `ReadModel`;
- page handlers;
- Twig;
- HTML;
- page-level сборка данных.

Пример Product:

```
src/Modules/Product/
├── Api/
│   ├── ProductApi.php
│   └── Response/
│       ├── ProductResponse.php
│       └── ProductDeleteResponse.php
└── Command/
    ├── CreateProduct/
    │   ├── CreateProductCommand.php
    │   └── CreateProductHandler.php
    ├── UpdateProduct/
    │   ├── UpdateProductCommand.php
    │   └── UpdateProductHandler.php
    └── DeleteProduct/
        ├── DeleteProductCommand.php
        └── DeleteProductHandler.php
```

---

## API Layer

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

Правила:

- класс `final readonly`;
- поля публичные через constructor promotion;
- обязательный метод `fromArray()`;
- форма входного массива описывается PHPDoc у `fromArray()`;
- списки собирать через `ApiResponse::fromArrayList($items, ProductResponse::fromArray(...))`;
- nested-объекты ответа тоже называются как response-модели.

---

## Command / Handler

`Command/Handler` используется только для write-сценариев во внешний backend/API:

- create;
- update;
- delete;
- submit;
- login/logout, если frontend обращается к внешнему auth API.

Правила:

- Command содержит входные данные сценария;
- Handler вызывает `Modules/{Entity}/Api/{Entity}Api`;
- Handler возвращает `Api/Response` модель результата;
- Handler не рендерит Twig;
- Handler не знает про HTTP request;
- Handler не собирает page array;
- для чтения страниц не создавать Command.

Пример Product write endpoints для шаблона:

```
ProductApi::createProduct() -> POST /products/create
ProductApi::updateProduct() -> PATCH /products/update
ProductApi::deleteProduct() -> DELETE /products/delete
```

Эти endpoint-ы в шаблоне считаются условным внешним backend contract. Они нужны, чтобы показать архитектурный паттерн write-сценариев.

Web-страница показывает write-сценарий через обычную HTML-форму в Twig:

```
templates/sections/product/command-demo.html.twig
    -> POST /products/create|update|delete
    -> Http/Web/Product/{Scenario}Controller
    -> Modules/Product/Command/{Scenario}/{Scenario}Handler
    -> Modules/Product/Api/ProductApi
```

HTML-формы поддерживают `GET` и `POST`, поэтому web route для update/delete тоже может быть `POST`. Реальный HTTP-метод внешнего API (`PATCH`/`DELETE`) выбирается внутри `ProductApi`.

---

## Http/Unifier

`Http/Unifier/{Page}/{Page}Unifier` собирает данные конкретной Twig-страницы.

Правила:

- вызывает module API;
- собирает page array для Twig;
- описывает форму page array через PHPDoc;
- не знает про HTTP request;
- не рендерит Twig;
- не возвращает response;
- не живет внутри `Modules`.

Fetcher в этом frontend-шаблоне не нужен, пока он просто повторяет метод `Modules/{Entity}/Api/{Entity}Api`. Возвращать fetcher стоит только если появится отдельный переиспользуемый read-сценарий поверх API, который не должен жить в контроллере или unifier.

---

## ReadModel

В этом frontend-шаблоне `ReadModel` в модулях не используется.

Если backend/API уже отдает нужную модель объекта, то `Api/Response/*Response` достаточно. Создавать второй слой `ReadModel`, который просто копирует те же поля, нельзя.

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
- вызывает unifier, command handler или module API;
- рендерит Twig;
- возвращает `HtmlResponse`;
- не содержит HTML строками;
- не содержит backend business logic.

В этом шаблоне нет `src/Http/Action`. Папку `Action` не создавать.

---

## Twig

Правила:

- layout: `templates/layouts`;
- страницы: `templates/pages/{page}`; страница только задает порядок секций;
- переиспользуемые крупные секции: `templates/sections/{domain}`;
- маленькие компоненты сущностей и UI: `templates/components/{domain}`;
- общие header/footer: `templates/shared/layout`;
- если блок может пригодиться на другой странице, он не должен лежать внутри `pages/{page}`;
- не смешивать все partials/sections в одной папке;
- Twig получает готовые модели/объекты, а не сырой внешний JSON.

---

## Assets / Styles

CSS хранится как frontend source, а не редактируется напрямую в `public`.

Структура:

```
assets/styles/
├── app.css
├── base/
├── components/
├── sections/
└── pages/
```

Правила:

- главный CSS entrypoint: `assets/styles/app.css`;
- `app.css` только импортирует файлы слоев;
- `base` — tokens, reset, общая раскладка, responsive;
- `components` — маленькие переиспользуемые UI-блоки;
- `sections` — крупные Twig-секции;
- `pages` — стили конкретной страницы;
- Vite собирает CSS в `public/build/app.css`;
- Twig подключает только собранный `/build/app.css`;
- файлы в `public/build` являются build output.

---

## React Islands

React используется только для интерактива внутри готового HTML.

Правила:

- PHP/Twig рендерит SEO HTML;
- React монтируется в элементы с `data-react-island`;
- React не должен быть единственным источником основного контента страницы;
- entrypoint: `assets/react/mount.tsx`;
- island-компоненты: `assets/react/islands`;
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
