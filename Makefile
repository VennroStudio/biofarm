.PHONY: app-db-validate-schema app-db-migrations-diff app-db-migrations app-db-fixtures rebuild-frontend rebuild-backend rebuild build-frontend-prod

app-db-validate-schema:
	docker-compose exec backend php bin/migrations.php orm:validate-schema

app-db-migrations-diff:
	docker-compose exec backend php bin/migrations.php migrations:diff

app-db-migrations:
	docker-compose exec backend php bin/migrations.php migrations:migrate --no-interaction

app-db-fixtures:
	docker-compose exec backend php bin/load-fixtures.php

rebuild-frontend:
	docker-compose build --no-cache frontend
	docker-compose up -d frontend

rebuild-backend:
	docker-compose build --no-cache backend
	docker-compose up -d backend

rebuild: rebuild-backend rebuild-frontend

build-frontend-prod:
	docker-compose exec frontend sh -c "cd /app && VITE_API_URL=https://api.biofarm.store npm run build"

build-app:
	docker-compose exec frontend sh -c "cd /app && npm run build"