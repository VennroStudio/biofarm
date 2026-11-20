```
Изучи правила docs/agent-rules.md:
- 1. Data Module / Repository / Model.

После этого приступай к задаче.

Создай модуль данных сущности Entity:
- src/Modules/Entity/Model/Entity.php
- src/Modules/Entity/Repository/EntityRepository.php

В Repository добавь только нужные методы:
- findAll
- findById / findBySlug
- save / delete, если нужен write-сценарий

Пока база данных не перенесена, repository может возвращать пустые коллекции или временные read-модели.
После миграции замени временную реализацию на чтение из БД без изменения Twig-контракта страницы.

Примеры в правилах абстрактные. Поля и routes подстрой под задачу.
```
