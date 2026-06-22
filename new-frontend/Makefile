init: docker-clear docker-build docker-up composer-install frontend-install frontend-build
restart: docker-down docker-up
lint: php-linter psalm frontend-check audit
fix: php-linter-fix

docker-up:
	docker compose up -d nginx php-fpm php-cli

docker-down:
	docker compose down --remove-orphans

docker-clear:
	docker compose down -v --remove-orphans

docker-build:
	docker compose build --pull nginx php-fpm php-cli

php-linter:
	docker compose run --rm php-cli composer lint
	docker compose run --rm php-cli composer rector -- --dry-run
	docker compose run --rm php-cli composer php-cs-fixer -- --dry-run --diff

php-linter-fix:
	docker compose run --rm php-cli composer rector
	docker compose run --rm php-cli composer php-cs-fixer

psalm:
	docker compose run --rm php-cli composer psalm

audit:
	docker compose run --rm php-cli composer audit --locked
	docker run --rm -v "$$PWD":/app -w /app node:20-alpine sh -lc "npm ci >/dev/null && npm audit --audit-level=high"

composer-install:
	docker compose run --rm php-cli composer install

composer-update:
	docker compose run --rm php-cli composer update $(PACK)

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

help:
	@echo "======================================"
	@echo "Slim frontend"
	@echo ""
	@echo "  make init                  - Собрать Docker, установить Composer/NPM и собрать React assets"
	@echo "  make restart               - Перезапустить контейнеры"
	@echo "  make lint                  - PHP/TS проверки, dependency audit и frontend build"
	@echo "  make fix                   - Rector + PHP CS Fixer"
	@echo "  make audit                 - Composer/NPM dependency audit"
	@echo "  make frontend-dev          - Vite dev server"
	@echo "  make frontend-check        - TypeScript + ESLint + Vite build"
	@echo "  make frontend-build        - Собрать React islands в public/build"
	@echo "  make composer-install      - Composer install в php-cli"
	@echo "======================================"

.PHONY: help init restart docker-up docker-down docker-clear docker-build \
        lint fix php-linter php-linter-fix psalm audit composer-install composer-update \
        composer-dump-autoload composer-add composer-remove frontend-install \
        frontend-dev frontend-check frontend-build
