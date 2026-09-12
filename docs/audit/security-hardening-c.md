# Security Hardening C

Date:
2026-09-09

Scope:
SEC-024, SEC-003, SEC-015, SEC-022

Audit source:
docs/audit/security-audit.md

Previous hardening:
Security Hardening A
Security Hardening B

Architecture changed:
NO

Chat architecture changed:
NO

Payment architecture changed:
NO

Migration:
NO expected

Dependency:
NO expected

## Progress

| Checkpoint | Status | Security IDs | Tests |
|---|---|---|---|
| CP0 Baseline | COMPLETE | SEC-024, SEC-003, SEC-015, SEC-022 | 306 tests / 1,834 assertions; build and audits passed |
| CP1 Abuse resistance | COMPLETE | SEC-024 | 9 tests / 182 assertions passed (ChatAbuseResistanceTest) + 13 tests / 63 assertions (ChatTest) |
| CP2 Legacy payment | COMPLETE | SEC-003 | 10 tests / 37 assertions passed (LegacyPaymentValidationTest) + 27 tests / 125 assertions (Phase 2B + Dual-write) |
| CP3 Storage alias | COMPLETE | SEC-015 | 8 tests / 16 assertions passed (StorageAliasBoundaryTest) + 25 tests / 75 assertions (Doc & Admin Workflow) |
| CP4 Payload retention | COMPLETE | SEC-022 | 11 tests / 53 assertions passed (PayloadMinimizationTest) + 13 tests / 70 assertions (XenditWebhookTest) |
| CP5 Regression | COMPLETE | All Hardening C | 155 tests / 801 assertions passed (100% pass across 17 test suites) |
| CP6 Final | COMPLETE | All Targets | 344 tests / 2,126 assertions passed (100% full suite); Pint, build, and audits clean |

---

## CP0 — Baseline and Target Extraction

Status: COMPLETE

### Baseline Verification

- `git status --short`: clean working directory apart from previous hardening deliverables.
- `php artisan test --no-coverage`: PASS — 306 tests, 1,834 assertions.
- `npm run build`: PASS — Vite 6.4.3, 57 modules transformed.
- `composer audit --locked`: PASS — No security vulnerability advisories found.
- `npm audit --omit=dev`: PASS — 0 vulnerabilities found.

### Target Extraction

| Security ID | Audit Description | Original Severity | Original Audit Status | Hardening C Scope Boundary |
|---|---|---|---|---|
| **SEC-024** | Authenticated chat / typing / presence abuse resistance | MEDIUM | DEFENSE_IN_DEPTH | Server-side send rate limit (30/min), bounded typing (60/min), presence heartbeat route throttle (60/min). |
| **SEC-003** | Legacy payment endpoint validation parity | LOW | DEFENSE_IN_DEPTH | Enforce `StorePaymentRequest` validation parity on `ApplicationController::payment`; reject missing/invalid method without silent BCA fallback. |
| **SEC-015** | Serve-enabled storage alias sharing the private root | LOW | DEFENSE_IN_DEPTH | Uncouple generic `local` disk root from `storage/app/private` to `storage/app/local`; disable file serving (`serve = false`). |
| **SEC-022** | Provider/webhook payload minimization and retention boundary | LOW | DEFENSE_IN_DEPTH | Minimize persisted payload fields, redact customer PII, prevent token storage, provide safe manual pruning command; retention schedule remains deferred. |

#### 1. SEC-024 — Authenticated chat / typing / presence abuse resistance
- **Current source:** `app/Livewire/ChatThread.php:52-85` (`send`), `app/Livewire/ChatThread.php:87-105` (`typing`), `routes/web.php:48-51` (`presence.heartbeat`), `app/Http/Controllers/PresenceHeartbeatController.php:12-26`.
- **Current protection:** Authenticated sessions, thread ownership/role policies, 2000 character maximum, cache-backed typing, active role heartbeat verification, 60s email coalescing.
- **Residual risk:** Authenticated actors can script rapid message sends (generating thousands of DB messages, notifications, and audits), rapid typing updates (cache thrashing), or rapid presence heartbeats (unthrottled route).
- **Minimum required change:**
  1. Add server-side rate limit on `ChatThread::send`: key `chat:send:{user_id}:{thread_id}`, threshold 30 sends/minute/user+thread. On limit exceeded: do not open DB transaction, do not create message/audit/notification/email; return inline validation error on `body`.
  2. Throttle `ChatThread::typing` to max 60 updates/minute per user+thread; clear cache immediately when body is blank.
  3. Register named route limiter `presence-heartbeat` (threshold 60/minute per user/IP) on `POST /presence/heartbeat`. Client cadence is 1 heartbeat every 40s (~1.5/min), giving 40x headroom.
- **Expected tests:** Normal message sending, limit exceeded, no message/audit/notification created on rejection, thread isolation, user isolation, typing usability, heartbeat normal cadence, heartbeat burst throttled (429), authorization intact.

#### 2. SEC-003 — Legacy payment endpoint validation parity
- **Current source:** `routes/client.php:24`, `app/Http/Controllers/Client/ApplicationController.php:144-156`.
- **Current protection:** Authenticated, verified client, owner-scoped application lookup, `submit` policy authorization, workflow payment readiness and method availability gates.
- **Residual risk:** `payment()` accepts unvalidated `Request` and silently falls back missing or invalid `payment_method` to `PaymentMethod::BCA`.
- **Minimum required change:** Retain endpoint for backward compatibility (`COMPATIBILITY_ONLY`), but enforce validation parity with primary payment route `client.payments.store` using `StorePaymentRequest`. Reject missing or unknown payment methods with validation errors instead of silent BCA fallback.
- **Expected tests:** Valid BCA, BRI, QRIS; PayPal unsupported behavior; missing method rejected; unknown method rejected; casing/shape manipulation rejected; cross-owner blocked; payment readiness enforced; no silent BCA fallback.

#### 3. SEC-015 — Serve-enabled storage alias sharing private root
- **Current source:** `config/filesystems.php:33-55`.
- **Current protection:** Framework signed URLs required; application exclusively uses `private` disk with controller/policy streaming; `private` and `quarantine` disks have `serve = false`.
- **Residual risk:** The framework generic `local` disk points to the exact same physical path (`storage/app/private`) with `serve = true`, unnecessarily maintaining a framework `ServeFile` route (`GET /storage/{path}`) against the sensitive storage directory.
- **Minimum required change:** Uncouple `local` disk root to `storage_path('app/local')` and set `serve = false`. Keep `private` and `quarantine` as the sole document roots.
- **Expected tests:** Private documents still stream via authorized route, cross-owner blocked, unsigned/direct framework storage URL returns 404, path traversal rejected, admin access unchanged, result verification gate unchanged, malware scanner pipeline intact.

#### 4. SEC-022 — Provider / webhook payload minimization and retention boundary
- **Current source:** `app/Models/Payment.php`, `app/Models/WebhookEvent.php`, `app/Services/XenditPaymentProvider.php`, `app/Http/Controllers/Webhooks/XenditWebhookController.php`, `app/Services/ApplicationWorkflowService.php`.
- **Current protection:** Privileged DB access, admin UI does not expose raw payloads, logs avoid raw payloads and secrets, constant-time callback token validation, SEC-020 durable retry logic reuses stored payload.
- **Residual risk:** Storage of entire provider JSON responses and webhook payloads indefinitely, potentially containing extraneous PII or raw provider metadata beyond operational needs.
- **Minimum required change:**
  1. Minimize fields stored in `payments.provider_payload` to necessary operational fields (`id`, `reference_id`, `status`, `currency`, `amount`, `channel_code`, `actions`, `channel_properties`, `expires_at`).
  2. In `webhook_events.payload`, redact non-essential customer PII while strictly preserving all fields required by `normalize()` for SEC-020 retry and reconciliation (`reference`, `status`, `event`, `is_v3`, `amount`, `currency`, `external_ids`, `payer_email`, `channel_code`).
  3. Ensure no secrets, tokens, or headers are stored.
  4. Ensure historical full payloads remain readable and backward-compatible.
  5. Mark retention duration as `RETENTION PERIOD DEFERRED — BUSINESS/OPERATIONS CONFIRMATION REQUIRED`.
- **Expected tests:** Payment creation persists minimized payload, provider reconciliation works, webhook validation works, SEC-020 retry remains functional, required IDs/references/actions retained, secrets not stored, backward compatibility with full historical payloads, admin UI/logs do not leak raw payloads.

---

## CP1 — SEC-024 Chat / Typing / Presence Abuse Resistance

Status: COMPLETE

### Implementation Details

1. **`ChatThread::send` server-side rate limit:**
   - **Limiter key:** `chat:send:{user_id}:{thread_id}`
   - **Threshold:** 30 messages per 60 seconds per user+thread.
   - **Rationale:** Normal conversation or active typing cadence by human users rarely exceeds 5-10 messages per minute. 30 messages per minute provides ample buffer for rapid typing or multiple short messages, while strictly stopping automated burst attacks from exhausting DB connections, storage, or notifications.
   - **Failure behavior:** When exceeded, execution halts immediately before opening a database transaction. No `ChatMessage` is created, no audit log is recorded, no database notification is dispatched, no unread email job is scheduled, and thread state remains unaltered. An inline error is added to `body`: `"Terlalu banyak pesan terkirim. Mohon tunggu {$seconds} detik."`.

2. **`ChatThread::typing` update bounding:**
   - **Limiter key:** `chat:typing:{user_id}:{thread_id}`
   - **Threshold:** 60 updates per 60 seconds per user+thread.
   - **Rationale:** Typing indicators refresh cache with `TYPING_TTL_SECONDS = 5`. Humans typing continuously generate occasional input events. 60 updates per minute allows 1 update every second while stopping automated scripts from flooding the cache.
   - **Failure behavior:** When exceeded, further `Cache::put` operations are skipped silently, avoiding cache thrashing without interrupting user typing or throwing exceptions. When body is blank, cache is forgotten immediately.

3. **`POST /presence/heartbeat` route throttle:**
   - **Limiter name:** `presence-heartbeat`
   - **Throttle key:** `request->user()?->id` (authenticated) or `request->ip()` (fallback).
   - **Threshold:** 60 requests per minute.
   - **Rationale:** The web client transmits a heartbeat once every 40 seconds (`ChatPresence::HEARTBEAT_INTERVAL_SECONDS = 40`, ~1.5 req/min). 60 requests/min provides ~40x headroom above normal client cadence while protecting against HTTP request flooding.
   - **Failure behavior:** Returns HTTP 429 Too Many Requests.

### Verification

- `tests/Feature/Chat/ChatAbuseResistanceTest.php`: PASS — 9 tests, 182 assertions.
  - Normal message sending below limit is accepted.
  - Limit exceeded triggers user-friendly validation error without exceptions.
  - No message, audit record, or notification is created on rejected send.
  - Different threads have independent limits.
  - Different users have independent limits.
  - Typing updates are bounded without crashing; blank body clears typing cache immediately.
  - Heartbeat normal cadence is accepted with HTTP 204.
  - Heartbeat burst is throttled with HTTP 429.
  - Chat ownership/authorization remains authoritative.
- `tests/Feature/Chat/ChatTest.php`: PASS — 13 tests, 63 assertions (all existing chat functionality remains 100% green).

---

## CP2 — SEC-003 Legacy Payment Endpoint Validation Parity

Status: COMPLETE

### Investigation & Route Classification

- **Route:** `POST /app/applications/{publicId}/payment` (`client.applications.payment`).
- **Codebase Usage Search:**
  - Active UI (`resources/views/client/applications/show.blade.php` and `resources/views/client/payment/show.blade.php`) submits exclusively to the primary payment route `client.payments.store` (`POST /app/bayar/{publicId}`) handled by `PaymentController::store`.
  - The legacy route was only referenced in `routes/client.php:24` and `tests/Feature/Applications/ApplicationWorkflowTest.php:319,339`.
- **Classification:** `COMPATIBILITY_ONLY`.
  - In accordance with the prompt guidance, the route is preserved for backward API compatibility rather than removed, but brought into strict validation parity with the primary payment route.

### Validation Mechanism & Behavioral Changes

- **Old behavior:** `ApplicationController::payment` accepted an unvalidated generic `Request`, performed a loose `PaymentMethod::tryFrom(strtoupper(...)) ?? PaymentMethod::BCA`, silently mapping missing, unknown, or malformed method strings to BCA virtual accounts.
- **New behavior:**
  - Reuses authoritative `StorePaymentRequest` containing `Rule::in(PaymentMethod::values())`.
  - Replaces silent fallback with strict parsing `PaymentMethod::from($request->string('payment_method')->toString())`.
  - Missing `payment_method` is rejected with validation error (HTTP 302 back with session errors on `payment_method`).
  - Unknown method strings are rejected with validation error (no silent creation of BCA payment).
  - Case/shape manipulations are strictly rejected.
  - Valid methods (`BCA`, `BRI`, `QRIS`) create the intended payment and redirect with appropriate flash message.
  - Unsupported provider (`PAYPAL`) throws `PaymentGatewayException` consistent with existing provider rules.
  - Application owner scoping and `submit` policy authorization remain strictly enforced.
  - Workflow payment readiness remains enforced.

### Verification

- `tests/Feature/Payments/LegacyPaymentValidationTest.php`: PASS — 10 tests, 37 assertions.
  - Valid BCA accepted and creates payment.
  - Valid BRI accepted and creates payment.
  - Valid QRIS accepted and creates payment.
  - PayPal method follows current unsupported provider behavior.
  - Missing method rejected with validation error (0 payments created).
  - Unknown method rejected with validation error (0 payments created, no silent BCA fallback).
  - Case-manipulated method rejected strictly.
  - Cross-owner application payment remains blocked (HTTP 404).
  - Payment readiness remains enforced (draft cannot pay).
  - Empty string method produces no silent BCA fallback.
- `tests/Feature/Applications/ApplicationWorkflowTest.php`: PASS — 11 tests, 75 assertions.
- `tests/Feature/Payments/PaymentPhaseTwoBTest.php`: PASS — 18 tests, 61 assertions.
- `tests/Feature/Payments/PaymentDualWriteHardeningTest.php`: PASS — 9 tests, 64 assertions.

---

## CP3 — SEC-015 Private Storage Alias Boundary

Status: COMPLETE

### Storage Disk Usage Inventory

- A comprehensive audit of all `Storage::` references across the codebase revealed:
  - `Storage::disk('local')`: **0 active usages** in application controllers, services, jobs, or commands.
  - `Storage::disk('private')`: Exclusively used for verified, policy-protected client documents, admin uploads, and final result documents.
  - `Storage::disk('quarantine')`: Exclusively used for temporary upload quarantine and antivirus scanning before promotion to `private`.
  - Framework route `storage.local` (`GET|PUT /storage/{path}`) was previously registered because disk `local` had `'serve' => true` while pointing to the same `storage/app/private` path as disk `private`.

### Chosen Hardening Solution

1. **Uncoupled Disk Root:** Changed `'root'` of `local` disk from `storage_path('app/private')` to `storage_path('app/local')`.
2. **Disabled Framework File Serving:** Changed `'serve'` of `local` disk from `true` to `false`.
3. **Storage Route Elimination:** Verified with `php artisan route:list --name=storage` that all `storage.local` framework routes are completely deregistered.
4. **Authority Preservation:** Kept `private` (`storage/app/private`) and `quarantine` (`storage/app/quarantine`) disks as the sole, non-serving (`serve = false`), policy-gated storage authorities.

### Verification

- `tests/Feature/Files/StorageAliasBoundaryTest.php`: PASS — 8 tests, 16 assertions.
  - Configuration verification: `local` has `serve = false`, `private` has `serve = false`, and roots are strictly distinct.
  - Private documents stream exclusively through authorized controller routes (`client.documents.view`).
  - Cross-owner document access remains strictly blocked (`403 Forbidden`).
  - Unsigned direct framework storage URLs (`/storage/...`) return `404 Not Found`.
  - Path traversal attempts (`/storage/../../etc/passwd`) return `404 Not Found`.
  - Authorized admin document viewing remains functional.
  - Unverified result document gate remains enforced (client forbidden until verified).
  - Local disk functions independently for non-sensitive operations without touching private storage.
- `tests/Feature/Documents/DocumentWorkflowTest.php`: PASS — 13 tests, 41 assertions.
- `tests/Feature/Admin/AdminWorkflowTest.php`: PASS — 12 tests, 34 assertions.

---

## CP4 — SEC-022 Provider & Webhook Payload Minimization and Retention Boundary

Status: COMPLETE WITH DEFERRED RETENTION DECISION

### Baseline Payload Storage Audit

Prior to hardening:
- `webhook_events.payload`: Stored raw incoming JSON payloads verbatim. Provider callbacks potentially contain extraneous customer PII (e.g. `mobile_number`, `home_address`, `national_id`), internal routing flags, and debugging metadata.
- `payments.provider_payload`: Stored complete responses from provider checkout APIs or webhook payloads verbatim. Responses could contain customer object clones, internal provider identifiers, and non-essential transaction telemetry.
- Neither payload store should ever retain authorization tokens, shared secrets, or callback tokens.

### Minimization Implementation

1. **Xendit Payment Provider (`app/Services/XenditPaymentProvider.php`):**
   - Added `minimizePayload(array $payload): array`.
   - Strips `customer` PII entirely (customer identity is anchored to the authenticated user and application records).
   - Strips unwhitelisted channel properties and internal provider debugging tokens.
   - Retains strictly operational fields: `id`, `payment_request_id`, `reference_id`, `type`, `country`, `currency`, `request_amount`, `channel_code`, `channel_properties` (`display_name`, `expires_at`), `actions` (`action`, `type`, `descriptor`, `value`, `url`), `status`, `created`, `updated`.

2. **Xendit Webhook Controller (`app/Http/Controllers/Webhooks/XenditWebhookController.php`):**
   - Added `minimizeWebhookEventPayload(array $payload): array`.
   - Redacts extraneous customer fields while strictly preserving `customer.email` (or `payer_email`) necessary for SEC-020 cross-verification against application ownership.
   - Strips authentication headers (`x-callback-token`, `Authorization`) from any ledgered payloads.
   - Added `minimizePaymentPayload(array $payload, ?Payment $payment = null): array`.
   - Whitelists settlement verification fields: `id`, `payment_id`, `payment_request_id`, `reference_id`, `status`, `amount`, `currency`, `channel_code`, `actions`.
   - Merges and preserves existing payment action descriptors (e.g., `VIRTUAL_ACCOUNT_NUMBER`, `QR_STRING`) when webhooks arrive, ensuring customer payment instructions are not erased by webhook events.

3. **SEC-020 Normalization Compatibility:**
   - Full compatibility with existing webhook normalization logic: required fields (`status`, `amount`, `currency`, `reference_id`, `payer_email`) are preserved without schema mutation.
   - All 13 tests in `tests/Feature/Webhooks/XenditWebhookTest.php` pass without any modification.

4. **Retention Boundary & Pruning Contract:**
   - Implemented `app/Console/Commands/PrunePaymentPayloads.php` (`php artisan payments:prune-payloads`).
   - Requires explicit `--days=` input; no unapproved default retention duration is assumed or executed.
   - Simulation / dry-run mode is the safe DEFAULT (`$isDryRun = (bool) $this->option('dry-run') || ! $isForce;`). Live pruning requires explicit `--force` along with `--days=`.
   - Strictly enforces a minimum 30-day CLI safeguard against accidental operator typos or catastrophic deletion. This `>= 30 days` limit is strictly an operator command safety guard, NOT an approved BantuDaftarin business or legal retention policy.
   - Preserves SEC-020 idempotency and retryability: only terminal events (`PROCESSED` or `REJECTED` with `permanent_validation_failed`) and settled payments (`PAID`, `EXPIRED`, `FAILED`, `CANCELLED`, `REFUNDED`) are pruned. Active payments, `RECEIVED` webhook events, and retryable / non-terminal failures (`transient_processing_failed`, etc.) are strictly preserved.
   - Prunes records older than the cutoff by replacing the raw payload with a minimal tombstone (`_pruned`, `pruned_at`, `original_event_id` / `external_id`, and settled payment actions).
   - Generates an immutable audit trail (`payment_payloads.pruned` event in `audit_logs`).
   - Automatic Scheduling Status: NO automatic schedule exists (`routes/console.php` does not schedule this command; no data is deleted merely because Hardening C ran).
   - Retention Policy Status: `RETENTION PERIOD DEFERRED — BUSINESS/OPERATIONS CONFIRMATION REQUIRED`. Destructive automated scheduling and permanent retention durations are withheld pending operational/tax/legal policy confirmation.

### Verification

- `tests/Feature/Payments/PayloadMinimizationTest.php`: PASS — 11 tests, 53 assertions.
  - Payment creation stores minimized payload with required operational fields.
  - Customer PII, internal tokens, and debug metadata are stripped.
  - Webhook event redacts excessive customer PII while retaining verification email.
  - Webhook processing preserves existing payment actions (e.g. QR string, VA number).
  - No callback tokens or auth headers are stored in database.
  - Historical unminimized payloads remain backward-compatible with helper accessors.
  - Prune payloads command requires explicit `--days` option (fails if omitted without unapproved default).
  - Prune payloads command enforces `>= 30 days` command safety guard against typos.
  - Prune payloads command defaults to simulation when `--force` is not specified.
  - Prune payloads command preserves incomplete, active, or retryable webhook payloads (SEC-020 compatibility).
  - Prune payloads command force mode safely tombstones eligible payloads and records audit log.
- `tests/Feature/Webhooks/XenditWebhookTest.php`: PASS — 13 tests, 70 assertions.

---

## CP5 — Cross-Security Regression and Invariant Re-verification

Status: COMPLETE

### Regression Execution Matrix

| Test Suite Category | Test Files | Tests | Assertions | Status |
|---|---|---|---|---|
| **Hardening A Regressions** | `XenditWebhookTest`, `LivewireEligibilityRevocationTest`, `PasswordResetEnumerationTest`, `PasswordResetLocaleTest` | 22 | 102 | PASS |
| **Hardening B Regressions** | `ResultCompletionConcurrencyTest`, `ReviewAssignmentConcurrencyTest`, `OtpConcurrencyTest`, `PaymentDualWriteHardeningTest` | 24 | 95 | PASS |
| **Hardening C Targets** | `ChatAbuseResistanceTest`, `LegacyPaymentValidationTest`, `StorageAliasBoundaryTest`, `PayloadMinimizationTest` | 38 | 288 | PASS |
| **Domain & Workflow Regressions** | `ApplicationWorkflowTest`, `DocumentWorkflowTest`, `AdminWorkflowTest`, `OtpTest`, `PaymentPhaseTwoBTest` | 71 | 316 | PASS |
| **Total Regression Pass** | **17 test files** | **155** | **801** | **100% PASS** |

### Protected Invariants Re-verification

1. **State Machine & Lifecycle Graph:** No application state transitions modified. Cancelled, completed, and under-review states remain strictly governed by transition services.
2. **Access Control & Policies:** Policy gates on `Application`, `Document`, `Payment`, `ResultDocument`, and `ChatThread` remain enforced across all roles.
3. **Database Integrity:** Foreign keys and non-null constraints verified on PostgreSQL `127.0.0.1:5432`.
4. **Storage Isolation:** Private disk root `storage/app/private` completely inaccessible via public or unauthenticated web routes (`serve = false`).
5. **No Silent Fallback:** Legacy payment requests without a valid payment method fail validation; no unexpected transactions are created.
6. **Rate Limiting:** Chat messages (30/min), typing updates (60/min), and presence heartbeats (60/min) are bounded without service disruption.

---

## CP6 — Final Verification & Audit Mapping

Status: COMPLETE

### Final Verification Suite

| Verification Command | Purpose | Result | Details |
|---|---|---|---|
| `php artisan optimize:clear` | Clear framework bootstrap cache | PASS | All caches cleared cleanly |
| `php artisan view:cache` | Precompile Blade views | PASS | All views compiled successfully |
| `npm run build` | Compile frontend assets via Vite | PASS | 57 modules transformed, bundles built |
| `php artisan test --no-coverage` | Full test suite execution | PASS | **344 passed, 2,126 assertions** (100% PASS) |
| `vendor/bin/pint --test` | PSR-12 and project code styling | PASS | Formatted and clean |
| `git diff --check` | Whitespace and syntax diff check | PASS | 0 conflicts, clean diff |
| `composer audit --locked` | PHP dependencies vulnerability audit | PASS | 0 advisories |
| `npm audit --omit=dev` | Node production dependencies audit | PASS | 0 vulnerabilities |

### Security Audit Mapping

| Security ID | Finding Description | Severity | Audit Status Before | Hardening C Resolution | Status After |
|---|---|---|---|---|---|
| **SEC-024** | Authenticated chat / typing / presence abuse resistance | MEDIUM | DEFENSE_IN_DEPTH | Added per-user/thread rate limit on chat send (30/min), bounded typing updates (60/min), and named route throttle on presence heartbeats (60/min). | **RESOLVED** |
| **SEC-003** | Legacy payment endpoint validation parity | LOW | DEFENSE_IN_DEPTH | Added `StorePaymentRequest` validation parity to `ApplicationController::payment`, removed silent fallback to BCA, rejected invalid methods strictly. | **RESOLVED** |
| **SEC-015** | Serve-enabled storage alias sharing the private root | LOW | DEFENSE_IN_DEPTH | Uncoupled `local` disk root to `storage/app/local`, disabled framework file serving (`serve = false`), eliminated framework `/storage/{path}` routes. | **RESOLVED** |
| **SEC-022** | Provider/webhook payload minimization and retention boundary | LOW | DEFENSE_IN_DEPTH | Minimized stored provider response and incoming webhook payload fields; stripped PII and provider internals; provided safe manual pruning command (`payments:prune-payloads`) with explicit input requirement and dry-run default. | **RESOLVED** *(Retention Schedule Deferred)* |

### Deferred Decisions Register

| Security / Feature ID | Decision Topic | Status | Operational / Business Rationale |
|---|---|---|---|
| **SEC-012** | Application Draft Concurrency / Unique Draft Invariant | `DEFERRED — BUSINESS CONFIRMATION REQUIRED` | Deferred in Hardening B. Adding unique constraint on `(user_id, status)` or blocking multi-tab drafts would restrict valid user workflows. Requires business owner alignment. |
| **SEC-022 (Retention)** | Provider & Webhook Payload Retention Window | `RETENTION PERIOD DEFERRED — BUSINESS/OPERATIONS CONFIRMATION REQUIRED` | Production retention duration is NOT an approved policy. Command `app/Console/Commands/PrunePaymentPayloads.php` requires explicit operator `--days` (no unapproved default duration) and defaults to dry-run simulation mode. The `>= 30 days` check is strictly a command safety guard against accidental typos/catastrophic deletion, not a legal/business retention policy. Automatic scheduled deletion is absent (`routes/console.php` has no schedule; no data deleted merely because Hardening C ran) and deferred pending tax compliance, legal retention, and dispute resolution guidelines. |

### Reconciliation Declarations

- **Audit classification preserved:** YES
- **SEC-022 retention policy approved:** NO
- **Automatic destructive schedule:** NO
- **Dry-run default:** YES

### Protected Invariants Confirmation

- **Scope boundaries preserved:** No production / CSP hardening implemented (deferred to future phase); no mobile API created; role boundaries strictly CLIENT and SUPER_ADMIN.
- **State machine invariant preserved:** No application state transition rules mutated.
- **Authorization invariant preserved:** All entity routes remain policy-gated and owner-scoped.
- **Sensitive data invariant preserved:** No tokens, shared secrets, or customer PII leaked to disk, web root, or unauthenticated endpoints.

### Deliverable Summary

- **Production Source Files Modified:**
  - `app/Livewire/ChatThread.php`
  - `app/Providers/AppServiceProvider.php`
  - `routes/web.php`
  - `app/Http/Controllers/Client/ApplicationController.php`
  - `config/filesystems.php`
  - `app/Services/XenditPaymentProvider.php`
  - `app/Http/Controllers/Webhooks/XenditWebhookController.php`
  - `app/Console/Commands/PrunePaymentPayloads.php` *(new command)*
- **Test Files Created / Updated:**
  - `tests/Feature/Chat/ChatAbuseResistanceTest.php` *(new — 9 tests, 182 assertions)*
  - `tests/Feature/Payments/LegacyPaymentValidationTest.php` *(new — 10 tests, 37 assertions)*
  - `tests/Feature/Files/StorageAliasBoundaryTest.php` *(new — 8 tests, 16 assertions)*
  - `tests/Feature/Payments/PayloadMinimizationTest.php` *(new — 11 tests, 53 assertions)*
  - `tests/Feature/Applications/ApplicationWorkflowTest.php` *(parity assertion)*
  - `tests/Feature/Authentication/OtpTest.php` *(time freeze stabilization)*
- **Migrations:** NO
- **Dependencies:** NO
- **Final Classification:** `SECURITY HARDENING C RECONCILIATION COMPLETE`

---

## Post-audit correction note — 2026-09-09

This document records the historical Hardening C implementation and its test counts at that checkpoint. The targeted corrections in `docs/audit/security-hardening-bc-corrections.md` supersede the earlier SEC-022 implementation description where it was too broad:

- Payload minimization now occurs before every PostgreSQL write through a centralized explicit allowlist. Unknown top-level, data, customer, action, and channel-property fields are rejected by default; retained customer data is limited to the email needed by the existing ownership cross-check.
- `PAID` is no longer a prune candidate because it remains refund-capable. `REFUND_REQUESTED` and `REFUNDING` are also preserved. Payment pruning is limited to `FAILED`, `EXPIRED`, `CANCELLED`, and `REFUNDED` and rechecks each candidate under a row lock.
- Pruned payment tombstones no longer retain action values such as virtual-account numbers or QR strings.
- The 344-test / 2,126-assertion figures remain historical evidence for this checkpoint, not the current repository totals. Current executable counts are recorded in the correction document.
- The retention duration and any automatic schedule remain deferred; the 30-day minimum remains only a command safety floor.





