# Инструкция по развертыванию на reg.ru

## Подготовка файлов

### 1. Сборка Frontend

На вашем локальном компьютере выполните:

```bash
cd frontend
npm install
npm run build
```

После сборки в папке `frontend/dist` будут готовые файлы для загрузки на хостинг.

### 2. Подготовка Backend

Убедитесь, что папка `backend/vendor` содержит все зависимости. Если нет, выполните:

```bash
cd backend
composer install --no-dev --optimize-autoloader
```

## Загрузка на хостинг reg.ru

### Структура папок на хостинге

Обычно на reg.ru структура такая:
```
/home/u1234567/domains/yourdomain.com/public_html/
```

Рекомендуемая структура:
```
public_html/
├── api/              # Backend (PHP)
│   ├── public/
│   │   └── index.php
│   ├── src/
│   ├── config/
│   ├── vendor/
│   └── .env
└── *                 # Frontend (статические файлы из frontend/dist)
```

### Вариант 1: Frontend в корне, Backend в подпапке

1. **Загрузите файлы frontend:**
   - Скопируйте ВСЕ файлы из `frontend/dist/` в корень `public_html/`

2. **Загрузите backend:**
   - Создайте папку `public_html/api/`
   - Загрузите туда всю папку `backend/` (кроме `backend/public/`)
   - Загрузите `backend/public/index.php` в `public_html/api/index.php`

### Вариант 2: Backend в поддомене (рекомендуется)

1. **Frontend:**
   - Загрузите файлы из `frontend/dist/` в `public_html/`

2. **Backend:**
   - Создайте поддомен `api.yourdomain.com`
   - Загрузите всю папку `backend/` в папку поддомена
   - Убедитесь, что `public/index.php` доступен как точка входа

## Настройка Backend

### 1. Создайте файл `.env` в папке backend

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=u1234567_dbuser
DB_PASSWORD=your_password
DB_NAME=u1234567_dbname
```

**Где найти данные БД:**
- В панели управления reg.ru найдите раздел "Базы данных MySQL"
- Используйте данные из созданной базы данных

### 2. Настройте права доступа

```bash
chmod 755 backend/
chmod 644 backend/.env
chmod 755 backend/public/
chmod 644 backend/public/index.php
```

### 3. Запустите миграции базы данных

Через SSH подключитесь к хостингу и выполните:

```bash
cd /home/u1234567/domains/yourdomain.com/public_html/api
php bin/migrations.php migrations:migrate --no-interaction
```

Если SSH недоступен, используйте файловый менеджер reg.ru для загрузки скрипта миграции.

## Настройка Frontend

### 1. Обновите переменную окружения API

В файле `frontend/src/lib/api.ts` измените:

```typescript
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';
```

На:

```typescript
const API_BASE_URL = import.meta.env.VITE_API_URL || 'https://api.yourdomain.com';
```

Или если API в подпапке:
```typescript
const API_BASE_URL = import.meta.env.VITE_API_URL || 'https://yourdomain.com/api';
```

**Важно:** Пересоберите frontend после изменения:
```bash
cd frontend
npm run build
```

### 2. Создайте файл `.htaccess` для Frontend (если используется Apache)

В корне `public_html/` создайте файл `.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Если файл или папка существуют, используем их
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # Иначе перенаправляем на index.html (для React Router)
    RewriteRule ^ index.html [L]
</IfModule>
```

## Настройка Backend (Apache)

### Создайте `.htaccess` в папке `api/` или `api/public/`

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /api/
    
    # Если файл существует, используем его
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # Иначе перенаправляем на index.php
    RewriteRule ^ index.php [L]
</IfModule>
```

### Если backend в поддомене, `.htaccess` в корне поддомена:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ public/index.php [L]
</IfModule>
```

## Настройка PHP

Убедитесь, что на хостинге:
- PHP версия 8.1 или выше
- Включены расширения: `pdo_mysql`, `mbstring`, `json`, `curl`
- `memory_limit` не менее 256M
- `upload_max_filesize` достаточен для загрузки изображений

В панели reg.ru настройте PHP версию в разделе "PHP настройки".

## Проверка работы

### 1. Проверьте API

Откройте в браузере:
```
https://api.yourdomain.com/
```

Должен вернуться JSON:
```json
{"message":"Biofarm API","version":"1.0.0"}
```

### 2. Проверьте Frontend

Откройте:
```
https://yourdomain.com/
```

Сайт должен загрузиться, и API запросы должны работать.

## Решение проблем

### Ошибка 500 на API

1. Проверьте права доступа к файлам
2. Проверьте логи ошибок в панели reg.ru
3. Убедитесь, что `.env` файл создан и содержит правильные данные
4. Проверьте, что `vendor/` загружен полностью

### CORS ошибки

Если видите ошибки CORS, проверьте настройки в `backend/public/index.php` - там уже настроен CORS для всех доменов.

### База данных не подключается

1. Проверьте данные в `.env`
2. Убедитесь, что база данных создана в панели reg.ru
3. Проверьте, что пользователь БД имеет все необходимые права

### Frontend не загружается

1. Убедитесь, что все файлы из `frontend/dist/` загружены
2. Проверьте `.htaccess` файл
3. Убедитесь, что `index.html` находится в корне `public_html/`

## Дополнительные настройки

### SSL сертификат

В панели reg.ru включите SSL сертификат для домена (обычно Let's Encrypt бесплатный).

### Кэширование

Для улучшения производительности можно настроить кэширование статических файлов через `.htaccess`:

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

## Контакты поддержки

Если возникнут проблемы, обратитесь в поддержку reg.ru или проверьте документацию хостинга.
