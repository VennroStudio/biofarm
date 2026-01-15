# Biofarm Project

## Запуск через Docker

### Первый запуск (установка зависимостей)

#### Frontend

Если зависимости еще не установлены, сначала установите их через Docker:

```bash
docker-compose run --rm frontend npm install
```

Эта команда установит все зависимости в папку `frontend/node_modules`, которые будут доступны и в проекте, и в контейнере.

#### Backend

Установите зависимости PHP через Composer:

```bash
# Запустите контейнер в фоне
docker-compose up -d backend

# Установите зависимости внутри запущенного контейнера
docker-compose exec backend composer install

# Остановите контейнер (опционально)
docker-compose down
```

**Примечание:** Если возникают проблемы с SSL при использовании `docker-compose run`, используйте метод выше (запуск контейнера и установка зависимостей внутри него).

Эта команда установит все зависимости в папку `backend/vendor`, которые будут доступны и в проекте, и в контейнере.

### Запуск dev серверов

После установки зависимостей запустите проект:

```bash
docker-compose up
```

Или в фоновом режиме:

```bash
docker-compose up -d
```

- **Frontend**: http://localhost:8080
- **Backend API**: http://localhost:8000

### Остановка

```bash
docker-compose down
```

### Просмотр логов

```bash
# Все сервисы
docker-compose logs -f

# Только frontend
docker-compose logs -f frontend

# Только backend
docker-compose logs -f backend
```

### Пересборка контейнеров

```bash
docker-compose up --build
```

## Структура проекта

- `frontend/` - React + Vite приложение
- `backend/` - PHP Slim Framework API
