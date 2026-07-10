# Biofarm Backend

Slim backend application that now serves the Biofarm public frontend directly with Twig, Tailwind/Vite assets and small React islands.

## Stack

- PHP 8.4, Slim 4, PHP-DI
- Doctrine ORM and migrations for the future database migration
- Twig 3 server-rendered pages
- Vite, Tailwind, TypeScript and React islands
- Docker development and production images
- Psalm, Rector, PHP-CS-Fixer, PHPLint and ESLint

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

## Frontend Rendering

The public site is rendered by backend web controllers:

- `src/Http/Web` contains HTTP controllers.
- `src/Http/Unifier` builds page view objects for Twig.
- `src/Http/View` contains explicit Twig page contracts.
- `templates/pages` defines page composition.
- `templates/sections` contains large page/domain sections.
- `templates/widgets` contains reusable composite domain blocks.
- `templates/components` contains reusable layout, UI and domain blocks.
- `assets/react/{components,sections,widgets,pages}` mirrors Twig ownership for isolated React islands.

The old standalone frontend API-client approach is intentionally not used here. Page data should be loaded through backend modules, repositories, queries and unifiers after the database migration is done.

## Assets

Vite writes hashed assets and a manifest to `public/build`. Twig resolves files with:

```twig
{{ vite_asset('assets/styles/app.css') }}
{{ vite_asset('assets/react/mount.tsx') }}
```

Run `make frontend-build` before serving Twig pages locally if `public/build` is missing.

## Runtime Endpoints

- `GET /healthz` returns service status.
- `GET /readyz` verifies runtime prerequisites such as Vite manifest assets.

## Quality Gate

The expected full local check is:

```bash
make lint
```

The frontend-only check is:

```bash
make frontend-check
```

