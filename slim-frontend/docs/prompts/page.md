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
- widgets/domain/*.html.twig — составные переиспользуемые блоки домена;
- components/domain/*.html.twig — маленькие переиспользуемые блоки домена;
- components/ui/*.html.twig — общие UI-блоки;
- components/layout/*.html.twig — глобальная layout-обвязка.

Зарегистрируй route в config/routes/web.php.

Примеры в правилах абстрактные. Поля, route и зависимости подстрой под задачу.
```
