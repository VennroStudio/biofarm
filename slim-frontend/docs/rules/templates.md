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
└── shared/
```

---

## Назначение папок

- `layouts` — базовая HTML-обертка.
- `pages/{page}` — страница и порядок секций.
- `sections/{domain}` — крупные секции.
- `components/{domain}` — маленькие переиспользуемые блоки.
- `shared/layout` — header, footer и общая layout-обвязка.

---

## Page

```twig
{% extends 'layouts/main.html.twig' %}

{% block title %}{{ page.title }}{% endblock %}

{% block content %}
    {% include 'sections/{domain}/hero.html.twig' with { item: page.item } only %}
    {% include 'sections/{domain}/list.html.twig' with { items: page.items } only %}
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

Основной HTML остается в Twig. React монтируется в готовую точку.

```twig
<div
    data-react-island="{island-name}"
    data-entity-id="{{ item.id }}"
></div>
```

React source:

```
assets/react/
├── mount.tsx
└── islands/
```

---

## Styles

CSS source:

```
assets/styles/
├── app.css
├── base/
├── components/
├── sections/
└── pages/
```

Twig подключает собранный файл:

```twig
<link rel="stylesheet" href="/build/app.css">
```

---

## Правила

- `pages` не содержит большие куски верстки.
- Переиспользуемый блок лежит в `sections` или `components`.
- Для `include` использовать `only`, если не нужен весь контекст.
- Twig получает модели и page array, не сырой внешний JSON.
