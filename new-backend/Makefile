-include .env.deploy
export

VERSION := $(IMAGE_TAG).$(BUILD_NUMBER)

init: docker-clear docker-build docker-up composer-init frontend-install frontend-build
restart: docker-down docker-up
lint: php-linter frontend-check
fix: php-linter-fix

docker-up:
	docker-compose up -d nginx php-fpm php-cli
docker-down:
	docker-compose down --remove-orphans
docker-clear:
	docker-compose down -v --remove-orphans
docker-build:
	docker-compose build --pull nginx php-fpm php-cli

php-linter:
	docker compose run --rm php-cli composer lint; \
	docker compose run --rm php-cli composer rector -- --dry-run; \
	docker compose run --rm php-cli composer php-cs-fixer fix -- --dry-run --diff; \

php-linter-fix:
	docker compose run --rm php-cli composer rector
	docker compose run --rm php-cli composer psalm -- --alter --issues=MissingOverrideAttribute
	docker compose run --rm php-cli composer php-cs-fixer fix

psalm:
	docker compose run --rm php-cli composer psalm

docker-login:
	docker login ${REGISTRY}

server-login:
	ssh -t $(HOST) -p $(PORT) 'docker login $(REGISTRY)'

server-logout:
	ssh -t $(HOST) -p $(PORT) 'docker logout'

build:
	docker --log-level=debug build --platform linux/amd64 --pull --file=./docker/production/nginx/Dockerfile --tag=${REGISTRY}/biofarm-nginx:${VERSION} .
	docker --log-level=debug build --platform linux/amd64 --pull --file=./docker/production/php-fpm/Dockerfile --tag=${REGISTRY}/biofarm-php-fpm:${VERSION} .
	docker --log-level=debug build --platform linux/amd64 --pull --file=./docker/production/php-cli/Dockerfile --tag=${REGISTRY}/biofarm-php-cli:${VERSION} .

push:
	docker push ${REGISTRY}/biofarm-nginx:${VERSION}
	docker push ${REGISTRY}/biofarm-php-fpm:${VERSION}
	docker push ${REGISTRY}/biofarm-php-cli:${VERSION}

deploy:
	ssh $(HOST) -p $(PORT) 'rm -rf $(PROJECT_NAME)_$(BUILD_NUMBER)'
	ssh $(HOST) -p $(PORT) 'mkdir $(PROJECT_NAME)_$(BUILD_NUMBER)'
	scp -P $(PORT) docker-compose-production.yml $(HOST):$(PROJECT_NAME)_$(BUILD_NUMBER)/docker-compose-production.yml
	scp -P $(PORT) .env.prod $(HOST):$(PROJECT_NAME)_$(BUILD_NUMBER)/.env
	ssh $(HOST) -p $(PORT) 'cd $(PROJECT_NAME)_$(BUILD_NUMBER) && echo "COMPOSE_PROJECT_NAME=$(PROJECT_NAME)" >> .env'
	ssh $(HOST) -p $(PORT) 'cd $(PROJECT_NAME)_$(BUILD_NUMBER) && echo "REGISTRY=$(REGISTRY)" >> .env'
	ssh $(HOST) -p $(PORT) 'cd $(PROJECT_NAME)_$(BUILD_NUMBER) && echo "IMAGE_TAG=$(VERSION)" >> .env'
	ssh $(HOST) -p $(PORT) 'cd $(PROJECT_NAME)_$(BUILD_NUMBER) && docker compose -f docker-compose-production.yml pull'
	ssh $(HOST) -p $(PORT) 'cd $(PROJECT_NAME)_$(BUILD_NUMBER) && docker compose -f docker-compose-production.yml down'
	ssh $(HOST) -p $(PORT) 'cd $(PROJECT_NAME)_$(BUILD_NUMBER) && docker compose -f docker-compose-production.yml up -d'
	ssh $(HOST) -p $(PORT) 'rm -f $(PROJECT_NAME)'
	ssh $(HOST) -p $(PORT) 'ln -sr $(PROJECT_NAME)_$(BUILD_NUMBER) $(PROJECT_NAME)'

rollback:
	ssh $(HOST) -p $(PORT) 'cd $(PROJECT_NAME)_$(BUILD_NUMBER) && docker compose -f docker-compose-production.yml pull'
	ssh $(HOST) -p $(PORT) 'cd $(PROJECT_NAME)_$(BUILD_NUMBER) && docker compose -f docker-compose-production.yml up --build --remove-orphans -d'
	ssh $(HOST) -p $(PORT) 'rm -f $(PROJECT_NAME)'
	ssh $(HOST) -p $(PORT) 'ln -sr $(PROJECT_NAME)_$(BUILD_NUMBER) $(PROJECT_NAME)'

list-v:
	ssh $(HOST) -p $(PORT) 'ls -lht $(PROJECT_NAME)_* 2>/dev/null || echo "Версии не найдены"'
	ssh $(HOST) -p $(PORT) 'readlink $(PROJECT_NAME) 2>/dev/null || echo "Символическая ссылка не найдена"'

clean-v:
	ssh $(HOST) -p $(PORT) 'cd ~ && ls -dt $(PROJECT_NAME)_* | tail -n +$$((${KEEP_VERSIONS} + 1)) | xargs -r rm -rf'

composer-init: composer-install

migrations-diff:
	docker compose run --rm php-cli composer app -- migrations:diff

migrations-migrate:
	docker compose run --rm php-cli composer app -- migrations:migrate --no-interaction

composer-update:
	docker compose run --rm php-cli composer update $(PACK)

composer-install:
	docker compose run --rm php-cli composer install

composer-dump-autoload:
	docker compose run --rm php-cli composer dump-autoload

composer-add:
	docker compose run --rm php-cli composer require $(PACK)

composer-remove:
	docker compose run --rm php-cli composer remove $(PACK)

frontend-install:
	docker run --rm -v "$$PWD":/app -w /app node:20-alpine npm ci

frontend-dev:
	docker run --rm -it -v "$$PWD":/app -w /app -p 5173:5173 node:20-alpine sh -lc "npm ci && npm run dev -- --host 0.0.0.0"

frontend-check:
	docker run --rm -v "$$PWD":/app -w /app node:20-alpine sh -lc "npm ci >/dev/null && npm run check"

frontend-build:
	docker run --rm -v "$$PWD":/app -w /app node:20-alpine sh -lc "npm ci >/dev/null && npm run build"

test-hello:
	docker compose run --rm php-cli composer app hello

help:
	@echo "======================================"
	@echo "-DEVELOPMENT:"
	@echo "  make init              		- Инициализация проекта dev-разработки"
	@echo "  make restart           		- Рестарт dev-контейнеров"
	@echo "  make lint           			- Проверка PHP и frontend-кода"
	@echo "  make frontend-dev      		- Vite dev server"
	@echo "  make frontend-check    		- TypeScript + ESLint + Vite build"
	@echo "  make frontend-build    		- Собрать assets в public/build"
	@echo ""
	@echo "-MIGRATIONS (APP_ENV=dev):"
	@echo "  make migrations-diff     		- Сгенерировать миграцию по отличию сущностей от БД"
	@echo "  make migrations-migrate  		- Выполнить накопленные миграции"
	@echo ""
	@echo "-COMPOSER:"
	@echo "  make composer-update          	- обновить все пакеты"
	@echo "  make composer-install          - установить по composer.lock"
	@echo "  make composer-dump-autoload    - перестроить автозагрузчик"
	@echo "  make composer-add PACK=	    - добавить зависимость"
	@echo "  make composer-remove PACK=		- удалить зависимость"
	@echo ""
	@echo "-PRODUCTION:"
	@echo ""
	@echo "--Registry-command:"
	@echo "  make docker-login      		- Авторизация локального докера в registry"
	@echo "  make server-login      		- Авторизация удаленного докера в registry"
	@echo ""
	@echo "--Project-command:"
	@echo "  make build             		- Собрать production образы"
	@echo "  make push              		- Загрузить образы в Registry"
	@echo "  make deploy            		- Деплой на сервер"
	@echo "  make rollback          		- Откат на предыдущую версию"
	@echo "  make list-v            		- Показать список версий на сервере"
	@echo "  make clean-v           		- Очистка старых версий"
	@echo "======================================"

.PHONY: help init restart docker-up docker-down docker-clear docker-build \
        ansible-build ansible-setup ansible-clean \
        docker-login server-login build push deploy rollback clean-v list-v \
        frontend-install frontend-dev frontend-check frontend-build
