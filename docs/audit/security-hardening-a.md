# Security Hardening A

Date: 2026-09-08

Scope: SEC-020 webhook retry semantics; SEC-006/SEC-023 Livewire eligibility revocation; SEC-005 password-reset response enumeration

Audit source: `docs/audit/security-audit.md`

Production source modified: YES

Architecture changed: NO

Migration: NO expected

Dependencies: NO expected

## Progress

| Checkpoint | Status | Security IDs | Tests |
|---|---|---|---|
| CP0 Baseline | COMPLETE | SEC-020, SEC-006, SEC-023, SEC-005 extracted | 269 tests / 1,674 assertions; build and audits passed |
| CP1 Webhook retries | COMPLETE | SEC-020 | 14 tests / 77 assertions passed |
| CP2 Livewire revocation | COMPLETE | SEC-006, SEC-023 | 36 tests / 216 assertions passed |
| CP3 Password reset | COMPLETE | SEC-005 | 20 tests / 95 assertions passed |
| CP4 Regression | COMPLETE | All Hardening A targets | 149 tests / 822 assertions passed |
| CP5 Final | COMPLETE | All Hardening A targets | 282 tests / 1,740 assertions passed |

## CP0 — Baseline and target extraction

Status: COMPLETE

### Existing worktree

- Initial `git status --short`: `?? docs/audit/security-audit.md`.
- The untracked security audit predates this hardening task and will not be reverted or edited.

### Baseline verification

- `php artisan test --no-coverage`: PASS — 269 tests, 1,674 assertions, 29.17 seconds.
- `npm run build`: PASS — Vite 6.4.3, 57 modules transformed.
- `composer audit --locked`: no security vulnerability advisories found.
- `npm audit --omit=dev`: 0 vulnerabilities.

### Target mapping

| Security ID | Audit status/severity | Phase result target | Scope boundary |
|---|---|---|---|
| SEC-020 | CONFIRMED / HIGH | IMPLEMENT | Only `PROCESSED` is terminal; resume retryable incomplete events without double processing |
| SEC-006 | CONFIRMED / HIGH | IMPLEMENT | Reuse authoritative admin eligibility on Livewire update requests |
| SEC-023 | CONFIRMED / HIGH | IMPLEMENT | Reuse authoritative client and verified eligibility on Livewire update requests |
| SEC-005 | CONFIRMED / MEDIUM | IMPLEMENT | Same neutral public acknowledgement for known and unknown email |

### Explicitly out of scope

Security Hardening B: SEC-010, SEC-011, SEC-012, SEC-013, SEC-021.

Security Hardening C/D or other audit items: SEC-003, SEC-015, SEC-016, SEC-022, SEC-024, SEC-027, SEC-028, SEC-029, SEC-032.

No OTP, session, application-state, document, chat, notification-delivery, payment-provider, or payment-state architecture change is authorized.

## CP1 — SEC-020 Webhook retry semantics

Status: COMPLETE

### Root cause

`app/Http/Controllers/Webhooks/XenditWebhookController.php` previously returned HTTP 200 for every existing `(provider, event_id)` ledger row before reading its status. A durable `RECEIVED` row or a transient failure stored as `REJECTED` was therefore misreported as successfully processed, preventing Xendit retries from reconciling payment state.

### Implementation

- `app/Http/Controllers/Webhooks/XenditWebhookController.php:21-193`
  - Creates the unique ledger entry once and always processes the payload already stored in that ledger.
  - Locks the ledger row before deciding whether it is terminal or retryable.
  - Treats `PROCESSED` as the only terminal idempotent success.
  - Treats `REJECTED` with `permanent_validation_failed` as a terminal invalid event.
  - Reconciles legacy `REJECTED` rows carrying the former ambiguous `validation_or_processing_failed` marker once, then persists an explicit outcome.
  - Leaves infrastructure/internal exceptions in `RECEIVED` with `transient_processing_failed` and returns HTTP 500 so provider retry remains possible.
  - Preserves callback-token validation, original ledger payload, application-then-payment lock order, field validation, downgrade prevention, and cancelled-application late-payment handling.
- No schema change was needed; the existing `status` and `error_message` columns express the distinction.

### Retry semantics

| Stored state | Error classification | Response/behavior |
|---|---|---|
| `PROCESSED` | N/A | HTTP 200; no payment or application transition is repeated |
| `RECEIVED` | New/incomplete/transient | Ledger row is locked and its stored payload is processed again |
| `REJECTED` | `permanent_validation_failed` | HTTP 422; invalid payload is not replaced or reprocessed |
| `REJECTED` | Legacy ambiguous marker | One reconciliation attempt using the original stored payload |
| Processing throws non-domain/internal exception | `transient_processing_failed` | Transaction rolls back, ledger remains `RECEIVED`, HTTP 500 requests a retry |

### Focused verification

- `tests/Feature/Webhooks/XenditWebhookTest.php`: PASS — 13 tests, 70 assertions.
- `tests/Feature/Webhooks/XenditWebhookConcurrencyTest.php`: PASS — 1 test, 7 assertions.
- Covered duplicate `PROCESSED`, existing `RECEIVED`, retryable legacy `REJECTED`, a synthetic transient internal failure followed by successful retry, permanent invalid event, original-payload preservation, concurrent duplicate delivery, and exactly one logical payment/application transition.

## CP2 — SEC-006/SEC-023 Livewire revocation

Status: COMPLETE

### Authoritative eligibility and mechanism

- Normal admin routes remain protected by `auth` plus `App\Http\Middleware\EnsureAdmin`, which checks authentication, admin role, `users.is_active`, the related Admin profile, and `admins.is_active`.
- Normal client routes remain protected by `auth`, Laravel's `verified`, and `App\Http\Middleware\EnsureClient`, which checks role and `users.is_active` while retaining the existing authorized admin chat exception.
- `app/Providers/AppServiceProvider.php:59-63` registers `EnsureAdmin`, `EnsureClient`, and `Illuminate\Auth\Middleware\EnsureEmailIsVerified` with Livewire 4 persistent middleware.
- Livewire applies only middleware already present on the component's original page route. Public or differently scoped components do not acquire unrelated admin/client checks.
- Object policies, owner-scoped queries, document/application authorization, and chat participant checks remain intact as the second authorization layer.

### Behavior change

Before: eligibility was checked on the initial page request, but a signed component snapshot could continue after an account/admin profile was revoked.

After: the original route's eligibility middleware runs before component hydration, render, or mutation on every real `/livewire/update` request. Inactive admin users, inactive Admin profiles, inactive clients, and clients whose verification is revoked receive HTTP 403 before the requested action executes.

### Runtime-snapshot regression coverage

`tests/Feature/Security/LivewireEligibilityRevocationTest.php:23-106` loads actual admin/client pages, extracts their signed Livewire snapshots, changes eligibility, then posts the original snapshot to the real Livewire update endpoint.

- Inactive `users.is_active` admin: denied.
- Inactive `admins.is_active` profile: denied.
- Inactive client: denied before `ApplicationDetailsForm::save`.
- Verification revoked after mount: denied before mutation.
- Still-eligible admin and client snapshots: continue to update successfully.
- Registration of all three persistent middleware classes is asserted.

Focused regression suite: PASS — 36 tests, 216 assertions across eligibility, admin reactive filtering, application forms, and chat rendering/authorization.

## CP3 — SEC-005 Password-reset enumeration

Status: COMPLETE

### Root cause and correction

`app/Http/Controllers/Auth/PasswordResetController.php` previously exposed Laravel Password Broker's distinct `passwords.sent` and `passwords.user` results directly. An unauthenticated requester could therefore distinguish registered and unknown addresses from visible copy.

- `app/Http/Controllers/Auth/PasswordResetController.php:24-26` still calls `Password::sendResetLink` normally, but always exposes the same neutral acknowledgement.
- `lang/id/passwords.php:6` defines: `Jika email terdaftar, tautan untuk mengatur ulang kata sandi akan dikirim.`
- Registered accounts still receive the real project password-reset notification; unknown addresses receive none.
- Broker tokens, expiry, reset execution, notification implementation, and route-level `auth-login` limiter remain unchanged.
- Broker-level statuses, including throttling differences, are no longer exposed publicly; the route limiter still returns HTTP 429 after five requests for the same normalized email/IP key.

### Focused verification

- `tests/Feature/Authentication/PasswordResetEnumerationTest.php:15-43` proves known and unknown addresses receive the same session status, only the registered account receives a notification, and the existing request limiter remains enforced.
- `tests/Feature/Authentication/PasswordResetLocaleTest.php:17` protects the neutral Indonesian string.
- Existing token-reset and OTP/session tests remain green.
- Focused auth suite: PASS — 20 tests, 95 assertions.

## CP4 — Cross-security regression

Status: COMPLETE

Focused cross-module regression passed: 149 tests, 822 assertions.

Covered suites:

- Xendit webhook authenticity, validation, retry, concurrency, late callbacks, and idempotence.
- Payment creation/provider mappings, amount snapshots, ownership, return-state trust boundary, QRIS, failure/expiry, and refund lifecycle.
- Admin reactive filtering plus real Livewire snapshot revocation.
- Application creation, Personal/Business data forms, autosave, documents, revision, cancellation, result/payment boundaries, and COMING_SOON rejection.
- Chat ownership, unread/read behavior, edit/delete eligibility, archive/unarchive, polling-related rendering, presence, global notifier isolation, and unread-email coalescing/deep links.
- OTP, verification-before-login, password reset notification/token flow, session logout, neutral reset acknowledgement, and request throttling.

No object policy, ownership query, callback token, payment validation, state graph, OTP/session flow, chat lifecycle, queue/email delivery, or reactive filtering behavior was removed or bypassed.

## CP5 — Final verification

Status: COMPLETE

### Required commands

| Command | Result |
|---|---|
| `php artisan optimize:clear` | PASS |
| `php artisan view:cache` | PASS |
| `npm run build` | PASS — Vite 6.4.3, 57 modules; the first sandboxed attempt was denied filesystem traversal by esbuild, then the approved unrestricted rerun passed |
| `php artisan test --no-coverage` | PASS — 282 tests, 1,740 assertions |
| `vendor/bin/pint --test` | PASS |
| `git diff --check` | PASS |
| `composer audit --locked` | PASS — no advisories |
| `npm audit --omit=dev` | PASS — 0 vulnerabilities |

## Final Hardening A Status

### SEC-020

FIXED. `PROCESSED` duplicates remain idempotent; incomplete and transient events are retryable under a locked ledger row; permanent provider/input validation remains terminal. Concurrent duplicate delivery was exercised against PostgreSQL with two child processes and produced one payment/application transition.

### SEC-006

FIXED. Livewire updates originating from admin routes re-run the existing `EnsureAdmin` middleware. Deactivation of either `users.is_active` or `admins.is_active` invalidates a previously issued component snapshot before its action runs.

### SEC-023

FIXED. Livewire updates originating from client routes re-run the existing `EnsureClient` and framework email-verification middleware. Inactive or newly unverified clients cannot reuse a prior component snapshot; active verified clients continue normally.

### SEC-005

FIXED. Known and unknown reset addresses now receive the same neutral public acknowledgement while the real broker call, notification delivery for a registered account, token flow, and throttling remain unchanged.

### Files changed

- `app/Http/Controllers/Webhooks/XenditWebhookController.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Auth/PasswordResetController.php`
- `lang/id/passwords.php`
- `tests/Feature/Webhooks/XenditWebhookTest.php`
- `tests/Feature/Webhooks/XenditWebhookConcurrencyTest.php`
- `tests/Feature/Security/LivewireEligibilityRevocationTest.php`
- `tests/Feature/Authentication/PasswordResetEnumerationTest.php`
- `tests/Feature/Authentication/PasswordResetLocaleTest.php`
- `docs/audit/security-hardening-a.md`

`docs/audit/security-audit.md` was already untracked at baseline and was not edited by this hardening task.

### Change boundaries

- Migration: NO.
- Dependency: NO.
- Architecture changed: NO.
- Payment gateway changed: NO.
- Authentication mechanism changed: NO.
- Livewire architecture changed: NO; existing Livewire 4 persistent middleware was configured.
- Object-level policies removed or weakened: NO.
- OTP/session flow changed: NO.
- Payment state graph changed: NO.
- Chat/polling/presence/notifier/email coalescing changed: NO.

### Runtime/manual verification remaining

No authenticated browser acceptance was required for the server-side changes and none is claimed. Automated tests exercised real Laravel HTTP/Livewire update requests and PostgreSQL process concurrency. A staging smoke test with Xendit's retry delivery can still confirm provider-level HTTP retry timing without changing code or calling the live provider from the test suite.

## Audit mapping

| Security ID | Audit severity | Original status | Hardening A result |
|---|---|---|---|
| SEC-020 | HIGH | CONFIRMED | FIXED — status-aware locked retry semantics and concurrent idempotence |
| SEC-006 | HIGH | CONFIRMED | FIXED — admin eligibility persisted on Livewire updates |
| SEC-023 | HIGH | CONFIRMED | FIXED — client active/verified eligibility persisted on Livewire updates |
| SEC-005 | MEDIUM | CONFIRMED | FIXED — neutral password-reset acknowledgement |

## Recommended Security Hardening B scope

Proceed separately with the audit-defined Security Hardening B items only: SEC-010, SEC-011, SEC-012, SEC-013, and SEC-021. They require their own concurrency/external-call boundary design and must not be folded into Hardening A.
