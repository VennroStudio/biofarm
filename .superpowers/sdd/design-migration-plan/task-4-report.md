# Task 4 — Entire admin application

## Coverage

- Reviewed all 75 files under `new-backend/assets/react/admin/**/*.tsx` against `admin-source-baseline.json`.
- Updated the shared admin shell and UI primitives used by all 16 route pages: layout, login, page headings, cards, fields, buttons, badges, alerts, empty states, tables, search and modal.
- Audited every feature area: dashboard, products and product groups, categories, attributes and values, pages, orders, promo codes, certificates, FAQ, blog, reviews, users, withdrawals, integration errors and all seven settings sections.
- Applied the approved white, ink and mint palette across every old beige color reference in admin TSX sources. Semantic success, warning, information and destructive colors remain distinct.
- Preserved all existing API calls, routes, permissions, CRUD actions, forms and field declarations.

## Responsive and accessibility behavior

- Desktop uses a compact light mint sidebar, restrained active state and a 1280px content container.
- Mobile uses a drawer with Escape close, focus containment, body scroll lock and focus restoration.
- Shared modal now provides Escape close, focus containment, body scroll lock, trigger focus restoration, compact mobile spacing and a viewport-safe scroll area.
- Data tables retain deliberate horizontal scrolling on small screens and use compact row spacing.
- Login and navigation render the real configured brand logo supplied by Twig data attributes from `site_setting`.

## Content alignment

- `home_features_enabled` keeps its existing key and API contract, while its admin label now describes the cooperation and partner form section used by the redesigned homepage.
- No public templates were changed except the admin Twig mount page needed to provide the existing brand name and logo configuration.

## Verification

- `git diff --check`
- Full Node 22 Docker check: TypeScript, ESLint and Vite production build.
- Source audit: 75 TSX files present; no old beige palette references remain.

## Review fixes

- Applied the established public-header green filter to the configured real logo on login, desktop sidebar and mobile header so the transparent white asset remains visible on light surfaces.
- Darkened green, amber and blue badge foregrounds, the secondary button foreground, sidebar email and inactive withdrawal tabs to keep small text above the WCAG AA 4.5:1 contrast threshold on their actual backgrounds.
