# Design migration review

Plan: [design-migration-plan.md](design-migration-plan.md)

## Baseline — 2026-09-16

- Branch: `codex/full-site-mint-redesign`; original application commit `8e92e3a`.
- `docker run --rm -v "$PWD/new-backend":/app -w /app node:22-alpine npm run check`: TypeScript, ESLint and production Vite build pass before edits.
- HTTP audit: sitemap plus login/profile/cart/checkout/order-success/email-verification/not-found/admin shells, **136 URLs**, 135 HTTP 200 and one intentional HTTP 404, no rendered exceptions.
- Browser: test customer login and separate admin login both work. Credentials are not stored in this document.
- Actual logo comes from `config/common/site.php` → `/uploads/images/logo.png`; white artwork on transparent background.

## Coverage ledger

| Area | Pages and states | Implementation | Review |
|---|---|---|---|
| Foundation | tokens, fonts, header, mobile menu, account/cart links, footer, shared cards, media modal | Done | Source review approved; desktop/mobile checked |
| Home | hero, categories/catalog, production/video, documents, blog, about, reviews/photos, marketplaces, loyalty, partners/form, contacts | Done | Reviewed; mobile blog snap rail also verified |
| Catalogue | listing, category/subcategory, component/purpose intersections, filters, sorting, search, pagination/empty state | Done | Separate review approved; desktop/mobile checked |
| Product | gallery, details, options, quantity/cart, description, attributes, related products/articles/documents | Done | Separate review approved; desktop/mobile checked |
| Editorial | blog list/post, certificates, FAQ, loyalty | Done | Separate review approved; desktop/mobile checked |
| CMS/legal | basic, landing, legal, delivery, payment, returns, consent, privacy, offer, 404 | Done | Separate review approved; desktop/mobile checked |
| Commerce | empty/populated cart, shipping/payment/promo/bonus checkout, success, unavailable | Done | Separate review approved; flows checked |
| Authentication | login, registration, errors, email verification | Done | Separate review approved; flows checked |
| Profile | summary, profile edit, orders/details, addresses/form, favorites, partner/referral/withdrawal states | Done | Separate review approved; flows checked |
| Admin shell | login, desktop/sidebar/mobile navigation, dashboard | Done | Separate review approved; routes/forms checked |
| Admin catalogue | products/groups and all editor tabs, categories, attributes/values, uploads | Done | Separate review approved; routes/forms checked |
| Admin operations | orders/details, promo codes, users/details, withdrawals, integration errors | Done | Separate review approved; routes/forms checked |
| Admin content | CMS pages, certificates/files, FAQ, blog/images, reviews/photos and all editors | Done | Separate review approved; routes/forms checked |
| Admin settings | features, home, SEO, integrations, orders/delivery, loyalty, security | Done | Separate review approved; routes/forms checked |

## Verification boundaries

Visual testing uses existing records and unsaved forms. No real order, partner message, withdrawal, account registration, password change or external CRM/email action is submitted for a cosmetic check. Feature-gated partner states and disabled service states are reviewed in source and component contracts unless reachable with the provided account. Actual test evidence is added below as each stage finishes.

## External implementation references

- [MDN keyboard accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility/Guides/Understanding_WCAG/Keyboard), checked 2026-09-16.
- [MDN reduced motion](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/At-rules/@media/prefers-reduced-motion), checked 2026-09-16.

## Stage 1 — foundation/home

Commits `60bc44c`, `5ffe51e`, `fe73ad1`. Separate source review approved after mobile density correction. Frontend check and all 68 Twig templates pass. Desktop1280: original logo and three independently linked packages, documents beside production, full review text/small photos, photo lightbox with Escape, video modal opening, partner required fields, marketplace cards. Add-to-cart changed badge0→1. Mobile390: menu opens and closes after anchor navigation; two product/category columns, three document columns; no page overflow at320. Existing third-party contact widget retained.

## Stage 2 — public inner pages

Commits `f6af9ef`, `31bb40e`. Separate review approved after correcting low-contrast product weight and catalog eyebrow. Frontend check passed; 136 HTTP routes retain baseline status/title/H1/forms. Browser: catalog list/grid, price sort, text search and ingredient filtering (four osina matches); product gallery open/close, variants and all content sections; blog post content and mobile snap rail; certificates, FAQ expand, loyalty, six CMS/legal pages. No page overflow at 320px catalog / 390px editorial and product. Review photo has visible close button; Tab remains in dialog, Escape closes, focus returns to thumbnail.

## Stage 3 — commerce/auth/account

Commits `6abfb10`, `4edb87f`. Review approved after correcting fixed-header overlap, complete profile tab keyboard/ARIA behavior, and initial Shift+Tab dialog containment. Frontend check passes. Browser: login works; registration preserves four fields; profile, orders/details, addresses/edit/cancel and existing favorites empty state checked. Checkout retains six required fields, saved address, comment, promo, two delivery/payment options and bonuses. Quantity 1→2→1 yields 1262→2174→1262 ₽ including CDEK; Post gives1162 ₽; using147bonuses gives1115 ₽ from1262 and unchecking restores total. No business submissions. Header bottom81px / checkout heading top128px after fix; mobile tabs named, arrow navigation activates/focuses, modal Shift+Tab stays inside, Escape closes and restores focus.

Media audit: 143 local image/document URLs return HTTP200.

Additional commerce checks: restored the originally empty local cart by removing the one QA item; empty checkout redirects to the empty cart. Review carousel next control changes the visible records. No browser console errors in the checked public tab.

## Stage 4 — entire admin

Commits `33c17f2`, `013b78f`. All75 TSX reviewed, all16 pages and7 settings sections. Separate review approved after real white logo visibility and small-text color fixes. Source audit preserves179/179 field declarations per file. Browser walked all route families/settings and unsaved editors (products/groups, categories, attributes/values, CMS, certificates, FAQ, blog, reviews, promos, orders, users). Product73fields/72labels, category11, attribute7, CMS20 match baseline exactly; settings field counts unchanged. Typing stays in the field; modal and drawer Shift+Tab/Escape/focus restoration/body lock work. Mobile390 product modal fits358×812 within viewport844; table738px scrolls inside293px container without page overflow. No settings or records were saved.

## Final integration checks

- Final pre-polish Node22 `npm run check` passes TypeScript, ESLint and Vite build (1904modules,2.04s).
- All68 Twig templates compile.
- 136 URLs:135HTTP200 and one deliberate404; no rendered exceptions. Baseline status/title/H1/form-action+method comparison has zero differences.
- All179 admin input/select/textarea declarations remain unchanged per source file.
- All143 local image/document URLs return200. The Vite warning for runtime-resolved production.webp is benign: that exact asset returns200 and appears in browser.
- Narrow320px admin route shells/settings fit the screen; populated product table was separately verified at390px with internal horizontal scroll. Actual admin logo now visible on login/sidebar/mobile header.
- Final independent review approved after two media-dialog keyboard fixes and darkening shared muted text.

## Completion

Final fix commit `d25feac`; independent scoped re-review APPROVED. Project-native `make frontend-check` passes TypeScript, ESLint and Vite build after final fixes. Root browser confirms video opens on Close, Shift+Tab enters its iframe, Tab returns to Close without reaching the page; Escape closes and restores the opener. Gallery wraps Shift+Tab/Tab, ArrowRight changes the rendered image after its transition, Escape restores the image opener. Light-theme muted text is #586e79 with contrast4.80:1 on mint,4.73:1 on lavender,4.90:1 on pale blue,5.36:1 on white. Temporary viewport override reset; actual homepage opened at http://localhost:8088/.

### Archived step reviews

- [Foundation and home](design-reviews/01-foundation-home.md)
- [Public inner pages](design-reviews/02-public-pages.md)
- [Commerce and account](design-reviews/03-commerce-account.md)
- [Admin](design-reviews/04-admin.md)
- [Whole site and final fixes](design-reviews/05-final.md)

### Execution decisions

- Used branch `codex/full-site-mint-redesign` in the existing checkout to preserve the running Docker bind mount. Changes are visible locally; no deployment or merge into main was performed.
- Preserved every existing review attachment. The user described current reviews as having1–2 photos; this was not treated as a data cap. A future review with many photos may need additional layout work.
- Kept existing account favorites while removing public favorite/share controls, as the full-site task requires retaining account functionality.
- Archived review outcomes here and removed only this task’s temporary review workspace after approval. Approved untracked `design-previews/` is untouched.
