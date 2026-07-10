FROM node:20-alpine AS assets

WORKDIR /app

COPY ./package.json ./package-lock.json ./

RUN npm ci

COPY ./assets ./assets
COPY ./templates ./templates
COPY ./tsconfig.json ./vite.config.ts ./tailwind.config.ts ./postcss.config.js ./

RUN npm run build

FROM nginx:alpine

COPY ./docker/production/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /app

COPY ./public /app/public
COPY --from=assets /app/public/build /app/public/build
