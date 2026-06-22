# Templates

Twig рендерит HTML страницы.

---

## Структура

```
templates/
├── layouts/
├── pages/
├── sections/
├── components/
│   ├── layout/
│   ├── ui/
│   └── {domain}/
└── widgets/
    └── {domain}/
```

---

## Назначение папок

- `layouts` — базовая HTML-обертка.
- `pages/{page}` — тонкая страница-композитор и порядок секций.
- `sections/{domain}` — крупные секции, принадлежащие домену или странице.
- `widgets/{domain}` — самостоятельные составные блоки домена, которые можно вставлять на разные страницы.
- `components/layout` — глобальные layout-компоненты: header, footer, navigation.
- `components/ui` — общие UI-блоки: section head, notice, empty state, метрики.
- `components/{domain}` — маленькие переиспользуемые блоки домена: карточки, формы, React island mount points.

---

## Ownership

Папку выбирает не место вывода, а владелец смысла.

Если Product command panel показывается на Home, он все равно лежит в `widgets/product`, потому что это Product use-case. Home только подключает его в нужном порядке.

```twig
{% include 'widgets/product/command-panel.html.twig' only %}
```

`sections/home` использовать только для блоков, которые существуют именно как часть Home и не имеют доменного владельца.

---

## Page

```twig
{% extends 'layouts/main.html.twig' %}

{% block title %}{{ page.meta.title }}{% endblock %}
{% block description %}{{ page.meta.description }}{% endblock %}

{% block content %}
    {% include 'sections/{domain}/hero.html.twig' with { item: page.item } only %}
    {% include 'sections/{domain}/list.html.twig' with { items: page.items } only %}
    {% include 'widgets/{domain}/{widget}.html.twig' with { item: page.item } only %}
{% endblock %}
```

---

## Section

```twig
<section class="section">
    {% include 'components/ui/section-head.html.twig' with {
        eyebrow: 'Section',
        title: 'Section title',
    } only %}

    {% for item in items %}
        {% include 'components/{domain}/card.html.twig' with { item: item } only %}
    {% endfor %}
</section>
```

---

## React island

Основной HTML остается в Twig. React монтируется в готовую точку. Если mount point используется больше одного раза, он должен быть компонентом.

```twig
{% include 'components/{domain}/{island-name}-island.html.twig' with { item: item } only %}
```

React source:

```
assets/react/
├── mount.tsx
└── islands/
```

React island используется для интерактивного поведения внутри уже отрендеренного Twig widget: отправка форм через `fetch`, модалки, toast, локальное состояние. Он не должен превращать страницу в SPA и не должен дублировать серверную разметку целиком.

Если island только добавляет поведение, его React-компонент возвращает `null`, а весь HTML остается в Twig. React монтировать в пустой marker-элемент рядом с Twig-разметкой, а не в контейнер с уже отрендеренными дочерними элементами.

---

## Widget

Widget — самостоятельный составной блок. Он может включать несколько components и иметь корневой `<section>`, если рендерится как блок страницы.

```twig
<section class="section" id="{domain}-{widget}">
    {% include 'components/ui/section-head.html.twig' with {
        eyebrow: 'Domain',
        title: 'Widget title',
    } only %}

    {% include 'components/{domain}/card.html.twig' with { item: item } only %}
</section>
```

Widget не должен содержать бизнес-запросы, сырые API payloads или controller-логику.
Если widget содержит формы, результат должен оставаться внутри widget через React island. Отдельную result page не создавать; HTML fallback может быть redirect обратно к widget.

---

## Styles

CSS source:

```
assets/styles/
├── app.css
├── base/
├── components/
│   ├── layout/
│   ├── ui/
│   └── {domain}/
├── sections/
│   └── {domain}/
└── widgets/
    └── {domain}/
```

CSS повторяет ownership Twig:

- `templates/components/ui/modal.html.twig` → `assets/styles/components/ui/modal.css`
- `templates/components/product/card.html.twig` → `assets/styles/components/product/card.css`
- `templates/sections/product/product-grid.html.twig` → `assets/styles/sections/product/product-grid.css`
- `templates/widgets/product/command-panel.html.twig` → `assets/styles/widgets/product/command-panel.css`

`app.css` остается только точкой сборки `@import`. Он не содержит селекторы.
Не создавать общие файлы-свалки вроде `cards.css`, `forms.css`, `islands.css` или общий `responsive.css`. Адаптив конкретного блока держать в CSS-файле этого блока.

Twig подключает собранный файл:

```twig
{% block stylesheets %}
    <link rel="stylesheet" href="{{ vite_asset('assets/styles/app.css') }}">
{% endblock %}

{% block javascripts %}
    <script type="module" src="{{ vite_asset('assets/react/mount.tsx') }}"></script>
{% endblock %}
```

---

## Formatting

Шаблоны не форматируют деньги, рейтинг и даты вручную.

```twig
{{ product.price|money }}
{{ product.ratingRate|rating }}
{{ order.orderDate|short_date }}
```

Новые форматтеры добавлять как Twig filters в `App\Components\Twig`.

---

## Правила

- `pages` не содержит большие куски верстки, только blocks и includes.
- `shared` не использовать. Общее живет в `components/ui`, глобальная обвязка в `components/layout`.
- Переиспользуемый маленький блок лежит в `components`.
- Переиспользуемый составной блок лежит в `widgets/{domain}`.
- Крупная секция конкретной страницы или домена лежит в `sections/{domain}`.
- Доменную папку определяет владелец смысла, а не страница, где блок отображается.
- Для `include` использовать `only`, если не нужен весь контекст.
- Twig получает модели и page array, не сырой внешний JSON.
- SEO-данные страницы приходят через `page.meta` или action meta, а не хардкодятся в layout.
- Дублирующиеся формы, карточки, notice, empty states и React island mount points выносить в components.
- React не рендерит HTML для Twig-страницы. Он только читает `data-*`, слушает события и обновляет уже существующие Twig-элементы.
- Модалки и overlay-shells держать в `components/ui`, а доменный widget заполняет их содержимое через `embed`/blocks/data-targets.
- List-section сама отвечает за empty state через `components/ui/empty-state.html.twig`.
- Для каждого нового Twig component/section/widget создавать отдельный CSS-файл в зеркальной папке `assets/styles`.
- Селекторы CSS должны принадлежать своему блоку: `product-card__category`, а не общий `pill`; `featured-product__facts`, а не общий `facts`.
- Статические assets подключать через Vite manifest helper `vite_asset()`, не хардкодить `/build/*.js`.
