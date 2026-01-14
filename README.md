# Biofarm Project

## Запуск через Docker

### Первый запуск (установка зависимостей)

Если зависимости еще не установлены, сначала установите их через Docker:

```bash
docker-compose run --rm frontend npm install
```

Эта команда установит все зависимости в папку `frontend/node_modules`, которые будут доступны и в проекте, и в контейнере.

### Запуск dev сервера

После установки зависимостей запустите проект:

```bash
docker-compose up
```

Или в фоновом режиме:

```bash
docker-compose up -d
```

Frontend будет доступен по адресу: **http://localhost:8080**

### Остановка

```bash
docker-compose down
```

### Просмотр логов

```bash
docker-compose logs -f frontend
```

### Пересборка контейнера

```bash
docker-compose up --build
```
