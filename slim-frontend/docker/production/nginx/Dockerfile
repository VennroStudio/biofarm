FROM nginx:alpine

COPY ./docker/production/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /app

COPY ./public /app/public
