```
Изучи правила docs/agent-rules.md:
- 3. Action;
- 4. Unifier;
- 5. Templates.

Создай страницу Page:
- src/Http/Web/Page/PageController.php
- src/Http/Unifier/Page/PageUnifier.php
- src/Http/View/Page/PageView.php
- templates/pages/page/index.html.twig

Если странице нужны данные внешнего API, используй `Modules/Entity/Api/EntityApi`.

Unifier возвращает view object из `src/Http/View`.

Разбей Twig:
- pages/page/index.html.twig — порядок секций;
- sections/domain/*.html.twig — крупные секции;
- widgets/domain/*.html.twig — составные переиспользуемые блоки домена;
- components/domain/*.html.twig — маленькие переиспользуемые блоки домена;
- components/ui/*.html.twig — общие UI-блоки;
- components/layout/*.html.twig — глобальная layout-обвязка.

Разбей CSS зеркально Twig:
- assets/styles/sections/domain/page-section.css для каждой новой секции;
- assets/styles/widgets/domain/widget.css для каждого нового widget;
- assets/styles/components/domain/component.css или components/ui/component.css для каждого нового компонента.

Подключи новые CSS-файлы только через assets/styles/app.css.

Если добавляешь React island, используй root/selector contract в Twig и mount.tsx.

Зарегистрируй route в config/routes/web.php.

Примеры в правилах абстрактные. Поля, route и зависимости подстрой под задачу.
```
