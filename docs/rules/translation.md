# Translation — Переводы

Переводы используются для: ошибок домена (`DomainExceptionModule`), валидации (`Assert`), писем (Twig-шаблоны). Хранятся как PHP-массивы, загружаются через Symfony Translator в `config/common/translator.php`.

**Локали:** `en`, `ru` — оба обязательны для каждого нового ключа.

**Расположение:**
- Модуль: `src/Modules/{Module}/Translation/`
- Компоненты: `src/Components/Translation/`

---

## Правила

- Файлы по категориям: `errors.{locale}.php`, `validators.{locale}.php`, и отдельные файлы для писем (например, `emailVerification.{locale}.php`)
- **Домен** = имя файла без `.{locale}.php` (например, `errors.en.php` → домен `errors`)
- **`DomainExceptionModule`** — домен = параметр `module` исключения; транслятор автоматически ищет ключи во всех файлах папки `src/Modules/{Module}/Translation/` (модуль приводится к нижнему регистру)
- Исключения из слоя компонентов — указывать `module: 'components'`; ключи хранить в `src/Components/Translation/`
- **Валидация** — домен `validators`; ключи совпадают с `message` в Assert атрибутах
- **Письма** — отдельный файл на каждый сценарий, домен совпадает с именем файла; в Twig — через функцию `trans()`

---

## Структура файлов модуля

```
src/Modules/{Module}/Translation/
├── errors.en.php
├── errors.ru.php
├── validators.en.php
├── validators.ru.php
├── {mailScenario}.en.php   # опционально, для писем
└── {mailScenario}.ru.php
```

---

## errors.{locale}.php — ошибки домена

```php
<?php

declare(strict_types=1);

return [
    'error.{entity}_not_found'     => '{Entity} not found.',
    'error.{entity}_is_deleted'    => '{Entity} is deleted.',
    'error.email_already_taken'    => 'Email is already taken.',
    'error.invalid_credentials'    => 'Invalid credentials.',
    // ...
];
```

## validators.{locale}.php — сообщения валидации

```php
<?php

declare(strict_types=1);

return [
    'validation.name_required'    => 'Name is required.',
    'validation.name_too_short'   => 'Name is too short.',
    'validation.email_required'   => 'Email is required.',
    'validation.email_invalid'    => 'Invalid email address.',
    'validation.password_required' => 'Password is required.',
    // ...
];
```

## {mailScenario}.{locale}.php — переводы для писем

Домен при вызове `trans()` совпадает с именем файла:

```php
// emailVerification.en.php
return [
    'mail.{scenario}.subject'  => 'Subject text',
    'mail.{scenario}.greeting' => 'Hello, %name%!',
    'mail.{scenario}.button'   => 'Confirm',
    // ...
];
```

```php
// В Handler'е:
$this->translator->trans('mail.{scenario}.subject', [], '{scenario}');
```

---

## Компоненты: src/Components/Translation/errors.{locale}.php

Для исключений из слоя компонентов (auth, валидация файлов и т.д.). При выбросе `DomainExceptionModule` из компонентов — `module: 'components'`.

```php
<?php

declare(strict_types=1);

return [
    'error.unauthorized'      => 'User is not authorized.',
    'error.invalid_token'     => 'Token is invalid or expired.',
    'error.missing_cookie'    => 'Missing token cookie.',
    'error.invalid_mime_type' => 'Invalid file format.',
    'error.file_too_large'    => 'The file size is too large.',
    // ...
];
```