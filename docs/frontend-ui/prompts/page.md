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

Если странице нужны данные, получай их внутри бэкенда через repository/service и передавай в Unifier.

Unifier возвращает view object из `src/Http/View`.

Разбей Twig:
- pages/page/index.html.twig — порядок секций;
- sections/domain/*.html.twig — крупные секции;
- widgets/domain/*.html.twig — составные переиспользуемые блоки домена;
- components/domain/*.html.twig — маленькие переиспользуемые блоки домена;
- components/ui/*.html.twig — общие UI-блоки;
- components/layout/*.html.twig — глобальная layout-обвязка.

Стили новых Twig-блоков делай utility-классами Tailwind прямо в шаблонах.
Не создавай отдельные CSS-файлы для секций, widgets или components.
`assets/styles/app.css` должен оставаться Tailwind entry point.

Если добавляешь React island, используй root/selector contract в Twig.
React-файл положи в зеркальный путь от Twig ownership: `templates/components/layout/header.html.twig` → `assets/react/components/layout/header.tsx`.
Из React-файла экспортируй mount-функцию, а в `assets/react/mount.tsx` только импортируй и вызови ее.

Зарегистрируй route в config/routes/web.php.

Примеры в правилах абстрактные. Поля, route и зависимости подстрой под задачу.
```
