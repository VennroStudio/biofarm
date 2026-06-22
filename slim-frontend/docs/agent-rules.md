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

## Runtime правила

- Новые page/form actions рендерят Twig через `HtmlResponder`.
- Новые write forms используют `csrf_token()` и валидацию входа до Handler.
- Новые runtime checks добавлять в `/healthz` или `/readyz`, если они нужны для deploy-платформы.
- Не выводить пользователю raw upstream URL, secrets или transport exception details.
