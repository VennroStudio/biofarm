# Slim Frontend

Slim frontend renderer with Twig pages, typed API modules and React islands.

## Stack

- PHP 8.4, Slim 4, PHP-DI
- Twig 3 server-rendered pages
- Symfony HttpClient for upstream API calls
- Vite, TypeScript and React islands
- Docker development and production images
- Monolog request logging, request IDs and health endpoints
- Psalm, Rector, PHP-CS-Fixer, ESLint, dependency audits

## Quick Start

```bash
cp .env.example .env
make init
```

Open the application at `http://localhost:8088`.

Useful commands:

```bash
make restart
make lint
make frontend-dev
make frontend-build
```

## Environment

```dotenv
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=change-me-in-local-development
APP_VERSION=dev
APP_TIMEZONE=UTC
LOG_CHANNEL=slim-frontend
LOG_LEVEL=info
LOG_STREAM=php://stderr
API_BASE_URL=https://fakeapi.net
API_TIMEOUT=5
```

Use `APP_DEBUG=false` or `APP_DEBUG=0` in production. `APP_SECRET` must be changed for production deployments.

## Architecture

- `src/Http/Web` contains HTTP controllers.
- `src/Http/Unifier` builds page arrays for Twig.
- `src/Modules/{Entity}/Api` contains upstream API clients per domain.
- `src/Modules/{Entity}/Command` contains write scenarios and handlers.
- `templates/pages` defines page composition.
- `templates/sections` contains large page/domain sections.
- `templates/widgets` contains reusable composite domain blocks.
- `templates/components` contains reusable layout, UI and domain blocks.
- `assets/react/islands` contains isolated React islands mounted from Twig.

Twig never receives raw upstream JSON. Module APIs map data into response models, and invalid upstream payloads fail explicitly.

Controllers render through `App\Components\Twig\HtmlResponder`, so web actions do not depend on a concrete PSR-7 implementation.

## Assets

Vite writes hashed assets and a manifest to `public/build`. Twig resolves files with:

```twig
{{ vite_asset('assets/styles/app.css') }}
{{ vite_asset('assets/react/mount.tsx') }}
```

Run `make frontend-build` before serving Twig pages locally if `public/build` is missing.

## Forms

Write forms use HMAC CSRF tokens:

```twig
<input type="hidden" name="_csrf_token" value="{{ csrf_token('products.create') }}">
```

Controllers validate CSRF and form values before calling command handlers.

## Runtime Endpoints

- `GET /healthz` returns service status, environment and version.
- `GET /readyz` verifies runtime prerequisites such as Vite manifest assets.

Every response receives an `X-Request-Id` header. HTTP requests are logged to `LOG_STREAM` with method, path, status, duration and request id.

## Quality Gate

The expected full check is:

```bash
make lint
```

CI runs Composer validation, static PHP checks, frontend checks, dependency audits and production Docker builds.
