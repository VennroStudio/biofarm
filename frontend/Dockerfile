FROM node:20-alpine

WORKDIR /app

# Зависимости должны быть установлены локально в проекте
# Этот Dockerfile только запускает dev сервер
# Установите зависимости командой: npm install (в папке frontend)

# Открываем порт для Vite dev server
EXPOSE 8080

# Запускаем dev сервер
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
