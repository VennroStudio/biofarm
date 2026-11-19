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

## Application Data Cache

Set `REDIS=false` in the backend `.env` to disable application data caching. Reads
go directly to MySQL; Redis connection settings and the PHP Redis extension are
not required. This is suitable for hosting without Redis.

Set `REDIS=true` to enable the existing Redis cache. Install the PHP `redis`
extension and configure `REDIS_HOST`, `REDIS_PORT`, and optional `REDIS_USER` and
`REDIS_PASSWORD`. Connection failures are reported rather than silently disabling
caching. If `REDIS` is absent, it defaults to `true` for existing installations.

This switch does not disable PHP OPcache, browser caching, or Doctrine metadata
caching. Orders, balances and authentication tokens remain stored in MySQL.

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

## Ozon Seller catalog

Apply migrations and configure the `OZON_*` variables from `.env.example`.
Register the exact `OZON_REDIRECT_URI` in the Ozon private app, then open
**Настройки → Интеграции → Ozon Seller** and connect the store. The first
**Синхронизировать каталог** request archives the old assortment and starts daily
sync. Later runs keep local IDs, orders and catalog relationships outside Ozon
groups. Photos are imported when a card is created in Biofarm. Later catalog
updates preserve its cover and gallery. The admin «Тестирование» switch does
not affect catalog sync or stock notifications.

In local Docker development start the worker in a separate terminal:

```bash
docker compose exec php-fpm php bin/sync-ozon.php --watch
```

On shared hosting, schedule `php /path/to/new-backend/bin/sync-ozon.php` once per
minute. It processes queued requests and FBO notifications on each run. It
performs one full sync per day from 03:00 in `OZON_SYNC_TIMEZONE` and retries
failed daily runs after 15 minutes. A single worker lock prevents overlap.

To receive stock changes, set a random `OZON_WEBHOOK_SECRET`, use an HTTPS
frontend URL, and register the address displayed in the Ozon Seller card in the
Ozon notification settings. Subscribe to `TYPE_FBO_STOCKS_CHANGED`. Treat the
address as a credential. Notifications queue a stock refresh via Seller API.

## Ozon Delivery to pickup points

Keep `OZON_DELIVERY_ENABLED=0` until the store is ready to accept orders. Enable
it on the target environment to use the Seller API checkout. Reconnect the
Ozon private application with catalog, logistics, FBO posting and returns
permissions. Enter the live YooKassa and SMS Aero settings and enable YooKassa
receipts. The admin «Тестирование» switch disables new Ozon pickup orders and
phone verification. Test payments are confirmed without a debit. With missing
SMS Aero credentials, buyers see the phone verification availability message
and can sign in by email.

Shared-hosting releases require `APP_ENV=prod` and `APP_DEBUG=0`.
Production payment responses must identify a live YooKassa payment.

For marked products use a registered FFD 1.2 cash register,
`YOOKASSA_FFD12_ENABLED=1` and the cash register's
`YOOKASSA_RECEIPT_TIMEZONE`. Check the VAT and taxation settings with the
configured cash register. Enable order emails and verify SMTP to send orders
and written return instructions.

After applying database migrations, populate the pickup directory and schedule
its refresh daily. Run the order worker continuously to reconcile payments,
dispatch paid orders, check handover and cancellation, and send fiscal operations.
Run the existing catalog worker separately for prices, stock and catalog updates.

```bash
php bin/process-ozon-orders.php --refresh-points
php bin/process-ozon-orders.php --watch
php bin/sync-ozon.php --watch
```

The checkout creates one storefront order, one payment and one Ozon order.
When Ozon splits the cart into FBO shipments, the worker tracks each shipment
and issues a settlement receipt for the items actually handed over in it.
After all shipments reach a final state, the worker refunds any unissued items.
If Ozon times out during order creation, use the order's payment tab to
reconcile the result before retrying or returning money. For pre-handover
cancellations the worker waits for Ozon to confirm before refunding.

Local integration tests with simulated provider responses

```bash
docker compose exec php-fpm php tests/ozon-catalog.php
docker compose exec php-fpm php tests/ozon-delivery-http.php
```

The integration tests use connection-local temporary tables on the test
server. Before opening sales, verify a controlled pickup purchase
with live credentials and a real registered cash register through handover,
both receipts, cancellation and a refund.
