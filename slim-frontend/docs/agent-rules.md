# Agent Rules

Единый справочник по структуре и паттернам проекта **vs-project**.

> **⚠️ Важно:** Примеры в правилах — ориентир, не шаблон для слепого копирования. У каждой сущности свои поля, связи, бизнес-правила и зависимости.

---

## Разделы

1. [Структура и окружение](rules/structure.md)
2. [Entity](rules/entity.md)
3. [Enum](rules/enum.md)
4. [Repository](rules/repository.md)
5. [Command / Handler](rules/commandhandler.md)
6. [Cache](rules/cache.md)
7. [Service](rules/service.md)
8. [Permission](rules/permission.md)
9. [Query / Fetcher](rules/fetcher.md)
10. [ReadModel](rules/readmodel.md)
11. [Action](rules/action.md)
12. [Unifier](rules/unifier.md)
13. [Translation](rules/translation.md)
14. [Components](rules/components.md)
15. [Исключения и HTTP-коды](rules/exceptions.md)
16. [Frontend API Docs](rules/frontend-api-docs.md)

---

## Миграции

> **⚠️ Миграции составлять НЕ нужно.** Делает разработчик самостоятельно через Doctrine Migrations.

Расположение: `src/Migrations/`
