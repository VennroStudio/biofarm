```
Изучи структуру проекта и окружение пункт 1. Структура и окружение, правил docs/agent-rules.md.

После этого приступай к работе над задачей.

Согласно пункту 4. Command / Handler, правил docs/agent-rules.md: 
1) Cоздай command/handler сущности Entity для:
- создания, 
- обновления,
- удаления.

Command/Entity/:
CreateEntityHandler/Command
DeleteEntityHandler/Command
UpdateEntityHandler/Command

Согласно пункту 9. Translation, правил docs/agent-rules.md: 
Создай нужные для сущности Entity переводы

Согласно пункту 7. Service / Permission, правил docs/agent-rules.md: 
1) Создай Service и Permission для сущности Entity (вариант сущности без владельца, пример src/Modules/Printing/Service/PrintingPermissionService.php).

Для обновления нужно добавить функцию edit в сущности. 

За пример бери реализованный в проекте модуль Primer, но подстраивайся под ситуацию с сущностью и опирайся на правила.
```