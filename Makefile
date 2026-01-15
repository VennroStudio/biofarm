.PHONY: app-db-validate-schema app-db-migrations-diff app-db-migrations app-db-fixtures

app-db-validate-schema:
	docker-compose exec backend php bin/migrations.php orm:validate-schema

app-db-migrations-diff:
	docker-compose exec backend php bin/migrations.php migrations:diff

app-db-migrations:
	docker-compose exec backend php bin/migrations.php migrations:migrate --no-interaction

app-db-fixtures:
	docker-compose exec backend php bin/load-fixtures.php

build-app:
	docker-compose exec frontend sh -c "cd /app && npm run build"