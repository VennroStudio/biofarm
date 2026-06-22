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

React island используется для интерактивного поведения внутри уже отрендеренного Twig widget: отправка форм через `fetch`, модалки, toast, локальное состояние.

Если island только добавляет поведение, React-компонент возвращает `null`, а HTML остается в Twig.

Mount point использует root/selector contract.

```twig
<div data-product-counter-root>
    <div data-product-counter></div>

    <div
        data-react-island="product-counter"
        data-target-selector="[data-product-counter]"
        hidden
    ></div>
</div>
```

```ts
const rootElement = htmlElement.closest<HTMLElement>('[data-product-counter-root]');
const targetElement = rootElement?.querySelector<HTMLElement>(
  htmlElement.dataset.targetSelector || '[data-product-counter]',
);
```

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

Widget содержит только Twig composition. Данные приходят через page view object.
Формы внутри widget обрабатываются через React island; HTML fallback делает redirect обратно к widget.

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

`app.css` остается точкой сборки `@import`.
Адаптив конкретного блока хранится в CSS-файле этого блока.

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

## Media

Картинки в списках, карточках и модалках:

```twig
<img
    src="{{ item.image }}"
    alt="{{ item.title }}"
    loading="lazy"
    decoding="async"
>
```

Главная картинка первого экрана:

```twig
<img
    src="{{ item.image }}"
    alt="{{ item.title }}"
    loading="eager"
    fetchpriority="high"
    decoding="async"
>
```

Видео:

```twig
<video
    controls
    preload="metadata"
    poster="{{ video.poster }}"
    playsinline
>
    <source src="{{ video.src }}" type="video/mp4">
</video>
```

Тяжелое видео ниже первого экрана:

```twig
<video
    controls
    preload="none"
    poster="{{ video.poster }}"
    playsinline
    data-lazy-video
>
    <source data-src="{{ video.src }}" type="video/mp4">
</video>
```

---

## Правила

- `pages` содержит blocks и includes.
- Общее живет в `components/ui`, глобальная обвязка в `components/layout`.
- Переиспользуемый маленький блок лежит в `components`.
- Переиспользуемый составной блок лежит в `widgets/{domain}`.
- Крупная секция конкретной страницы или домена лежит в `sections/{domain}`.
- Доменную папку определяет владелец смысла, а не страница, где блок отображается.
- Для `include` использовать `only`, если не нужен весь контекст.
- Twig получает модели и view objects, не сырой внешний JSON.
- SEO-данные страницы приходят через `page.meta` или action meta, а не хардкодятся в layout.
- Дублирующиеся формы, карточки, notice, empty states и React island mount points выносить в components.
- React island читает `data-*`, слушает события и обновляет Twig-элементы.
- React island использует root/selector contract.
- Модалки и overlay-shells держать в `components/ui`, а доменный widget заполняет их содержимое через `embed`/blocks/data-targets.
- List-section сама отвечает за empty state через `components/ui/empty-state.html.twig`.
- Для каждого нового Twig component/section/widget создавать отдельный CSS-файл в зеркальной папке `assets/styles`.
- Селекторы CSS должны принадлежать своему блоку: `product-card__category`, а не общий `pill`; `featured-product__facts`, а не общий `facts`.
- Статические assets подключать через Vite manifest helper `vite_asset()`, не хардкодить `/build/*.js`.
- Картинки в карточках, списках и модалках используют `loading="lazy"` и `decoding="async"`.
- Картинка первого экрана использует `loading="eager"`, `fetchpriority="high"` и `decoding="async"`.
- Видео использует `poster`, `playsinline` и `preload="metadata"`; тяжелое видео ниже первого экрана использует `preload="none"` и `data-src`.
