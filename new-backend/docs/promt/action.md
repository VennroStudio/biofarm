```
Изучи структуру проекта и окружение пункт 1. Структура и окружение, правил docs/agent-rules.md.

После этого приступай к работе над задачей.

Cогласно пункту 8. Action, правил docs/agent-rules.md: 
1.1) Cоздай Action для сущности Entity:

Http/Action/v1/Entity/:
CreateEntityAction
DeleteEntityAction
UpdateEntityAction
GetEntityByIdAction
GetEntitysAction

1.2) Создай Unifier для сущности Entity:

Http/Unifier/Entity/EntityUnifier

1.3) Зарегистрируй маршруты в config/routes/v1.php.

За пример бери реализованный в проекте модуль Primer, но подстраивайся под ситуацию с сущностью и опирайся на правила.
```