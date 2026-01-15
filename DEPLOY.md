# Инструкция по развертыванию на reg.ru

## Подготовка файлов

### 1. Сборка Frontend

**Если используете Docker (рекомендуется):**

```bash
# В корне проекта
docker-compose exec frontend sh -c "cd /app && npm run build"
```

**Или локально:**

```bash
cd frontend
npm install
npm run build
```

После сборки в папке `frontend/dist` будут готовые файлы для загрузки на хостинг.

### 2. Подготовка Backend

Убедитесь, что папка `backend/vendor` содержит все зависимости. 

**Если используете Docker:**

```bash
# В корне проекта
docker-compose exec backend sh -c "cd /app && composer install --no-dev --optimize-autoloader"
```

**Или локально:**

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

### Важно: Использование единой базы данных

В вашем случае база данных находится отдельно от хостинга и используется как для локальной разработки, так и для продакшена. Это означает:

- ✅ Одна база данных для разработки и продакшена
- ✅ Не нужно синхронизировать данные между окружениями
- ✅ Миграции можно запускать с любой машины, имеющей доступ к БД
- ✅ Те же учетные данные для подключения везде

### 1. Создайте файл `.env` в папке backend на хостинге

**Важно:** Используйте те же данные подключения к базе данных, что и на локальной машине.

Скопируйте ваш локальный `.env` файл из `backend/.env` и загрузите его на хостинг в папку `api/.env`:

```env
DB_HOST=your_database_host
DB_PORT=3306
DB_USER=your_database_user
DB_PASSWORD=your_database_password
DB_NAME=your_database_name
```

**Критически важно:**
- ✅ Используйте **точно те же** учетные данные, что и в локальном `.env` файле
- ✅ База данных уже существует и находится отдельно от хостинга
- ✅ Убедитесь, что сервер БД разрешает подключения с IP-адреса хостинга reg.ru
- ✅ Проверьте firewall настройки на сервере БД - добавьте IP хостинга в whitelist
- ✅ Убедитесь, что порт 3306 (или другой, если используется) открыт на сервере БД

### 2. Настройте права доступа

```bash
chmod 755 backend/
chmod 644 backend/.env
chmod 755 backend/public/
chmod 644 backend/public/index.php
```

### 3. Запустите миграции базы данных

**Рекомендуется: Запуск миграций с локальной машины**

Поскольку база данных одна и та же для локальной разработки и продакшена, выполните миграции локально:

```bash
cd backend
php bin/migrations.php migrations:migrate --no-interaction
```

Или через Docker:
```bash
docker-compose exec backend php bin/migrations.php migrations:migrate --no-interaction
```

Миграции применятся к вашей базе данных, которая используется и на проде, и на локалке.

**Альтернатива: Через SSH на хостинге (если доступен)**

Если SSH доступен, можно выполнить миграции на хостинге:

```bash
cd /home/u1234567/domains/yourdomain.com/public_html/api
php bin/migrations.php migrations:migrate --no-interaction
```

**Важно:** 
- Миграции нужно запускать только один раз (при первом деплое)
- При последующих обновлениях запускайте миграции только если были добавлены новые
- Поскольку БД одна, миграции можно запускать с любой машины, имеющей доступ к БД

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

1. **Проверьте данные в `.env`** - они должны **точно совпадать** с локальными
2. **Проверьте доступность БД с хостинга:**
   - Узнайте IP-адрес вашего хостинга reg.ru (можно найти в панели управления)
   - Убедитесь, что сервер БД разрешает подключения с этого IP-адреса
   - Проверьте firewall настройки на сервере БД - добавьте IP хостинга в whitelist
3. **Проверьте сетевые настройки:**
   - Убедитесь, что порт 3306 (или другой, если используется) открыт на сервере БД
   - Если БД находится за NAT/firewall, убедитесь, что порт проброшен
4. **Проверьте права пользователя БД:**
   - Убедитесь, что пользователь БД имеет все необходимые права:
     - SELECT, INSERT, UPDATE, DELETE
     - CREATE, ALTER, DROP (для миграций)
     - INDEX, REFERENCES
5. **Проверьте логи:**
   - Посмотрите логи ошибок в панели reg.ru
   - Проверьте логи на сервере БД для диагностики проблем подключения

**Тестирование подключения:**

Можно создать тестовый PHP скрипт на хостинге для проверки подключения:

```php
<?php
// test-db.php - временный файл для проверки подключения
$host = 'your_database_host';
$db = 'your_database_name';
$user = 'your_database_user';
$pass = 'your_database_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "Подключение успешно!";
} catch(PDOException $e) {
    echo "Ошибка подключения: " . $e->getMessage();
}
?>
```

**Важно:** Удалите этот тестовый файл после проверки!

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
