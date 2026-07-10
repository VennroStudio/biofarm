# Agent Rules

Краткие правила проекта **biofarm**.

Примеры абстрактные. Поля, routes, endpoints и form action names подстраиваются под задачу.

---

## Разделы

1. [Data Module / Repository / Model](rules/module.md)
2. [Command / Handler](rules/commandhandler.md)
3. [Action](rules/action.md)
4. [Unifier](rules/unifier.md)
5. [Templates](rules/templates.md)

## Общие правила

- Page Action: `Controller -> Unifier -> HtmlResponder`.
- Page data: view object из `src/Http/View`.
- Form Action: `FormData -> CsrfToken -> Command Handler -> redirect/render`.
- Data module читает данные через repository/service внутри бэкенда.
- Twig: `pages` собирают секции, `sections` собирают блоки, `widgets` содержат составные use-case блоки, `components` содержат малые переиспользуемые части.
- React island: HTML в Twig, UI-поведение в зеркальном пути `assets/react/{components|sections|widgets|pages}`, mount через явный root/selector contract.
- Формы отправляются обычным HTML POST в web-контроллеры.
- `assets/react/mount.tsx` только импортирует и вызывает mount-функции конкретных React islands.
- Стили Twig-блоков пишутся Tailwind utility-классами в шаблонах; `assets/styles/app.css` остается Tailwind entry point.
