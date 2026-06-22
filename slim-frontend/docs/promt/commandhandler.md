```
Изучи правила docs/agent-rules.md:
- 1. Module / API / Response;
- 2. Command / Handler;
- 3. Action.

После этого приступай к задаче.

Создай command/handler сущности Entity для нужных write-сценариев:
- create;
- update;
- delete.

Структура:
- src/Modules/Entity/Command/CreateEntity/CreateEntityCommand.php
- src/Modules/Entity/Command/CreateEntity/CreateEntityHandler.php
- src/Modules/Entity/Command/UpdateEntity/UpdateEntityCommand.php
- src/Modules/Entity/Command/UpdateEntity/UpdateEntityHandler.php
- src/Modules/Entity/Command/DeleteEntity/DeleteEntityCommand.php
- src/Modules/Entity/Command/DeleteEntity/DeleteEntityHandler.php

Handler вызывает EntityApi.
Handler может содержать сценарную бизнес-логику: нормализацию, расчеты, фильтрацию, подготовку payload перед сохранением или подготовку данных перед выводом.

Если write-сценарий запускается из формы, создай Action в src/Http/Web/Entity и route в config/routes/web.php.

Примеры в правилах абстрактные. Поля, endpoints и result view подстрой под задачу.
```
