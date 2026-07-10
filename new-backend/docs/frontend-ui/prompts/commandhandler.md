```
Изучи правила docs/agent-rules.md:
- 1. Data Module / Repository / Model;
- 2. Command / Handler;
- 3. Action.

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

Handler вызывает repository/service внутри бэкенда.
Handler может содержать сценарную бизнес-логику: нормализацию, расчеты, фильтрацию, подготовку данных перед сохранением или подготовку данных перед выводом.

Если write-сценарий запускается из формы, создай Action в src/Http/Web/Entity и route в config/routes/web.php.
Form Action читает данные через `App\Components\Http\Form\FormData`.

Примеры в правилах абстрактные. Поля, routes и result view подстрой под задачу.
```
