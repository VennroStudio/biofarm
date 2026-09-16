# Partner Program Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Implement the approved partner/referral, payment, refund, manual payout and shared-cart program.
**Architecture:** Existing Slim/Doctrine app with a dedicated Program domain. Immutable order terms and a minor-unit financial journal separate commissions from shopping bonuses; payment provider consumes domain transitions. Existing account/admin React islands expose the program.
**Tech Stack:** PHP 8.4+, Doctrine DBAL/ORM, Slim, Guzzle, React/TypeScript, Twig, MariaDB, Docker.
**Spec:** ../specs/2026-09-17-partner-program-design.md

## Global Constraints

Stay on main. Do not hand-author migrations. No external mail/CRM or real charges in tests. Manual payouts only. User authorizes implementation and local test users. Preserve existing data/history. Default sample program budget is 4%, no qualification ranks. Partner promotion detaches parent but retains descendants. Secrets never returned to the browser. No subagents spawned by workers.

## Task 1: Financial core and referral structure

**Files:** new Modules/Program entities/services; existing OrderBonusApplier, CreateOrderHandler, user creation/confirmation and admin profile updates, setting actions; Program HTTP actions in dedicated directories and route fragment.
**Interfaces:** See `.superpowers/biofarm-program/core-brief.md`; expose a domain facade consumed by payment integration and documented JSON contract consumed by UI.
- [x] Add failing deterministic tests for 4-level allocation, partner detachment, discounts, refund proration, payout reservation and duplicate events.
- [x] Implement mapped additive tables, integer monetary services, snapshots, referral tree, manual payouts, validated settings, history and protected endpoints.
- [x] Wire creation/payment/delivery/cancellation adapters, disable welcome credit, preserve legacy historical balances without turning them into withdrawable cash.
- [x] Run PHP checks and tests; publish exact facade/HTTP contract to `.superpowers/biofarm-program/core-contract.md`.
- [x] Review spec and financial concurrency before integration.

## Task 2: Payments, shared proposals and promo requests

**Files:** Modules/Payment entities/services, payment HTTP actions/routes, Modules/PartnerOffer entities/services/actions, Checkout/OrderSuccess integration.
**Interfaces:** Domain facade from Task 1; Guzzle provider adapter with configurable injected transport; public proposal endpoint with opaque token.
- [x] Test creation/webhook provider verification and idempotency using fake HTTP transport.
- [x] Implement server-created payments, safe guest capabilities, webhook/reconciliation, refunds/receipts, no real payout API.
- [x] Implement partner-only offer creation/list/disable, public safe offer reader, explicit cart import and local QR rendering.
- [x] Implement partner promo request lifecycle and admin validation through existing promo handling.
- [x] Review authorization, secrets, replay and uncertain provider outcomes.

## Task 3: Account and admin UI

**Files:** ProfilePage/program components, admin pages/features/navigation/api, settings/welcome UI, checkout and order payment displays.
- [x] Add dedicated profile tab for all participants, partner-only offers/promo tools, team/history/earnings/manual requests.
- [x] Add admin program management, rates/budget/hold simulator, finance/history/manual payout/refund tools and isReferral badges/referrer.
- [x] Preserve unrelated account/order functionality and mobile layouts; run tsc/eslint/build and auth regression tests.
- [x] Browser review of public checkout/account/admin paths.

## Task 4: End-to-end validation and rollout documentation

**Files:** tests/program integration scripts and docs/program implementation review.
- [x] Inspect generated ORM schema changes; apply additive program tables locally without modifying production.
- [x] Create clearly identifiable local test users, run multiple real local HTTP order/account/admin requests, verify DB records and totals.
- [x] Test promotion detachment, 4 levels, duplicate processing, concurrent/reserved spending, partial/full refunds and manual payout history.
- [x] Independent final review, resolve material findings, record verified checks and external credentials limitations.

## Result and limits

Implementation and local checks completed; see `../../partner-program-implementation.md`. Real-provider testing requires shop credentials. Production migration remains developer-owned by project rules. Mobile viewport override did not take effect, so no mobile visual pass is claimed. Delivery settlement receipts, durable outbox and reconciliation CLI are implemented; live fiscal verification and production scheduling need the shop environment.
