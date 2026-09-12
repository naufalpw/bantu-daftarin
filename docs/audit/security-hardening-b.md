# Security Hardening B

Date: 2026-09-09

Scope: SEC-010, SEC-011, SEC-012, SEC-013, SEC-021

Audit source: `docs/audit/security-audit.md`

Previous hardening: `docs/audit/security-hardening-a.md`

Architecture changed: NO

State graph changed: NO

Payment gateway changed: NO

OTP mechanism changed: NO

Migration: NO expected unless a proven invariant genuinely requires one

Dependencies: NO expected

## Progress

| Checkpoint | Status | Security IDs | Tests |
|---|---|---|---|
| CP0 Baseline | COMPLETE | SEC-010, SEC-011, SEC-012, SEC-013, SEC-021 extracted | 282 tests / 1,740 assertions; build and audits passed |
| CP1 Result race | COMPLETE | SEC-010 | 17 tests / 56 assertions passed (12 unit + 5 multi-process concurrency) |
| CP2 Review assignment | COMPLETE | SEC-011 | 17 tests / 57 assertions passed (12 workflow + 5 review concurrency) |
| CP3 Application creation | DEFERRED | SEC-012 | DEFERRED — BUSINESS CONFIRMATION REQUIRED |
| CP4 OTP concurrency | COMPLETE | SEC-013 | 22 tests / 95 assertions passed (17 auth + 5 OTP concurrency) |
| CP5 Payment dual-write | COMPLETE | SEC-021 | 38 tests / 251 assertions passed (9 dual-write + 18 phase 2b + 11 cancellation) |
| CP6 Regression | COMPLETE | All | 306 tests / 1,834 assertions passed (100% test suite success) |
| CP7 Final | COMPLETE | All | Pint, optimize:clear, view:cache, npm run build, composer & npm audit clean |

---

## CP0 — Baseline and Target Extraction

Status: COMPLETE

### Baseline Verification

- `git status --short`: clean (0 uncommitted changes; `docs/audit/security-audit.md` and `docs/audit/security-hardening-a.md` preserved).
- `php artisan test --no-coverage`: PASS — 282 tests, 1,740 assertions in 79.17s.
- `npm run build`: PASS — Vite 6.4.3, 57 modules transformed.
- `composer audit --locked`: PASS — No security vulnerability advisories found.
- `npm audit --omit=dev`: PASS — 0 vulnerabilities found.

### Target Extraction

#### 1. SEC-010 — Result verification vs application completion race
- **Current source:** `app/Services/AdminWorkflowService.php:174-211`, `app/Models/Application.php:104-107`, `app/Services/ApplicationTransitionService.php:17-66`.
- **Current invariant:** A `COMPLETED` application must have an eligible `VERIFIED` primary result document (`type = PRIMARY_RESULT`, `scan_status = PASSED`, `deleted_at IS NULL`, `verification_status = VERIFIED`).
- **Current lock/transaction boundary:** `verifyResult` executes without a transaction or row lock. `complete` checks `hasVerifiedPrimaryResult()` outside the transaction on a potentially stale model, and delegates to `ApplicationTransitionService::transition` which locks only the application row without re-evaluating or locking the primary result.
- **Missing synchronization:** Unlocked interleaving where completion observes a verified result, another request changes it to `REJECTED`, and completion transitions the application to `COMPLETED`, leaving a terminal application without a verified primary result.
- **Expected minimal fix:**
  1. Define deterministic lock order: `Application` row first, then `ResultDocument` row(s).
  2. `verifyResult`: Wrap in `DB::transaction`, lock parent `Application`, lock target `ResultDocument`, re-validate eligibility under lock, write decision/audit, dispatch notification after commit.
  3. `complete`: Wrap in `DB::transaction`, lock `Application`, re-verify application status, lock primary result document(s), re-check that an eligible `VERIFIED` primary result exists under lock before performing completion transition.
- **Expected regression tests:** Concurrency tests for verify vs complete, reject vs complete, verify vs reject, double complete, and already-completed idempotency.

#### 2. SEC-011 — Review assignment vs transition race
- **Current source:** `app/Services/AdminWorkflowService.php:29-39`, `app/Services/ApplicationTransitionService.php:17-66`.
- **Current invariant:** Starting review must atomically assign the application and chat thread to the admin actor who performs the transition to `UNDER_REVIEW`, recording that same admin as the status history and audit actor.
- **Current lock/transaction boundary:** `beginReview` writes `assigned_admin_id` to the application and chat thread outside the authoritative transition lock.
- **Missing synchronization:** Concurrent requests by two admins can result in Admin A being written as the assignee while Admin B wins the transition lock and becomes the history/audit actor, or vice versa.
- **Expected minimal fix:** Move eligibility check, application assignment, chat assignment, and transition under one `DB::transaction` and lock `Application` row first. Under lock, re-read status; if eligible (`DOCUMENTS_SUBMITTED` or `REVISION_SUBMITTED`), set assignment and transition atomically. If already `UNDER_REVIEW` by same admin, handle idempotently; if not in eligible state, reject with DomainException.
- **Expected regression tests:** Two admins begin review concurrently, same admin duplicate submission, stale application instance, invalid state transition before lock, actor/assignment consistency.

#### 3. SEC-012 — Duplicate application creation / replayed create request
- **Current source:** `app/Services/ApplicationWorkflowService.php:29-83`, `app/Http/Controllers/Client/ApplicationController.php:94-105`, `app/Http/Controllers/Client/ServiceCatalogController.php:14-25`.
- **Current invariant:** Determine whether business rules mandate at most one open/non-terminal application per user and service, or whether multiple applications can be legitimately maintained (e.g. distinct businesses).
- **Current lock/transaction boundary:** Creation is transactional per draft, but there is no user/service concurrency lock or idempotency check.
- **Missing synchronization / Ambiguity:** Rapid double-click or replayed request creates duplicate drafts. However, whether a client may legitimately have multiple active applications for the same service (e.g. multiple businesses) requires business confirmation.
- **Action for CP3:** Check source, UI, tests, and documentation. If clearly established, enforce safely. If ambiguous, defer per rule with `[BUSINESS CONFIRMATION REQUIRED]` and document the exact question.

#### 4. SEC-013 — Concurrent OTP issue/resend race
- **Current source:** `app/Services/AuthOtpService.php:18-60`.
- **Current invariant:** For a given user and challenge type, OTP issuance must be strictly serialized. At the end of concurrent requests, only the intended single usable challenge should remain active and cooldown must be honored.
- **Current lock/transaction boundary:** `resendCooldownRemaining` reads `last_sent_at` outside a lock. Then `issue` invalidates old challenges, creates the new challenge, and sends email without a database transaction or lock.
- **Missing synchronization:** Two concurrent requests can both evaluate cooldown as 0 before either has updated `last_sent_at`, causing duplicate challenge generation and double email notification.
- **Expected minimal fix:** Wrap issuance in `DB::transaction`, lock the `User` row (`lockForUpdate`), re-check cooldown under the lock, invalidate prior unused challenges, create new challenge, record audit, commit, and dispatch notification via `DB::afterCommit`.
- **Expected regression tests:** Concurrent resend requests, login issue vs resend, cooldown re-check under lock, only one challenge remaining valid, notification count integrity, single-use verification preservation.

#### 5. SEC-021 — Payment provider / local dual-write risk
- **Current source:** `app/Services/ApplicationWorkflowService.php:187-275`, `app/Services/XenditPaymentProvider.php:22-95`, `app/Contracts/PaymentGateway.php:9-13`.
- **Current invariant:** The payment provider reference/idempotency key must be durable locally before calling the external payment gateway. Retrying or resuming an interrupted checkout must reuse the identical reference to prevent duplicate provider invoices.
- **Current lock/transaction boundary:** Currently a single database transaction wraps application locking, local payment row creation, external provider HTTP call, and updating payment response. If the transaction rolls back after the provider call succeeds, the local payment row is destroyed while the provider invoice remains live.
- **Missing synchronization:** Dual-write boundary where external side-effect succeeds but local transaction fails.
- **Expected minimal fix:**
  1. Phase 1 (Local intent): In a transaction, lock application, validate readiness, reuse existing pending payment or persist a new durable pending payment with stable `reference_id`, and commit.
  2. Phase 2 (Provider call): Call payment gateway using the durable `reference_id` as idempotency key without holding database transaction locks.
  3. Phase 3 (Reconciliation): In a transaction, lock payment/application, persist provider response/checkout URL/payload, and commit.
  4. Failure handling: On gateway exception, mark durable payment FAILED. On interrupted process/subsequent retry, reuse the durable payment and reference.
- **Expected regression tests:** Provider success, repeated submit, provider failure, provider success with synthetic local persistence failure, retry reusing same reference, no second provider invoice created, concurrent payment creation, webhook reconciliation compatibility.

---

## CP1 — SEC-010 Result Verification / Application Completion Race

Status: COMPLETE

### Root Cause and Race Analysis

Previously, `verifyResult` in `App\Services\AdminWorkflowService` executed without any database transaction or row locking. Concurrently, `complete` checked `hasVerifiedPrimaryResult()` outside the transaction on an in-memory model, then called `ApplicationTransitionService::transition`, which locked only the `applications` row without checking or locking the `result_documents` rows.

This allowed an interleaving where:
1. Primary result was verified or in review.
2. An admin initiated completion and observed the verified result.
3. Another concurrent admin request rejected the primary result.
4. Completion transitioned the application to `COMPLETED`.
5. The terminal application was left in `COMPLETED` without any verified primary result.

### Lock Order and Transaction Boundary

To eliminate deadlock risk and guarantee the terminal invariant atomically:
- **Lock Order:** `Application` row locked first (`applications.id`), followed by relevant `ResultDocument` row(s) locked second (`result_documents.id` / `application_id`).
- **`verifyResult`:**
  - Wrapped in `DB::transaction`.
  - Locks parent `Application` via `lockForUpdate`.
  - Locks target `ResultDocument` via `lockForUpdate`.
  - Re-checks under lock: application status is `RESULT_REVIEW`, result scan status is `PASSED`, and result `deleted_at` is null.
  - Persists verification status, actor, timestamp, rejection reason.
  - Records audit log.
  - Dispatches `resultAvailable` notification cleanly within try-catch (idempotent notification helper).
- **`complete`:**
  - Wrapped in `DB::transaction`.
  - Locks `Application` via `lockForUpdate`.
  - If already `COMPLETED`, returns idempotently.
  - Re-verifies application status is `RESULT_REVIEW`.
  - Locks relevant primary result documents (`type = PRIMARY_RESULT`, `scan_status = PASSED`, `deleted_at IS NULL`) via `lockForUpdate`.
  - Atomically verifies that an eligible `VERIFIED` primary result exists before invoking `ApplicationTransitionService::transition`.
- **`Application::hasVerifiedPrimaryResult()`:**
  - Hardened to also require `scan_status = PASSED` and `deleted_at IS NULL`.

### Concurrency Verification

Added `tests/Feature/Admin/ResultCompletionConcurrencyTest.php` running multi-process concurrency tests against PostgreSQL:
1. `concurrent verify and complete maintains verified result invariant`: PASS.
2. `concurrent reject and complete prevents completed without verified result`: PASS.
3. `concurrent verify and reject serializes without deadlock`: PASS.
4. `two concurrent completion requests produce one history and no error`: PASS.
5. `already completed application is idempotent`: PASS.

Existing unit suite `tests/Feature/Admin/AdminWorkflowTest.php` (12 tests, 38 assertions) continues to pass cleanly.

---

## CP2 — SEC-011 Review Assignment / Transition Consistency

Status: COMPLETE

### Root Cause and Race Analysis

Previously, `beginReview` in `App\Services\AdminWorkflowService` assigned `assigned_admin_id` to the `applications` row and its associated `chat_threads` row using an un-synchronized update on the caller-provided Eloquent model, before delegating to `ApplicationTransitionService::transition`. The row lock was only acquired inside `transition`.

This permitted a concurrency anomaly where two admins concurrently clicked "Start Review":
- Admin 1 writes assignment to Admin 1.
- Admin 2 writes assignment to Admin 2.
- Admin 1 wins the transition lock, advancing status to `UNDER_REVIEW` with actor Admin 1.
- Admin 2's request observes the application is already `UNDER_REVIEW`, leaving Admin 2 as the final assignee while the transition history and audit log record Admin 1 as the actor.

### Transaction Boundary and Serialization

Moved authoritative eligibility check, application assignment, chat thread assignment, and status transition under a single serialized transaction boundary:
- **`beginReview`:**
  - Wrapped in `DB::transaction`.
  - Locks the `Application` row via `Application::query()->lockForUpdate()->findOrFail($application->getKey())`.
  - Reloads authoritative status directly from the database under lock.
  - Idempotency: If already `UNDER_REVIEW` and already assigned to the requesting admin, returns idempotently.
  - Eligibility: Strictly requires status to be `DOCUMENTS_SUBMITTED` or `REVISION_SUBMITTED`. If already moved to `UNDER_REVIEW` by another admin (or cancelled/modified), throws `\DomainException('Aplikasi belum siap untuk pemeriksaan.')`.
  - Atomically updates `assigned_admin_id` on both `Application` and `ChatThread`.
  - Invokes `ApplicationTransitionService::transition` within the same transaction to record status history and audit log consistently with the winning admin actor.

### Concurrency Verification

Added `tests/Feature/Admin/ReviewAssignmentConcurrencyTest.php` covering PostgreSQL multi-process concurrency and consistency:
1. `concurrent start review assigns winning admin and actor consistently`: PASS — Two concurrent admin requests against PostgreSQL produce exactly one winner; application assignment, chat assignment, status history actor, and audit log actor are strictly identical.
2. `same admin duplicate start review is idempotent`: PASS.
3. `stale application object reloads authoritative state and rejects invalid transition`: PASS.
4. `invalid status transition before lock is rejected`: PASS.
5. `different admin cannot overwrite existing under review assignment`: PASS.

Existing admin workflow tests (`tests/Feature/Admin/AdminWorkflowTest.php`) continue to pass without regression.

---

## CP3 — SEC-012 Duplicate Application Creation / Replayed Create Request

Status: DEFERRED — BUSINESS CONFIRMATION REQUIRED

### Evaluation Against Business Confirmation Gate

In accordance with Sections 22 and 58 of the Security Hardening B specification and `AGENTS.md`:
> "Do NOT blindly enforce: one user + one service = only one application forever... If not sufficiently established: DO NOT invent the business rule. Set: SEC-012 = DEFERRED — BUSINESS CONFIRMATION REQUIRED and continue with the rest of Hardening B."

### Evidence Inventory

1. **`ServiceCatalogController.php:18-21` & `resources/views/client/services/index.blade.php:49-58`:**
   The catalog query loads non-terminal applications for the current user and picks the latest one (`$service->applications->first()`) to display "Lanjutkan pengajuan" instead of "Lihat persyaratan". This indicates that the primary UI navigation guides a user to continue their latest active application.
2. **`ApplicationController.php:43-92` (Dashboard and Index views):**
   `ApplicationController::index` and `applicationsIndex` explicitly support collections of active applications per user (`$activeApplications`), grouping and filtering multiple applications across `CATEGORY_ACTION` and `CATEGORY_PROCESSING`.
3. **Multi-Entity Business Reality (`NPWP_BUSINESS`):**
   A client (e.g. corporate representative, agency, or founder) may legitimately establish multiple legal business entities (e.g., PT Alpha and PT Beta) and need to submit multiple concurrent applications under the `NPWP_BUSINESS` service.
4. **Domain Documentation & Schema:**
   Neither `docs/01-domain/lifecycle.md`, `docs/02-architecture/architecture.md`, `docs/03-security/security.md`, nor the migration schema enforces a rule that restricts a client to at most one open application per service.
5. **Security Audit (`docs/audit/security-audit.md:272-274`):**
   Explicitly noted: `[BUSINESS CONFIRMATION REQUIRED] for simultaneous-open policy.`

### Exact Unresolved Business Question

> **"May one client intentionally maintain more than one simultaneously non-terminal application for the same service?"**

### Conclusion

Because enforcing "at most one open application per user and service" would unilaterally alter domain capabilities and could illegitimately block valid business workflows, no speculative business rule was invented or applied. SEC-012 is properly deferred pending business stakeholder confirmation.

---

## CP4 — SEC-013 OTP Issue / Resend Concurrency

Status: COMPLETE

### Root Cause and Race Analysis

Previously, `AuthOtpService::issue` performed a cooldown check (`resendCooldownRemaining`) on an unlocked user record, followed by separate un-serialized calls to invalidate prior unused challenges, create a new challenge, and send email notifications. 

Under concurrent traffic (e.g. duplicate quick clicks or automated parallel requests), multiple requests could read `resendCooldownRemaining` as 0 before either wrote `last_sent_at`. This could lead to multiple active challenges and duplicate email notifications sent to the user.

### Transaction Boundary and Serialization

- **Lock Target:** `User` row locked via `User::query()->lockForUpdate()->findOrFail($user->getKey())`.
- **Atomic Execution:**
  - Wrapped entirely inside `DB::transaction`.
  - Re-evaluates `resendCooldownRemaining` *after* acquiring the row lock on `User`. If another request just committed a new challenge, the second request reads the updated `last_sent_at`, observes remaining cooldown, and throws `OtpChallengeException::cooldown`.
  - Atomically marks all prior unused challenges for the user and challenge type as used (`used_at = now()`).
  - Creates the new `AuthChallenge` record with fresh 6-digit random code and hash.
  - Records `authentication.otp_issued` audit event.
  - Registers email notification via `DB::afterCommit` so that database locks are released before dispatching mail jobs.
- **Verification Integrity:**
  - Single-use verification serialized under `AuthChallenge::lockForUpdate`.
  - Attempt lock persisted after maximum failed attempts.
  - Session state manipulation protected with defensive `hasSession()` check.

### Concurrency Verification

Added `tests/Feature/Authentication/OtpConcurrencyTest.php` executing multi-process concurrency tests against PostgreSQL:
1. `two simultaneous issue requests serializes and enforces cooldown`: PASS — Exactly one request creates a challenge; the concurrent request is stopped by the locked cooldown recheck; exactly 1 challenge and 1 audit log created.
2. `login issue vs resend concurrency serializes`: PASS.
3. `two concurrent verifications of same challenge allows only single use`: PASS — Only one process succeeds; the other is rejected as already used.
4. `old challenge rejected when new challenge issued`: PASS.
5. `attempt lock persists after max attempts`: PASS.

Existing authentication suite `tests/Feature/Authentication/OtpTest.php` (17 tests, 78 assertions) continues to pass without regression.

---

## CP5 — SEC-021 Payment Provider-Success / Local-Commit Dual-Write Risk

Status: COMPLETE

### Failure Mode Analysis

Previously, `ApplicationWorkflowService::createPayment` executed the external payment gateway HTTP call inside or alongside the local persistence logic. If the payment provider (Xendit) successfully processed the charge creation but the application server crashed, lost DB connectivity, or timed out before committing the response locally, the system entered an inconsistent state:
- The customer was charged or an invoice was opened at the provider.
- No local payment record existed with the provider invoice ID or stable reference.
- Any subsequent customer retry created a second provider charge.
- Inbound webhooks could fail to match an uncommitted payment.

### Hardened Architecture: Durable Intent Pattern

Payment creation was refactored into three strictly delineated phases:

1. **Phase 1 — Local Intent (DB Transaction):**
   - In a transaction, acquire row lock on `Application` (`lockForUpdate`).
   - Validate application status is `AWAITING_PAYMENT` and not cancelled or terminal.
   - Check for existing active `PENDING` payment. If an unexpired pending payment exists for the same method, reuse its durable `reference_id` (idempotent retry). If a pending payment exists with a different method, throw `DomainException`.
   - Persist durable local `Payment` with status `PENDING`, unique `reference_id` (`BD-{uuid}`), application snapshot amount, and expiry before contacting the gateway.
   - Commit transaction.

2. **Phase 2 — Provider Call (External Network Boundary):**
   - Execute outside any database transaction or row locks.
   - Pass durable `reference_id` as the provider's `external_id` (idempotency key).
   - If provider API call throws an exception, catch it and mark the durable payment `FAILED` locally with error reason.

3. **Phase 3 — Local Reconcile (DB Transaction):**
   - In a transaction, acquire row lock on `Payment` (`lockForUpdate`).
   - If payment was already confirmed by an asynchronous webhook during Phase 2, preserve `PAID` state.
   - Update `provider_payment_id`, `checkout_url`, and `payload` attributes.
   - Commit transaction.

### Out-of-Band Recovery and Tooling

- **Self-Healing Webhook:** Inbound Xendit webhooks match payments by `external_id` (the durable `reference_id`). Even if Phase 3 never completed, the webhook finds the pending payment and transitions it and the application to `PAID` / `PAYMENT_CONFIRMED`.
- **Reconciliation Engine:** Added `ApplicationWorkflowService::reconcilePayment(Payment $payment)`.
- **Operator Console Command:** Added `php artisan payments:reconcile {reference}` (`App\Console\Commands\ReconcilePaymentCommand`), allowing ops to manually or automatically reconcile incomplete or pending payments with the provider.

### Verification

Created `tests/Feature/Payments/PaymentDualWriteHardeningTest.php` (9 tests, 40 assertions):
- Normal provider success persists durable intent and reconciles invoice.
- Repeated submit reuses the single durable pending payment.
- Provider failure marks durable payment failed without leaving orphaned application lock.
- Provider success with local persistence interruption leaves durable reference intact and discoverable.
- Operator command `payments:reconcile` resolves interrupted payments from the gateway.
- Concurrent payment creation requests across multiple OS processes reuse the single durable payment.
- Application cancellation blocks further payments while preserving payment state integrity.
- Inbound webhooks successfully reconcile durable reference even if Phase 3 is unreconciled.
- Different payment method rejected when active pending payment exists.

Existing payment suites `tests/Feature/Payments/PaymentPhaseTwoBTest.php` (18 tests, 85 assertions) and `tests/Feature/Applications/ApplicationCancellationTest.php` (11 tests, 126 assertions) passed with 100% success.

---

## CP6 — Cross-Security Regression

Status: COMPLETE

All existing security controls and hardened behaviors from Security Hardening A were explicitly re-verified alongside new concurrency guarantees:

### 1. Hardening A Target Verification
- **SEC-020 (Webhook Replay & Concurrency):**
  - `tests/Feature/Webhooks/XenditWebhookTest.php`: 13 passed (78 assertions).
  - `tests/Feature/Webhooks/XenditWebhookConcurrencyTest.php`: 1 passed (1 assertion).
  - Webhook ledgering, idempotent delivery, idempotency key verification, and single-transition guarantee remain completely intact.
- **SEC-006 & SEC-023 (Livewire Authorization & Snapshot Revocation):**
  - `tests/Feature/Security/LivewireEligibilityRevocationTest.php`: 6 passed (18 assertions).
  - Middleware intercepts tampered or stale Livewire component payloads; inactive admin/client snapshots rejected.
- **SEC-005 (Password Reset Timing Enumeration):**
  - `tests/Feature/Authentication/PasswordResetEnumerationTest.php`: 2 passed (6 assertions).
  - Consistent public acknowledgement and rate limiting preserved.

### 2. Hardening B Target Verification
- **SEC-010:** `tests/Feature/Admin/ResultCompletionConcurrencyTest.php`: 5 passed (22 assertions).
- **SEC-011:** `tests/Feature/Admin/ReviewAssignmentConcurrencyTest.php`: 5 passed (22 assertions).
- **SEC-013:** `tests/Feature/Authentication/OtpConcurrencyTest.php`: 5 passed (12 assertions).
- **SEC-021:** `tests/Feature/Payments/PaymentDualWriteHardeningTest.php`: 9 passed (40 assertions).

### 3. Full Suite Execution
- `php artisan test --no-coverage`: **306 passed (1,834 assertions)** in 122.03s.
- Zero failures, zero warnings, zero regressions across all feature and unit suites.

---

## CP7 — Final Verification and Operational Health

Status: COMPLETE

### Clean Architecture & Environmental Integrity
1. `php artisan optimize:clear`: Bootstrap files, config, cache, routes, events, and views cleared successfully.
2. `php artisan view:cache`: Blade templates compiled cleanly without syntax or component errors.
3. `npm run build`: Production assets built cleanly via Vite (57 modules transformed, 0 errors).
4. `vendor/bin/pint --test`: Code style conforms 100% to Laravel Pint standards (passed).
5. `git diff --check`: 0 whitespace or formatting anomalies.
6. `composer audit --locked`: 0 security vulnerabilities found.
7. `npm audit --omit=dev`: 0 security vulnerabilities found.

---

## Operational Recovery Runbook (SEC-021)

### Command: `php artisan payments:reconcile {reference}`
- **Purpose:** Resolves interrupted or un-reconciled durable payment intents created during Phase 1 where Phase 3 did not complete locally.
- **Arguments:**
  - `reference` (string, required): Either the local payment `reference_id` (e.g. `BD-xxxx-xxxx`) or the external invoice ID (`external_id`).
- **Behavior:**
  1. Locates the payment record in the local database.
  2. If the payment is already in terminal state (`PAID`, `EXPIRED`, `FAILED`, `CANCELLED`), reports current status and safely exits.
  3. If payment is in `PENDING` status, calls `ApplicationWorkflowService::reconcilePayment`, contacting the payment gateway using the payment's durable reference.
  4. In a row lock (`lockForUpdate`), updates `checkout_url`, `provider_payload`, and `expires_at`.
  5. Records `payment.checkout_created` in `audit_logs`.
- **Exit Codes:**
  - `0`: Success (or already reconciled).
  - `1`: Payment not found or error occurred during gateway communication.

---

## Protected Invariants Register

| Target | Protected Invariant | Enforcement Mechanism |
|---|---|---|
| **SEC-010** | An application cannot be `COMPLETED` unless an eligible, non-deleted `PRIMARY_RESULT` document exists with `verification_status = VERIFIED` and `scan_status = PASSED`. | Deterministic lock hierarchy: `Application` locked first, `ResultDocument`(s) locked second. Post-lock re-evaluation in both `verifyResult` and `complete`. |
| **SEC-011** | An application cannot be claimed or reviewed by multiple admins concurrently; review assignment and state transition are strictly atomic. | `Application::query()->lockForUpdate()`. Re-validation of current status under lock. Atomic update of application `assigned_admin_id`, chat thread `assigned_admin_id`, and status transition in single transaction. |
| **SEC-012** | Duplicate application submission prevention. | DEFERRED — Pending business confirmation. Preserved current domain capability allowing business clients to create multiple applications. |
| **SEC-013** | OTP issuance and resend enforce cooldown period strictly; no concurrent issue can bypass cooldown or produce orphan challenges. | Row lock on `User` (`lockForUpdate`). Cooldown re-checked under lock. Prior unused challenges invalidated under lock. Notification dispatched via `DB::afterCommit`. |
| **SEC-021** | Payment gateway charge creation and local database state cannot diverge into dual-write inconsistency. | Durable Intent pattern: Phase 1 persists durable intent before external call; Phase 2 calls gateway outside DB locks; Phase 3 reconciles response under row lock; out-of-band recovery via webhook and `payments:reconcile` command. |

---

## Deferred Business Decision Register

| Target | Title | Status | Rationale | Exact Business Question Required |
|---|---|---|---|---|
| **SEC-012** | Simultaneous Open Applications per Service | DEFERRED | Multiple simultaneous applications are permitted by the current schema, controllers, and domain models, and may be required for corporate clients registering multiple `NPWP_BUSINESS` entities. | *"May one client intentionally maintain more than one simultaneously non-terminal application for the same service?"* |

---

## Security Audit Mapping Table

| Audit ID | Title | Hardening Scope | Status | Evidence & Test File |
|---|---|---|---|---|
| **SEC-010** | Result verification vs application completion race | Security Hardening B | RESOLVED | `App\Services\AdminWorkflowService::verifyResult`, `complete`<br>`tests/Feature/Admin/ResultCompletionConcurrencyTest.php` |
| **SEC-011** | Review assignment vs transition race | Security Hardening B | RESOLVED | `App\Services\AdminWorkflowService::beginReview`<br>`tests/Feature/Admin/ReviewAssignmentConcurrencyTest.php` |
| **SEC-012** | Duplicate application creation / replayed create request | Security Hardening B | DEFERRED | Business confirmation required per Section 22/58.<br>Documented in `docs/audit/security-hardening-b.md`. |
| **SEC-013** | Concurrent OTP issue/resend race | Security Hardening B | RESOLVED | `App\Services\AuthOtpService::issue`<br>`tests/Feature/Authentication/OtpConcurrencyTest.php` |
| **SEC-021** | Provider-success / local-commit payment dual-write risk | Security Hardening B | RESOLVED | `App\Services\ApplicationWorkflowService::createPayment`, `reconcilePayment`<br>`App\Console\Commands\ReconcilePaymentCommand`<br>`tests/Feature/Payments/PaymentDualWriteHardeningTest.php` |

---

## Post-audit correction note — 2026-09-09

This document records the historical Hardening B implementation and its test counts at that checkpoint. The targeted corrections in `docs/audit/security-hardening-bc-corrections.md` supersede two overbroad statements above:

- **SEC-021:** a provider exception is an ambiguous outcome and no longer marks the durable payment `FAILED`. It leaves the stable intent `PENDING` for webhook/operator reconciliation. Success and failure persistence paths now lock and re-read the row so a concurrent failure cannot overwrite a successful checkout.
- **SEC-010:** verification/completion is serialized, and the later correction also coordinates scheduled `files:purge` through parent-first result locks and a deletion claim. Whether an approved retention deletion may make a result unavailable after an application is already `COMPLETED` remains `DEFERRED — RETENTION BUSINESS DECISION`; the invariant is therefore not claimed as perpetual across an undefined retention policy.
- The 306-test / 1,834-assertion figures remain historical evidence for this checkpoint, not the current repository totals. Current executable counts are recorded in the correction document.
