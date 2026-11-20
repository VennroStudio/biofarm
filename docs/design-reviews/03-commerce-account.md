# Итоговый статус: APPROVED

Все замечания этого этапа закрыты и повторно проверены. Ниже сохранена история ревью.

---

# Task 3 review — commerce, authentication and account

## Verdict

CHANGES REQUIRED.

The diff preserves the reviewed API calls, feature gates, form fields, cart calculations, order data rendering and account actions. `git diff --check 31bb40e..6abfb10` passes. The review found three accessibility/layout regressions that block approval.

## Findings

### [HIGH] Fixed header covers the start of every Task 3 page

Files/lines:

- `new-backend/templates/layouts/main.html.twig:53-56`
- `new-backend/templates/components/layout/header.html.twig:2,6`
- `new-backend/assets/react/pages/cart/CartPage.tsx:34,68`
- `new-backend/assets/react/pages/checkout/CheckoutPage.tsx:173`
- `new-backend/assets/react/pages/auth/LoginPage.tsx:75`
- `new-backend/assets/react/pages/order-success/OrderSuccessPage.tsx:10`
- `new-backend/assets/react/pages/profile/ProfilePage.tsx:210,221`
- `new-backend/templates/pages/auth/email-verification.html.twig:7`
- `new-backend/templates/pages/cart/unavailable.html.twig:4`

`header.html.twig` still positions an `h-20` header as `fixed`, while `layouts/main.html.twig` gives `<main>` no top offset. Task 3 removed the compensating `pt-24`/`pt-28`/`pt-32` from every React page and the equivalent Twig-only states. Root browser verification on the 390 px checkout measured `h1` at top 39–75 px while the header ended at 81 px, so the heading was fully covered. The same structural condition applies to cart (including empty), login, order success, profile (including loading), email verification and ordering-unavailable states.

Add one shared offset for the fixed 80 px header (preferably on the shared `<main>`) and keep page-local spacing measured from below that offset. Verify the fallback Twig markup as well as the mounted React state.

### [MEDIUM] Profile tabs do not implement keyboard tab behavior and lose their mobile names

Files/lines:

- `new-backend/assets/react/pages/profile/ProfilePage.tsx:239-268,270-314`
- `new-backend/assets/react/pages/profile/components/ProfileTabs.tsx:4-17`

The change adds `role="tablist"`, `role="tab"` and `aria-selected`, but all tabs remain in the normal Tab sequence and there is no ArrowLeft/ArrowRight/Home/End handling, roving `tabIndex`, `aria-controls`, associated `role="tabpanel"`, or focus transfer when keyboard selection changes. This creates an incomplete ARIA tabs widget. In addition, every visible text label is `hidden sm:inline`; at widths below `sm` the icon-only tab buttons have no explicit accessible name.

Implement the complete tab keyboard contract and tab/panel relationships, or use ordinary buttons/navigation without tab roles. Keep an explicit accessible name for each icon-only mobile control.

### [MEDIUM] Initial Shift+Tab escapes `OrderDetailsDialog`

File/lines:

- `new-backend/assets/react/pages/profile/components/OrderDetailsDialog.tsx:17,29-44,67-77`

On open, focus is placed on the dialog container (`tabIndex={-1}`), not on the first interactive element. The trap only wraps backward when `document.activeElement === first`. Therefore the first keypress `Shift+Tab` from the initially focused container follows the browser's document order to an element outside the modal. This contradicts the claimed focus containment while `aria-modal="true"` is active.

Move initial focus to the Close button (or another intentional control), or explicitly handle the container as the pre-first position so initial `Shift+Tab` wraps to the last focusable element. Retain Escape, body scroll restoration and trigger focus restoration.

## Preserved behavior reviewed

- No endpoint, payload, cart storage/event, redirect or feature-gate changes were introduced by this diff.
- Checkout still exposes the six required contact/address fields, comment, saved addresses, CDEK/Post, card/SBP, optional promo and authenticated bonus controls, and passes the same values to `createOrder`.
- Cart quantity/removal, totals, delivery threshold/CDEK price and order-bonus presentation remain intact.
- Login/register and `registration_enabled` behavior remain intact; no password-reset flow was invented.
- Profile orders, addresses, favorites, referral and withdrawal paths and gates remain present.
- Email verification keeps success, error and missing-token branches; order success still reads the query-string order ID.
- The responsive content grids and wrapped tab strip are compact, but the header overlap must be fixed before visual layout can be accepted.

## Review scope

Read-only source/diff review of `31bb40e..6abfb10`; no browser automation and no redundant full suite were run. Root supplied the live checkout header measurement above.

---

## Fix re-review — `6abfb10..4edb87f`

APPROVED.

All three findings above are resolved in the scoped fix diff, and no new blocking regression was found.

- Every affected React state and Twig fallback/standalone state now includes the fixed `h-20` header in its top spacing. Standard pages use `120px` on mobile and `128px` on desktop (`80px` header plus the specified `40px`/`48px` content spacing); centered states use `128px`. The fix does not add a global offset that could double-space other tasks.
- Profile tabs now have stable tab/panel IDs, `aria-controls`/`aria-labelledby`, roving `tabIndex`, ArrowLeft/ArrowRight/Home/End navigation with selection and focus movement, and `sr-only sm:not-sr-only` labels that remain accessible in the icon-only mobile layout. Feature-gated tabs are discovered from the rendered tablist, so keyboard wrapping follows the actual enabled set.
- `OrderDetailsDialog` now focuses the marked Close button on open. It is the first focusable element, so initial `Shift+Tab` reaches the trap's backward-wrap branch and moves to the last focusable control; forward Tab and focus restoration remain intact.
- Rendering inactive panels with the native `hidden` attribute keeps them out of layout, focus navigation and the accessibility tree. The panel components contain no mount-time effects or background writes, so keeping them mounted does not introduce an API or data mutation.
- The fix diff changes presentation and accessibility wiring only; no commerce/auth/account endpoint, payload, feature gate, calculation or mutation handler changed.

Verification evidence reviewed: `git diff --check 6abfb10..4edb87f` passes. The task report records a successful final Node 22 Docker `npm run check` (TypeScript, ESLint and Vite; 1904 modules, 1.81s). Per scope, this re-review did not rerun the full suite or browser automation; root owns final visual verification.
