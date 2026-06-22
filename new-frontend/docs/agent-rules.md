# Agent Rules

Краткие правила проекта **slim-frontend**.

Примеры абстрактные. Поля, routes, endpoints и form action names подстраиваются под задачу.

---

## Разделы

1. [Module / API / Response](rules/module.md)
2. [Command / Handler](rules/commandhandler.md)
3. [Action](rules/action.md)
4. [Unifier](rules/unifier.md)
5. [Templates](rules/templates.md)

## Общие правила

- Page Action: `Controller -> Unifier -> HtmlResponder`.
- Page data: view object из `src/Http/View`.
- Form Action: `FormData -> CsrfToken -> Command Handler -> Responder`.
- Module API возвращает Response-модели.
- Twig: `pages` собирают секции, `sections` собирают блоки, `widgets` содержат составные use-case блоки, `components` содержат малые переиспользуемые части.
- React island: HTML в Twig, поведение в зеркальном пути `assets/react/{components|sections|widgets|pages}`, mount через явный root/selector contract.
- `assets/react/mount.tsx` только импортирует и вызывает mount-функции конкретных React islands.
- CSS зеркалит Twig ownership и подключается через `assets/styles/app.css`.
