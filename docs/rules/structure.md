# Структура и окружение проекта

> **⚠️ Важно:** Примеры в правилах — ориентир, не шаблон для слепого копирования. У каждой сущности свои поля, связи, бизнес-правила и зависимости.

---

## Окружение и стандарты

- **PHP 8.4** — asymmetric visibility, property hooks и т.д.
- **Docker** — весь runtime в контейнерах, настройки в `docker/`, `docker-compose.yml` (dev) / `docker-compose-production.yml` (prod).
- **PHPDoc** — тип `mixed` запрещён; массивы описывать через array shapes: `array{id: int, name: string}`.
- **Команды** — только через Make или `docker compose run --rm php-cli ...`, не напрямую с хоста.

Основные цели Make (`make help` — полный список):

| Цель | Назначение |
|------|------------|
| `make init` | Инициализация dev-окружения |
| `make lint` / `make fix` | Линтеры (rector, php-cs-fixer, psalm) |
| `make migrations-diff` | Сгенерировать миграцию |
| `make migrations-migrate` | Выполнить миграции |
| `make composer-add PACK=...` | Добавить зависимость |

---

## Структура проекта

```
src/
├── Components/          # Инфраструктурные компоненты (общие для всех модулей)
├── Console/             # Консольные команды
├── Http/
│   ├── Action/v1/       # HTTP Actions (Slim), группировка по сущности/сценарию
│   └── Unifier/         # Unifier-классы
├── Migrations/          # Doctrine-миграции
└── Modules/             # Доменные модули
```

```
config/
├── common/              # slim, doctrine, auth, repositories, translator и др.
├── dev/                 # dev-only overrides
├── routes/v1.php        # Маршруты API
├── app.php, container.php, middleware.php, dependencies.php
```

---

## Структура модуля

```
src/Modules/{Module}/
├── Entity/{Entity}/
│   ├── {Entity}.php                          # Доменная сущность (Doctrine ORM)
│   ├── {Entity}Repository.php                # Интерфейс репозитория (только write)
│   ├── Fields/Enums/                         # Enum-поля сущности
│   └── Persistence/Doctrine/
│       └── Doctrine{Entity}Repository.php
├── Command/{Entity}/{Action}/
│   ├── {Action}{Entity}Command.php
│   └── {Action}{Entity}Handler.php
├── Query/{Entity}/{Action}/
│   ├── {Entity}{Action}Query.php
│   └── {Entity}{Action}Fetcher.php
├── ReadModel/{Entity}/
│   ├── Interface/{Entity}ModelInterface.php
│   └── {Entity}By{Field}.php
├── Permission/
│   └── {Module}Permission.php
├── Service/
│   ├── {Name}Service.php
│   └── {Module}PermissionService.php
└── Translation/
    ├── errors.en.php / errors.ru.php
    └── validators.en.php / validators.ru.php

src/Http/Action/v1/{Entity}/
└── {Action}{Entity}Action.php
```

---

## Правила структуры

**Именование:**
- Модуль — `src/Modules/{Module}/` с заглавной буквы.
- Action — `{Действие}{Сущность}Action` (например, `CreatePostAction`, `GetUsersAction`).
- Маршруты — в `config/routes/v1.php`, группы соответствуют префиксам URL.

**Components vs Modules:**
- `src/Components/` — чистая инфраструктура, без доменной логики, не зависит от модулей.
- `src/Modules/` — доменная логика. Межмодульное взаимодействие — только через публичный API (Handler, Fetcher, Service), не через чужие Entity/Repository напрямую.

**Конфигурация:**
- Новые сервисы и репозитории регистрируются в `container.php` / `config/common/repositories.php`.
- Роуты — только в `config/routes/`.

**Документация:**
- При добавлении эндпоинтов — обновлять `docs/frontend/{Module}.md`.
- Правила проекта — `docs/rules/`.