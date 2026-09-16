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

## Follow-up — fluid page width and review alignment (2026-09-16)

- Removed the shared 1280px container limit and explicit outer page limits in commerce, profile, editorial, FAQ, loyalty and legal templates. Admin content fills the area beside its sidebar. Page gutters remain; individual controls, media and dialogs retain their own sizing.
- Review rows now stretch cards to the tallest visible review. Cards use a flex column so the existing `mt-auto` photo row aligns at the bottom. Explicit `[hidden]` handling keeps inactive carousel cards hidden after adding flex display. Review text, images and carousel behavior are unchanged.
- Before: at 1280px viewport, the first three card heights were 259.5 / 282.25 / 327.75px. After: at 1920px, all three were 282.25px with matching photo positions; after advancing, alignment remained equal. At 1440px, the next three cards were all 305px (rounded DOM measurements).
- Browser checks at 390px: cards fit within page gutters; no section/header/footer exceeded the viewport. Next control and photo lightbox open/close work. At 1920px, homepage, delivery, privacy, login and authenticated profile containers fill the available 1905px document width; admin content spans 1664px beside its 256px sidebar.
- `npm run check` passes TypeScript, ESLint and production build. All 68 Twig templates compile; `git diff --check` passes. Existing production image build warning remains a runtime asset reference.
- Layout references checked: [Tailwind 3 container](https://v3.tailwindcss.com/docs/container), [CSS align-items](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/align-items).

## Follow-up — company video panel (2026-09-16)

- Replaced the plain `#about` text row with a mint split panel, forest video preview, production/documents links and a separate raw-materials strip. Both original paragraphs remain. Shared fluid page width is unchanged.
- Uses the existing `/assets/images/home/hero-poster.jpg` and `/uploads/videos/biofarm.mp4` also referenced by the approved v11 prototype. Extended the home video island to support native video alongside its existing iframe mode; no media download until playback (`preload="none"`).
- Browser: native video reached `readyState=4`, duration 65.24s and advancing playback time. Closing pauses and resets playback and restores trigger focus. Existing production iframe modal still opens independently. At 390px, panel descendants fit the viewport and the play button remains unobstructed; the modal fits the screen. Desktop layout visually checked at 1280px.
- TypeScript, ESLint, production build, modified Twig compilation and `git diff --check` pass. Local video supports HTTP 206 range responses.

## Follow-up — catalogue, product and editorial composition (2026-09-16)

- Catalogue: split heading/intro panel, category/count rail above the products, compact ingredient/purpose sidebar with an accessible mobile toggle, separate search/sort/view toolbar, fluid 2/3/4-column cards. Product cards rebuilt with framed images, weight badges, clearer category/title/description/price hierarchy and distinct cart controls; list layout redesigned too. Removed the unrelated farm/pesticide claim from the default catalogue intro; copy now describes the actual extract/capsule categories.
- Product: framed gallery with horizontal thumbnails; identity/variants/features separate from purchase panel on desktop; anchored description and ingredient/use/contraindication panels below. All previous product field references and interaction data selectors retained (source comparison). Documents, related products, articles and FAQ remain composed by the page.
- Blog: magazine headline, category/search toolbar, large lead story beside secondary cards, remaining articles below. Article: split photo/title/excerpt hero, author/navigation sidebar, full content panel and related materials. Server rendering retains all 6 real articles exactly once, article HTML and all related links; later-page pagination also checked by the editorial agent.
- Favorites restored by explicit user request (supersedes prior removal). Shared heart component on grid/list/home/related cards and product purchase panel. Active fill/text plus visible success/removal/error notice, polite live region, dismiss button and account link. Buttons wait for initial saved state before accepting clicks. The existing guest/account storage and API behavior stay unchanged.
- Independent read-only review found an invisible desktop filter-toggle tab stop; fixed by hiding the checkbox itself at lg+. No product fields or filter query/link contracts were lost in the reviewed changes.
- Browser QA: desktop 1280 and 1920, mobile 390. All four page types fill available width with no section/article/heading overflow. Mobile filters open/close, ingredient filter returns 4 products, reset and grid/list links work; ascending sort starts 509/579/651/717. Blog search for Abisib returns the matching article; article anchor navigation works. Product quantity changes 912 to 1824 then back, thumbnails and lightbox work.
- Favorites QA: adding shows filled heart, “В избранном” and “Добавлено в избранное”; account Favorites tab shows the same product. Removing shows “Удалено из избранного”; dismiss closes notice. QA product removed afterwards to restore its original state, existing other saved items preserved.
- Final `npm run check`: TypeScript, ESLint and Vite build pass. All 70 Twig templates compile, PHP unifier syntax check and `git diff --check` pass. HTTP audit of 118 sitemap catalogue/product/blog routes plus homepage: 118 HTTP 200, no Twig/Slim error pages.
- Accessibility reference consulted: [MDN status role](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Roles/status_role).

## Follow-up — purpose tiles and ingredient dialog (2026-09-16)

- Removed the catalogue sidebar; product cards now use the full content width (four columns at 1280px). Category links, search, sorting, grid/list view, pagination and favorite controls remain.
- Shared purpose tiles appear above products on the homepage and catalogue. They use all 13 real purpose facets, their product counts and individual line icons. Homepage tiles link to the corresponding purpose landing pages; catalogue tiles preserve the current ingredient, search, sort and view. The active purpose is highlighted and can be cleared independently.
- The “Состав” button opens a native modal dialog with the existing single-component filter, explicit apply/reset controls, scrollable options and visible active-filter chips. Escape/close discard unapplied changes, reopening restores the server selection. Form action uses the category route without an embedded SEO facet so ingredient reset works after entering via a landing page.
- Selected facets remain removable when a search returns zero results. Applying a filter lands at the product toolbar rather than at the top of the purpose grid.
- Browser QA: ingredient returns 4 products; immunity returns 12; combined returns 1. Switching sort and list view retains both facets; resetting ingredient retains purpose/sort/view. Filtering capsules by rhodiola returns 1 product and retains the category. Homepage tile navigation and zero-result removal links verified.
- Responsive QA: 320/390px tiles have no horizontal overflow; mobile dialog fits the viewport, its options scroll independently and its apply button fills the footer width. Desktop dialog visually checked, and the homepage has seven tile columns at 1920px. Final viewport override reset.
- Validation: TypeScript, ESLint and Vite build pass; all 73 Twig templates compile; `git diff --check` passes. All 13 homepage purpose URLs return HTTP 200. Existing production-image build warning checked against the live asset (HTTP 200).
- Native dialog behavior reference: [MDN dialog](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/dialog).

## Follow-up — product tabs and document strip (2026-09-16)

- Product documents now reuse the homepage paper-preview strip through `widgets/certificate/strip.html.twig`. Product and home DTO differences handled explicitly: product name is optional; document titles, descriptions and file URLs are retained. Layout adapts to document count, with the same fluid page gutters.
- Replaced the product description/information anchor sidebar with horizontal tabs. Only the selected panel is shown; switching does not change the URL or intentionally scroll. All description HTML and ingredient/use/contraindication/country/shelf-life/storage/disclaimer bindings remain.
- Behavior-only product detail island provides active state, tab/tabpanel relationships, roving tabindex and ArrowLeft/ArrowRight/Home/End controls. Without JS both content sections remain visible. The existing “Подробнее о продукте” link selects description, and existing inbound panel hashes still reveal their panel.
- Browser checked at desktop, 390px and 320px: click and keyboard switching, one visible panel, active styling, long mobile tab label, document previews without overflow, description link and direct information hash. Viewport restored after QA.
- TypeScript, ESLint and Vite build pass; modified Twig templates compile; `git diff --check` passes. All 26 product routes and homepage return HTTP 200; referenced declaration/protocol PDF endpoints return HTTP 200.
- Keyboard pattern reference: [W3C APG Tabs](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/).

### Product tabs placement correction (2026-09-16)

- Restored the previous desktop side navigation (220px) beside one changing right-hand content area. Kept actual tab switching and all content/document changes.
- Desktop tab orientation and keyboard controls are vertical (Up/Down); below 1024px they remain horizontal (Left/Right).
- Verified desktop placement and switching, mobile 390px layout without tab overflow, and one visible panel. TypeScript, ESLint, build, Twig compilation and diff whitespace checks pass.

## Follow-up — account side tabs (2026-09-16)

- Personal account uses a 220px left tab menu and a flexible right content area on desktop, matching product side tabs. Summary cards remain above. All existing feature-gated panels and action handlers are retained.
- Mobile menu scrolls horizontally with visible text labels. Orientation switches with the 1024px breakpoint; Up/Down navigate desktop tabs, Left/Right mobile tabs, Home/End both. Keyboard focus brings offscreen mobile tabs into view.
- Verified authenticated test account: Profile, Orders, Addresses and Favorites each replace the right panel; exactly one panel is visible. Checked desktop geometry, keyboard switching, 390px menu width and mobile End-to-Favorites visibility. No profile data, orders, addresses or favorites were changed. Viewport reset and profile tab restored.
- TypeScript, ESLint, Vite build and `git diff --check` pass.

## Follow-up — immediate product cards (2026-09-16)

- Removed entry-reveal attributes from the shared product card and unused per-index delay arguments. Previous catalogue delays reached 1100ms before a 500ms opacity/transform transition; cards now render immediately. Hover color styling remains.
- Browser verified catalogue (12 cards), homepage (4 cards), and product related section: no product entry-reveal hooks; computed opacity 1, delay 0s, transform none in catalogue. Changed Twig templates compile and `git diff --check` passes. No JS/CSS changes required.

## Follow-up — immediate review carousel (2026-09-16)

- Removed the carousel's own entry animation, which restarted on every update with 200–500ms delays followed by a 600ms transition. Visible cards now switch immediately. Removed the review heading's scroll-reveal hook as well.
- Browser verified initial display, five successive next clicks including wraparound, previous and dot navigation: three visible cards, opacity 1, transition duration/delay 0s. Photo lightbox still opens and closes correctly.
- TypeScript, ESLint, Vite build, modified Twig compilation and `git diff --check` pass. Existing production-image build warning remains unrelated to this change.
