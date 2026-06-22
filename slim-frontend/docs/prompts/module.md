```
Изучи правила docs/agent-rules.md:
- 1. Module / API / Response.

После этого приступай к задаче.

Создай API-модуль сущности Entity:
- src/Modules/Entity/Api/EntityApi.php
- src/Modules/Entity/Api/Response/EntityResponse.php

В EntityApi добавь только нужные методы:
- getEntities
- getEntity
- createEntity / updateEntity / deleteEntity, если нужен write-сценарий

Ответы внешнего API преобразуй в Response через fromArray().
Списки собирай через ApiResponse::fromArrayList().

Примеры в правилах абстрактные. Поля и endpoints подстрой под задачу.
```
