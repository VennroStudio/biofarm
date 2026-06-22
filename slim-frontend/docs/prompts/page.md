```
Изучи правила docs/agent-rules.md:
- 3. Action;
- 4. Unifier;
- 5. Templates.

После этого приступай к задаче.

Создай страницу Page:
- src/Http/Web/Page/PageController.php
- src/Http/Unifier/Page/PageUnifier.php
- templates/pages/page/index.html.twig

Если странице нужны данные внешнего API, используй Modules/Entity/Api/EntityApi.

Разбей Twig:
- pages/page/index.html.twig — порядок секций;
- sections/domain/*.html.twig — крупные секции;
- components/domain/*.html.twig — переиспользуемые блоки.

Зарегистрируй route в config/routes/web.php.

Примеры в правилах абстрактные. Поля, route и зависимости подстрой под задачу.
```
