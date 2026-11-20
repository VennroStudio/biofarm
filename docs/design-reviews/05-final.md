# Итоговый статус: APPROVED

Все замечания этого этапа закрыты и повторно проверены. Ниже сохранена история ревью.

---

# Final whole-branch review — `8e92e3a..013b78f`

## Verdict

**CHANGES REQUESTED.**

The four implementation stages preserve the reviewed application surface: the 136-URL public/admin HTTP inventory, all 179 admin form-field declarations, existing backend/API/form contracts, feature-gated homepage sections, real configured logo, actual product destinations, full review text and attachments, account favorites, marketplace links, checkout/account/admin actions, and the Twig/React-island architecture. The approved public removal of favorite/share controls does not remove the existing account favorites flow. `git diff --check 8e92e3a..013b78f` passes.

Two modal dialogs still violate the keyboard-containment requirement explicitly included in Task 5, and the shared public muted-text token is too light on the redesign's pale panels. These user-reachable accessibility defects block final integration approval until they are fixed and rechecked.

## Critical

None.

## Important

### 1. Production-video modal lets keyboard focus escape the active dialog

- File: `new-backend/assets/react/sections/home/video.tsx:33-38,60-68`
- Dialog markup: `new-backend/templates/components/ui/modal.html.twig:8-25`

Opening the video focuses the non-interactive dialog container. The only document-level keyboard handling closes on Escape; there is no Tab/Shift+Tab containment. The root browser check confirmed that Shift+Tab from the initial focus leaves the dialog. Because the dialog is exposed as `aria-modal="true"`, background controls must not remain keyboard-reachable while it is open.

Focus an intentional control on open (the Close button is suitable), cycle Tab and Shift+Tab across the dialog's enabled focusable controls, and retain the existing Escape close, scroll lock, frame cleanup and trigger-focus restoration.

### 2. Product-gallery lightbox also lacks modal focus containment

- File: `new-backend/assets/react/components/product/gallery.ts:151-168,232-244`
- Dialog markup: `new-backend/templates/components/product/gallery.html.twig:88-107`

The lightbox focuses its outer container on open. Its key handler implements Escape and arrow navigation only; Tab and Shift+Tab can move to controls behind the `aria-modal="true"` overlay. This affects every product with an openable gallery and contradicts the final-plan requirement to verify focus/navigation across the whole site.

Focus the Close button (or another deliberate first control) when opening, trap forward and backward Tab navigation among the controls rendered for the current gallery, and preserve Escape, arrow navigation, body scroll lock and restoration to the gallery opener.

### 3. Shared public muted text fails AA contrast on pale redesign panels

- Token: `new-backend/assets/styles/tailwind.css:34-37`
- Confirmed consumers: `new-backend/templates/sections/home/loyalty.html.twig:1-16`
- Additional pale-card consumers: `new-backend/templates/sections/home/marketplaces.html.twig:10-12`

The global `--muted-foreground` resolves to `#5f7580`, while `--secondary` resolves to `#eaf5f1`. Their contrast is approximately **4.34:1**, below WCAG AA's **4.5:1** requirement for the 12px normal text used throughout the loyalty panel. The marketplace descriptions use the same foreground on similarly pale lavender/blue cards, so they need the same correction. Stage 4 already had to darken this exact semantic role in admin after identifying the same failure.

Darken the shared light-theme `--muted-foreground` to a verified value such as the admin's `#586e79` (approximately **4.93:1** on `#eaf5f1`), then recheck its pale lavender/blue and white-background uses. A shared-token correction is preferable because this is a global semantic color and many public 12–14px labels consume it.

## Minor

None.

## Reviewed preservation evidence

- The staged reviews cover foundation/home, all public inner pages, commerce/auth/account and all 16 admin route pages plus seven settings sections; their scoped findings were fixed and re-reviewed.
- Homepage feature gates remain present for catalog, video, certificates, blog, reviews, about, marketplaces, loyalty, partner form and contacts. Dynamic products, reviews, blog posts and documents remain backend-fed.
- Review cards render full text and every stored attachment. The prior 1–2-photo concern was correctly withdrawn because the user clarified that 1–2 describes current data rather than a cap.
- Public product favorite/share controls were intentionally removed, while `/profile` still receives and renders the `favorites_enabled` state and its existing favorites panel.
- Commerce/auth/profile source retains the six required checkout fields, delivery/payment/promo/bonus handling, registration fields, orders, addresses, referrals, withdrawals and favorites. No endpoint or payload change was found in the redesign diff.
- Admin source inventory remains 75 TSX files and 179/179 input/select/textarea declarations per the recorded baseline; the staged review found no route, permission, API, mutation-handler or settings-key removal.
- The only backend additions are the typed homepage hero-product view and an active/nondeleted product lookup for the three approved hero destinations; the existing response composition pattern remains intact.

## Approval condition

Fix both focus traps and perform focused keyboard checks for initial focus, forward Tab, backward Shift+Tab, Escape close and opener-focus restoration in each dialog. Darken the shared public muted foreground and verify its contrast on secondary, marketplace and white backgrounds. No broader redesign or content change is required.

## Final fix re-review — `013b78f..d25feac`

**APPROVED.** No Critical, Important or Minor findings remain in the reviewed fix scope.

- The production-video modal now focuses its Close button on open, wraps forward and backward Tab navigation over its enabled controls, includes the iframe in that order, and redirects any escaped focus back into the dialog. Escape/backdrop close, iframe cleanup, scroll unlock and opener-focus restoration remain intact.
- The product-gallery lightbox now focuses its Close button and wraps Tab/Shift+Tab over the controls actually rendered for the gallery. Escape, arrow navigation, thumbnail selection, scroll unlock and opener-focus restoration remain intact.
- The light-theme `--muted-foreground` is now `#586e79`. Independent WCAG relative-luminance calculation confirms **4.80:1** on mint `#eaf5f1`, **4.73:1** on lavender `#f6eefb`, **4.90:1** on blue `#edf6ff` and **5.36:1** on white, all above the 4.5:1 requirement for normal text.
- The scoped diff contains only those three source fixes and their report. `git diff --check 013b78f..d25feac` passes. The fix report records a passing project-native `make frontend-check` covering TypeScript, ESLint and the Vite production build (1904 modules).

Final whole-branch source-review verdict: **APPROVED**. Root-owned browser QA remains responsible for exercising both keyboard loops, iframe traversal, Escape and focus restoration in the rendered application.
