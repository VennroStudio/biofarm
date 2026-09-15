# Итоговый статус: APPROVED

Все замечания этого этапа закрыты и повторно проверены. Ниже сохранена история ревью.

---

# Task 4 review — entire admin application

## Verdict

CHANGES REQUIRED.

The diff preserves the reviewed admin routes, API calls, mutation handlers and field declarations. The source inventory remains exactly 75 TSX files and 179 raw `<input>`/`<select>`/`<textarea>` declarations, with every per-file count matching `admin-source-baseline.json`. The shared modal and mobile drawer add stable focus containment, Escape close, body scroll restoration and trigger focus restoration without resetting focus during controlled-input rerenders. Two visual-accessibility defects block approval.

## Findings

### [HIGH] The real white logo is invisible on the new light admin surfaces

Files/lines:

- `new-backend/assets/react/admin/pages/AdminLogin.tsx:35-38`
- `new-backend/assets/react/admin/layout/AdminLayout.tsx:121-125,233-234`

The configured `/uploads/images/logo.png` is the correct real brand asset, but it is a white transparent logo. The public header/footer already apply the explicit green brand filter to this same asset. The admin renders it with no filter on white or pale-mint backgrounds. Root browser QA confirmed the login image loaded successfully (`naturalWidth=1916`, `naturalHeight=770`) while its computed filter was `none`, leaving the logo visually blank. The desktop sidebar and mobile header use the same unfiltered image and have the same defect.

Apply the established public-header green filter (or an equivalent verified treatment) to all three admin logo instances, then visually verify login, desktop sidebar and mobile header.

### [MEDIUM] New small-text color pairs fail WCAG AA contrast

Files/lines:

- `new-backend/assets/react/admin/shared/ui/badge.tsx:7-10,15`
- `new-backend/assets/react/admin/layout/AdminLayout.tsx:215`
- `new-backend/assets/react/admin/features/withdrawals/ui/WithdrawalTabs.tsx:13-24`

These components render 12–14 px text, so WCAG 2.2 AA requires 4.5:1. Calculated from the literal sRGB colors in the diff:

- `#2e8175` on `#eaf5f1` (green badge): **4.17:1**
- `#b36a08` on `#faeed7` (amber badge): **3.67:1**
- `#2563eb` on `#dceafe` (blue badge): **4.24:1**
- `#5f7580` on `#eaf5f1` (sidebar email and inactive withdrawal tabs): **4.34:1**

Use darker foreground colors that reach at least 4.5:1 on their actual backgrounds. The existing `#526d78` on `#eaf5f1` is **4.93:1** and is suitable for the muted mint pair; choose similarly verified darker semantic colors for amber, blue and green badges.

## Preserved behavior reviewed

- All 16 route pages, the products/groups tab, attribute values and all seven settings sections remain present.
- The seven settings field groups preserve their declarations and keys; `home_features_enabled` keeps its key while its label/help text now matches the cooperation form used by the redesigned public homepage.
- No endpoint, payload key, route target, permission check, CRUD handler or authentication TTL changed in the reviewed diff.
- All tabular views route through the shared table component or retain an explicit `overflow-x-auto` wrapper; forms use mobile-first single-column layouts with responsive breakpoints.
- Modal focus is initialized on the first enabled control and does not rerun when form state changes because the effect depends only on `open`; typing focus is therefore stable. Drawer and modal focus restoration, Escape handling and body-scroll cleanup are present.
- `git diff --check 4edb87f..33c17f2` passes. Per scope, this review did not rerun browser automation or the full frontend suite; root owns browser QA and supplied the live logo finding above.

---

## Fix re-review — `33c17f2..013b78f`

APPROVED.

- Login, desktop sidebar and mobile header now apply the exact green filter already used by the public header/footer to the configured real logo. The image source, brand data and link behavior are unchanged.
- Every reported small-text pair now exceeds WCAG AA 4.5:1 on its actual background: amber badge **5.96:1**, blue badge **5.50:1**, green badge/secondary button **7.49:1**, and muted sidebar/tab text **4.93:1**. The secondary button hover state is also **6.99:1**.
- The scoped fix changes only logo presentation and color classes plus the report. It introduces no route, API, field, action, state or focus-management change.
- `git diff --check 33c17f2..013b78f` passes. The task report records the successful full Node 22 frontend check; per scope, this re-review did not rerun that suite or browser automation.
