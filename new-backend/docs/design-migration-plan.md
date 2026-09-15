# Full site mint redesign — 2026-09-16

## Specification and global constraints

User approved `design-previews/biofarm-v11/index.html` and `style.css`. Implement that compact, light mint design across EVERY public, commerce, account, CMS and admin screen. Source truth is the existing dynamic application and original assets. No lost sections, fields, filters, actions, settings, integrations or SEO. Real logo is `site.brand.logoUrl` (`/uploads/images/logo.png`), never substitute a leaf. Preserve actual packaging and dynamic product destinations. No invented content, ratings, discounts or customer claims. Preserve current backend/API/form contracts, CSRF, feature flags and React island architecture. Follow `docs/frontend-ui/agent-rules.md` and template rules; Twig uses Tailwind utilities, global tokens in `assets/styles/tailwind.css`.

Visual contract: white background, ink #294555, mint heading #2e8175, deep green #18574f, muted #718894, border #dfece9, pale panel #eaf5f1. Sans rounded lightweight headings (Avenir Next/Segoe UI fallback), compact 30px mobile / 40–48px desktop section spacing, 1280px max container, 12–18px corner radii. No large empty bands, brown/serif styling, heavy gradients or excessive bold headings. Retain readable contrast/focus and reduced motion. Homepage matches v11 section composition; preserve additional existing about/contact information as compact sections.

Hero three real products each separately linked. Documents remain visible in compact horizontal rail. Reviews show full text with small 1–2 photo attachments; no separate read-more page. Marketplace WB lavender / Ozon blue parcel cards without benefits list. Separate loyalty facts, prominent partner form with name/email/message required and phone optional. No public favorite/share additions. Existing account favorites functionality remains because user requires preserving all account blocks.

## Task 1: Shared foundation and complete homepage

Implement global theme tokens/fonts/containers, header/footer/shared UI and complete home with approved visual contract. Read v11 HTML/CSS and existing Twig/data/features first. Use real logo with appropriate contrast (source is white on transparent). Retain all header navigation, CMS links, mobile menu, cart counter; expose account entry. Hero/production/parcel decorative assets may be copied from v11 into `public/assets/images/design/`; accurate original product media remains preferred for labeling. Preserve all home feature flags, dynamic products/reviews/blog/certificates, partner POST form fields, validation states, media viewer/video behavior. Changes may include Home view/unifier for dynamic hero products, but do not hardcode stale prices/content. Shared product/review/blog cards may be redesigned here. Production and documents adjacent; marketplace cards match v11; partner CTA unmistakable. Review every modified file. Run frontend check and render home/template check. Report files/verification/remaining concerns.

## Task 2: All public inner pages and content states

Apply shared theme with deliberate layouts to catalog and every filtered route (categories/components/purposes/search/sort/pagination), product detail/variants/attributes/media/related products/articles/certificates, blog listing/post, certificate list, FAQ, loyalty, all CMS basic/landing/legal, privacy, oferta, not-found. Preserve every block and action/dynamic content. No mechanical recolor only: compact title bands, clear information hierarchy, cards/accordions/sidebars suited to same mint design. Reuse Task 1 shared cards. Review template inventory against coverage. Run frontend check, Twig compile/render checks and HTTP page matrix. Root browser checks follow.

## Task 3: Commerce, authentication and personal account

Redesign all React and Twig cart/checkout/order-success/unavailable/login/register/email verification/profile states, site UI primitives and all profile tabs/dialogs. Preserve state, validation, submission contracts, shipping/payment/bonus/promo handling, address/order/referral/favorite functions and authentication. Account gets clear tabs, concise information cards and accessible responsive forms. Do not send email, place real orders or modify account data just for visual tests. Run frontend check and review every source component; root browser verifies login, navigation, cart and validation.

## Task 4: Entire admin application

Redesign AdminLayout/login/dashboard and all 16 route pages, settings sections, tables, filters, loading/empty/error states, form modals, uploaders and shared UI. Real logo, light mint sidebar, clear active state, restrained semantic badges, compact accessible tables/forms. All admin CRUD fields/actions remain available. Handle small screens with intentional horizontal table scrolling and responsive drawers/forms. Preserve routing, API, permissions and actual content. Run frontend check; review all admin source paths with coverage list. Root browser walks all routes/settings and representative dialogs without saving destructive/business changes.

## Task 5: Integration and whole-site review

Review each task diff for visual/spec and behavioral regressions. Build, TypeScript, ESLint, all Twig compile, HTTP matrix from sitemap and route config. Browser desktop/mobile: full home sections, category/search/filter, product options/cart, checkout validation, login/account tabs, CMS/legal/blog/documents, all admin routes/settings and dialogs. Check missing/broken media, console errors, overflow, focus/navigation, logo, documents, full review text, every form field. Fix findings and re-review. Record evidence in `docs/design-migration-review.md`. Task complete ONLY after whole-site coverage and no unresolved load-bearing findings.

## Execution decisions

- Work on branch `codex/full-site-mint-redesign` in the current checkout so the existing Docker bind mount continues to serve changes. No commits to main, no push/deploy.
- Preserve untracked approved `design-previews/`; application commits must not accidentally include it.
- One implementation agent at a time, independent root inventory/browser/verification alongside it. Review each task before next implementation task. No worker-spawned agents.
- External references checked: MDN keyboard accessibility and prefers-reduced-motion documentation on 2026-09-16.

## Final status

All five tasks completed. See [design-migration-review.md](design-migration-review.md) for route, template, field, media, browser and independent review evidence. Readability refinement uses #586e79 for light-theme muted text. Every review attachment is preserved;1–2 was the description of current data, not an imposed maximum.
