# Independent Security Re-audit — Hardening B + C

Date: 2026-09-09

Repository/worktree: `C:\Users\nopal\Documents\project-bug-squasher\bantu-daftarin`

Mode: source-grounded, audit only

Authoritative finding source: `docs/audit/security-audit.md`

Implementation claims reviewed: `docs/audit/security-hardening-b.md`, `docs/audit/security-hardening-c.md`

Production source modified: NO

Audit artifact: `docs/audit/security-hardening-bc-independent-audit.md`

## Progress

| Checkpoint | Status | Scope | Evidence status |
|---|---|---|---|
| CP0 Baseline / source map | COMPLETE | Current worktree, required documents, implementation map | Baseline and source locations recorded |
| CP1 Hardening B SEC-010 | COMPLETE | Result verification/completion race | Core race fixed; retention-purge interaction remains |
| CP2 Hardening B SEC-011 | COMPLETE | Review assignment/transition | Verified against all assignment writers |
| CP3 Hardening B SEC-012 | COMPLETE | Duplicate application creation | Deferral verified; no rule introduced |
| CP4 Hardening B SEC-013 | COMPLETE | OTP issue/resend concurrency | Stable-row serialization verified |
| CP5 Hardening B SEC-021 | COMPLETE | Payment durable intent/reconciliation | Durable intent verified; new asymmetric-failure race found |
| CP6 Hardening C SEC-024 | COMPLETE | Chat/presence abuse limits | Controls verified; duplicate middleware registration noted |
| CP7 Hardening C SEC-003 | COMPLETE | Legacy payment validation | Strict parity and owner scope verified |
| CP8 Hardening C SEC-015 | COMPLETE | Storage alias | Roots, routes, and policy streaming verified |
| CP9 Hardening C SEC-022 | COMPLETE | Payload minimization/pruning | Partial minimization; raw-write and pruning-state defects found |
| CP10 Cross-hardening regression | COMPLETE | Locks, external I/O, commands, A/D compatibility | Opposing document order and residual purge race recorded |
| CP11 Final verdict | COMPLETE | Matrix, counts, required questions | Security corrections required |

## Methodology

The original audit defines each threat, severity, and intended boundary. Hardening B/C reports are treated only as claims. Each verdict is traced through the current route or command entry point, authorization, service/controller call path, transaction and lock order, schema constraints, payload fields, and existing PostgreSQL tests. Safe local tests use the dedicated PostgreSQL testing database and fake providers; no live Xendit request, real mail, production data, or destructive pruning is used.

Verdict vocabulary: `VERIFIED_CORRECT`, `VERIFIED_WITH_MINOR_CONCERN`, `PARTIALLY_RESOLVED`, `NOT_RESOLVED`, `REGRESSION_INTRODUCED`, `NEEDS_RUNTIME_VERIFICATION`, `DEFERRED_CORRECTLY`.

## CP0 — Baseline / source map

Status: COMPLETE

### Baseline

- Pre-audit `git status --short`: the worktree already contained the uncommitted Hardening B/C/D implementation and reconciliation changes. They were preserved. The complete status output was recorded during execution.
- `php artisan test --no-coverage`: PASS — **359 tests, 2,291 assertions**.
- `npm run build`: PASS — Vite 6.4.3, 57 modules transformed.
- `vendor/bin/pint --test`: PASS.
- `git diff --check`: PASS.
- `composer audit --locked`: PASS — no security advisories.
- `npm audit --omit=dev`: PASS — 0 vulnerabilities.
- Baseline deviation from the latest known value: none.

### Source map

| Area | Current implementation / evidence |
|---|---|
| Result workflow and review assignment | `app/Services/AdminWorkflowService.php`, `app/Services/ApplicationTransitionService.php`, `app/Models/Application.php`, result/application migrations, admin routes/controllers |
| Application creation and cancellation | `app/Services/ApplicationWorkflowService.php`, `app/Services/ApplicationCancellationService.php`, client controllers/requests/routes |
| OTP | `app/Services/AuthOtpService.php`, auth controllers/routes, `AuthChallenge` model/migration |
| Payment durable intent | `app/Services/ApplicationWorkflowService.php`, `app/Services/XenditPaymentProvider.php`, `app/Contracts/PaymentGateway.php`, `app/Console/Commands/ReconcilePaymentCommand.php` |
| Webhook processing | `app/Http/Controllers/Webhooks/XenditWebhookController.php`, `Payment` and `WebhookEvent` models/migrations |
| Chat/presence limits | `app/Livewire/ChatThread.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, presence controller/support class |
| Legacy payment ingress | `routes/client.php`, `app/Http/Controllers/Client/ApplicationController.php`, `app/Http/Requests/Client/StorePaymentRequest.php` |
| Storage alias | `config/filesystems.php`, private document/result controllers and policies, storage boundary tests |
| Payload minimization/pruning | Xendit provider, webhook controller, payment workflow, `app/Console/Commands/PrunePaymentPayloads.php`, `routes/console.php` |
| Concurrency and regression tests | Hardening B/C test files under `tests/Feature/Admin`, `Authentication`, `Payments`, `Chat`, `Files`, `Webhooks`, and `Security` |

### Existing worktree boundary

No pre-existing modified/untracked implementation file was reverted or normalized. The only file intentionally created by this independent re-audit is this audit artifact.

## CP1 — Hardening B SEC-010

Status: COMPLETE

## SEC-010

Original audit finding:
Concurrent result verification/rejection and application completion could leave a `COMPLETED` application without an eligible verified primary result.

Original severity:
HIGH

Original status:
POTENTIAL

Hardening report claim:
`verifyResult` and `complete` now share deterministic parent-first locking (`Application` then `ResultDocument`), recheck eligibility under lock, preserve one completion history, and prevent duplicate result notification.

Current implementation:
The principal verify/reject/complete race is closed. `verifyResult` locks the authoritative application, then the target result, and checks stage, scan status, and deletion state after both locks. `complete` locks the application and every eligible primary result before testing for a verified result and transitioning. Searches found no other production writer of `verification_status` or `ApplicationStatus::COMPLETED`.

Relevant files:
`app/Services/AdminWorkflowService.php`; `app/Services/ApplicationTransitionService.php`; `app/Services/NotificationService.php`; `app/Models/Application.php`; `app/Console/Commands/PurgeExpiredFiles.php`; `tests/Feature/Admin/ResultCompletionConcurrencyTest.php`; domain migration.

Exact line range:
`app/Services/AdminWorkflowService.php:185-223`, `226-257`; `app/Services/ApplicationTransitionService.php:17-66`; `app/Services/NotificationService.php:85-127`; `app/Models/Application.php:104-111`; `app/Console/Commands/PurgeExpiredFiles.php:20-36`; `tests/Feature/Admin/ResultCompletionConcurrencyTest.php:25-215`; `database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:299-325`.

Transaction boundary:
Verification/rejection and completion each execute in a database transaction. The nested transition and result-notification database work remain inside the outer transaction; mail notification is queued with `afterCommit` by `ResultAvailableNotification`.

Lock order:
Verification: `Application -> target ResultDocument`. Completion: `Application -> eligible primary ResultDocument rows`. The notification helper re-locks the already-owned target result. This ordering is mutually consistent for the reviewed result operations.

Database constraint:
Result public IDs and foreign keys are constrained, but no database constraint couples `applications.status = COMPLETED` to an eligible verified primary result. Correctness therefore depends on all write paths honoring the service lock boundary.

Authorization boundary:
Admin routes require `auth` + `admin`; controller actions additionally authorize `adminAction` on the application/result before invoking the service.

Tests:
Targeted suite PASS — 5 multi-process PostgreSQL tests within the 24-test Hardening B run. The tests use Laravel's process concurrency driver and the test harness rejects any database other than `pgsql` / `bantu_daftarin_mvp_test`. They assert the terminal invariant, one completion history, and serialized conflicting result decisions. They do not use a deterministic transaction barrier, so overlap timing is probable rather than proven on every run. They also do not cover `files:purge` racing completion.

Potential regression:
`files:purge` is an existing scheduled writer of `ResultDocument.deleted_at`. It deletes the physical file before setting `deleted_at` and takes neither the application lock nor a result-row lock. If an expired primary result is purged while completion holds the application lock and is about to lock/read that result, completion can accept a row whose file was already removed, or a completed application can later cease to satisfy `hasVerifiedPrimaryResult()`. Post-retention unavailability may be intended, but the concurrent physical-delete/completion window is not covered by the claimed terminal invariant. Priority: P2. Separate test gap: no deterministic barrier around the core races (P2).

Domain behavior changed:
NO for the B implementation. The existing retention-purge semantics remain separate and unresolved in relation to completion.

Architecture changed:
NO

Audit verdict:
PARTIALLY_RESOLVED

Confidence:
HIGH for source analysis; MEDIUM for runtime overlap probability.

Notes:
The original verify/reject/complete interleaving is effectively serialized. Completion history is idempotent, and result notification is deduplicated under the result lock by application/result identity. The remaining concern is not another verification-status writer; it is the unlocked retention writer changing the same eligibility predicate and physical availability.

## CP2 — Hardening B SEC-011

Status: COMPLETE

## SEC-011

Original audit finding:
Review assignment was written before the authoritative transition lock, allowing the final application/chat assignee to diverge from the transition/history actor under competing admin requests.

Original severity:
MEDIUM

Original status:
POTENTIAL

Hardening report claim:
Application eligibility, application assignment, chat assignment, and the transition/history are now serialized in one transaction after locking the application.

Current implementation:
The service re-fetches and locks the application before reading status or writing either assignment. A same-admin duplicate is idempotent. A competing admin arriving after the winner sees `UNDER_REVIEW` and is rejected. Application and chat assignment occur before the nested transition commits its status history and audit actor in the same outer transaction.

Relevant files:
`app/Services/AdminWorkflowService.php`; `app/Services/ApplicationTransitionService.php`; `app/Livewire/ChatThread.php`; `app/Http/Controllers/Admin/ApplicationController.php`; `tests/Feature/Admin/ReviewAssignmentConcurrencyTest.php`.

Exact line range:
`app/Services/AdminWorkflowService.php:29-50`; `app/Services/ApplicationTransitionService.php:17-66`; `app/Livewire/ChatThread.php:335-345`; `app/Http/Controllers/Admin/ApplicationController.php:40-47`; `tests/Feature/Admin/ReviewAssignmentConcurrencyTest.php:22-157`.

Transaction boundary:
`beginReview` wraps the parent lock, both assignment writes, and the transition/history/audit in one outer database transaction.

Lock order:
`Application -> ChatThread` (the relationship update obtains the chat-row write lock); the nested transition re-locks the already-owned application. Chat sending can lock `ChatThread`, but does not subsequently acquire the application row, so no concrete opposing `ChatThread -> Application` cycle was found.

Database constraint:
Foreign keys constrain both assignee columns. There is no cross-table equality constraint between application/chat assignees or history actor, so transactional serialization remains necessary.

Authorization boundary:
The route requires authenticated active admin middleware; the controller authorizes `adminAction` on the application. The service receives the authenticated user's admin profile.

Tests:
Targeted suite PASS — 5 tests, including a two-process PostgreSQL competing-admin test, same-admin idempotence, stale object rejection, invalid-stage rejection, and prevention of a later overwrite. The main test asserts equality of application assignee, chat assignee, history actor, and audit actor.

Potential regression:
The only other production assignment writer found is `ChatThread::assignCurrentAdmin`, which writes `chat_threads.assigned_admin_id` for an unassigned thread while the thread is locked. It does not write the application assignee and does not create a reverse lock cycle. For an application thread, a later serialized `beginReview` authoritatively aligns the chat assignment with its winning admin.

Domain behavior changed:
NO

Architecture changed:
NO

Audit verdict:
VERIFIED_CORRECT

Confidence:
HIGH

Notes:
The source and current PostgreSQL process test establish that competing admins cannot leave the final assignment and transition actor inconsistent.

## CP3 — Hardening B SEC-012

Status: COMPLETE

## SEC-012

Original audit finding:
Replayed or concurrent application creation can create multiple non-terminal drafts for one client/service; whether that is invalid depends on an unconfirmed business invariant.

Original severity:
MEDIUM

Original status:
POTENTIAL with `[BUSINESS CONFIRMATION REQUIRED]`.

Hardening report claim:
No uniqueness, reuse, or idempotency rule was introduced because the product has not answered whether one client may intentionally maintain multiple concurrent non-terminal applications for the same service.

Current implementation:
Every valid call to `createDraft` still creates a new application and its details, requirements, chat thread, consent, history, and audit inside one transaction. It does not lock client/service rows, search for an existing open application, or accept an idempotency token.

Relevant files:
`app/Services/ApplicationWorkflowService.php`; `app/Http/Controllers/Client/ApplicationController.php`; applications migration; service catalog and registration entry controllers.

Exact line range:
`app/Services/ApplicationWorkflowService.php:29-82`; `app/Http/Controllers/Client/ApplicationController.php:95-105`; `database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:71-89`.

Transaction boundary:
One transaction protects completeness of each individual draft; it does not provide request idempotency across draft creations.

Lock order:
No user/service/application row lock is acquired for creation.

Database constraint:
There are indexes on `(user_id, status)` and `(service_id, status)`, but no unique user/service/open-status constraint and no idempotency-key constraint.

Authorization boundary:
The route remains under authenticated, verified, active-client middleware; `StoreApplicationRequest` validates the request and the controller rechecks service kind/bookability.

Tests:
Existing creation/workflow tests verify transaction completeness and domain validation, not duplicate-request prevention. That is consistent with a consciously deferred control rather than evidence of resolution.

Potential regression:
None. The current code did not silently restrict legitimate multiple-application behavior.

Domain behavior changed:
NO

Architecture changed:
NO

Audit verdict:
DEFERRED_CORRECTLY

Confidence:
HIGH

Notes:
The unresolved question remains verbatim: “May one client intentionally maintain more than one simultaneously non-terminal application for the same service?”

## CP4 — Hardening B SEC-013

Status: COMPLETE

## SEC-013

Original audit finding:
OTP issue/resend performed cooldown check, invalidation, creation, and notification without one stable serialization boundary, permitting concurrent requests to issue more than one usable challenge.

Original severity:
LOW

Original status:
POTENTIAL

Hardening report claim:
Issuance now locks the user row, rechecks cooldown after the lock, atomically invalidates old challenges and creates one replacement, then dispatches the queued email after commit.

Current implementation:
Both initial issue and resend enter `AuthOtpService::issue`. The service locks the stable user row, runs the type-scoped cooldown query after locking, invalidates all unused challenges for that user/type, creates one hashed challenge, and records its audit event before commit. `resend` is a direct delegation, so no alternate issuance path bypasses the boundary.

Relevant files:
`app/Services/AuthOtpService.php`; `app/Http/Controllers/Auth/AuthController.php`; `app/Notifications/LoginOtpNotification.php`; `app/Enums/AuthChallengeType.php`; `config/auth_otp.php`; auth routes; auth-challenge migration; `tests/Feature/Authentication/OtpConcurrencyTest.php`.

Exact line range:
`app/Services/AuthOtpService.php:18-114`; `app/Http/Controllers/Auth/AuthController.php:48-76`, `95-122`; `app/Notifications/LoginOtpNotification.php:12-43`; `config/auth_otp.php:3-7`; `routes/web.php:38-45`; `database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:22-37`; `tests/Feature/Authentication/OtpConcurrencyTest.php:21-175`.

Transaction boundary:
Issue/invalidate/create/audit is one transaction. `DB::afterCommit` dispatches `LoginOtpNotification`; the notification implements `ShouldQueue` and also requests after-commit delivery. Actual mail transport is not executed while the user row is locked. Verification uses a separate transaction that locks only its challenge row, then performs login/session regeneration after commit.

Lock order:
Issue/resend: `User -> write/update matching AuthChallenge rows`. Verify: `AuthChallenge` only. Verification releases its challenge lock before updating admin last-login/session state; no concrete `AuthChallenge -> User` transaction path was found, so there is no opposing cycle.

Database constraint:
There is no partial unique constraint for one unused challenge per user/type. The stable user lock is therefore the authoritative serialization mechanism.

Authorization boundary:
Login requires valid password, active and verified user, and active admin profile where applicable. Verify binds the challenge to the session user and challenge type. Route limits remain 5/min login, 10/min verify, and 3/min resend; cooldown remains 60 seconds and maximum attempts remain five.

Tests:
Targeted suite PASS — 5 tests, including two-process issue/issue and issue/resend races, challenge single-use verification, replacement invalidation, and attempt locking. PostgreSQL is enforced by the shared test guard. The process driver has no explicit barrier, but the stable-row lock and post-lock query independently establish the invariant.

Potential regression:
None found. Admin/client challenge values remain separate, old challenges become unusable, route limits and session rotation are unchanged, and no actual mail is sent under a database lock.

Domain behavior changed:
NO

Architecture changed:
NO

Audit verdict:
VERIFIED_CORRECT

Confidence:
HIGH

Notes:
Concurrent issuance cannot bypass the authoritative cooldown through the reviewed service paths or leave multiple intended usable challenges.

## CP5 — Hardening B SEC-021

Status: COMPLETE

## SEC-021

Original audit finding:
The provider call occurred inside a rollback-capable transaction, so provider success followed by local rollback could lose the reference, orphan the provider instruction, and create a second logical payment on retry.

Original severity:
HIGH

Original status:
POTENTIAL

Hardening report claim:
Payment creation now uses three phases: durable local intent committed under application/payment serialization, provider call outside a database transaction using the durable idempotency reference, and locked local reconciliation. A command can reconcile an interrupted pending intent.

Current implementation:
The durable-intent core is real: the payment row and unique `BD-{UUID}` reference commit before the provider call; the browser cannot supply that reference; Xendit receives it as `reference_id` and `idempotency-key`; webhook lookup uses the same local `reference_id`. Local retry reuses an unexpired pending payment. The provider call is outside any database transaction.

Relevant files:
`app/Services/ApplicationWorkflowService.php`; `app/Services/XenditPaymentProvider.php`; `app/Services/PaymentGatewayRouter.php`; `app/Console/Commands/ReconcilePaymentCommand.php`; `app/Http/Controllers/Webhooks/XenditWebhookController.php`; `app/Services/ApplicationCancellationService.php`; `app/Enums/PaymentStatus.php`; payment migration; `tests/Feature/Payments/PaymentDualWriteHardeningTest.php`.

Exact line range:
`app/Services/ApplicationWorkflowService.php:187-334`; `app/Services/XenditPaymentProvider.php:22-95`; `app/Console/Commands/ReconcilePaymentCommand.php:9-30`; `app/Http/Controllers/Webhooks/XenditWebhookController.php:93-190`; `app/Services/ApplicationCancellationService.php:53-94`; `app/Enums/PaymentStatus.php:5-24`; `database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:199-216`; `tests/Feature/Payments/PaymentDualWriteHardeningTest.php:26-233`.

Transaction boundary:
Phase 1 commits the application transition/pending payment before network I/O. Phase 2 invokes the gateway without a transaction. Failure handling and Phase 3 each use a payment-row transaction. `reconcilePayment` likewise invokes the provider before its payment-row transaction.

Lock order:
Create Phase 1: `Application -> pending Payment`. Cancellation: `Application -> all Payments`. Webhook: `WebhookEvent -> Application -> Payment`. Phase 3/reconcile/failure handling: `Payment` only. No direct two-row reverse cycle was found among these paths; however, provider calls are intentionally outside locks and concurrent callers can act on the same durable intent.

Database constraint:
`payments.reference_id` and non-null `payments.public_id` are unique; `external_id` is nullable unique. There is no database uniqueness rule limiting one pending payment per application, so the application lock is essential.

Authorization boundary:
Browser creation routes are authenticated, verified, active-client routes, owner-scope application lookup, `submit` policy, strict method validation, server-side amount/currency snapshot, and service payment readiness. `payments:reconcile` is CLI-only and accepts an exact canonical `reference_id`; it has no HTTP route.

Tests:
Targeted Hardening B suite PASS — `PaymentDualWriteHardeningTest` contributes 9 tests. It proves reference persistence/reuse, one local pending payment under process concurrency, provider-failure marking, webhook compatibility, and command behavior with the fake provider. The named “provider success with local persistence interruption” test does not inject failure after an actual provider success: it manually creates an incomplete local payment and then retries. It therefore proves recovery from a seeded interrupted shape, but not the claimed provider-success/local-write failure boundary or asymmetric concurrent provider outcomes. The 24-test Hardening B group currently reports **94 assertions**, while Hardening B documentation claims 95 in its summary and 40 assertions for this file; those counts are stale.

Potential regression:
P1 implementation bug introduced by moving the provider call outside the application lock: two concurrent create/reconcile attempts can share one pending intent and both call the provider. If caller A receives success while caller B receives a transient exception, B can lock first and mark the still-blank pending payment `FAILED` (`ApplicationWorkflowService.php:270-275`). A then writes the successful provider ID/checkout payload without rechecking or restoring status (`282-294`), leaving a provider-backed payment in terminal local `FAILED`. A later paid webhook cannot transition `FAILED -> PAID` because `PaymentStatus::canTransitionTo` permits no transition from `FAILED`; `reconcilePayment` also returns immediately for non-`PENDING`. The bad ordering is not covered by existing tests. This does not lose the durable reference or create a second logical idempotency key, but it can still strand provider truth locally.

Domain behavior changed:
NO intended change; YES, potentially, in the new concurrent asymmetric-failure ordering described above.

Architecture changed:
NO (bounded orchestration refactor within the existing gateway design).

Audit verdict:
REGRESSION_INTRODUCED

Confidence:
HIGH for the source-level interleaving; MEDIUM for provider/runtime frequency.

Notes:
`payments:reconcile` cannot fabricate `PAID`, cannot downgrade an existing paid row, and never changes application status. It does call the provider before locking/rechecking the payment and does not check application cancellation, so it may make an unnecessary idempotent provider call for a stale/non-pending or cancelled application; this is a P2 operational concern. It records `payment.checkout_created` only when it updates a still-pending row and prints no secret. The original durable-reference loss is fixed, but Hardening B is not fully verified because its new unlocked external phase has an uncovered failure race.

## CP6 — Hardening C SEC-024

Status: COMPLETE

## SEC-024

Original audit finding:
Authenticated chat send/typing and presence heartbeat writes lacked an application-side abuse-rate boundary.

Original severity:
MEDIUM

Original status:
DEFENSE_IN_DEPTH

Hardening report claim:
Send is limited to 30/min per user+thread, typing to 60/min per user+thread, and presence heartbeat to 60/min per user (IP fallback), without changing chat semantics.

Current implementation:
All three controls exist with the claimed thresholds and keys. Send resolves and authorizes the thread before the limiter, then rejects before the transaction, message/audit/database notification, and unread-email scheduling. Typing authorizes the thread and only writes a five-second cache key under quota; blank input clears it immediately. Presence remains authenticated and active-role checked before writing its existing cache heartbeat.

Relevant files:
`app/Livewire/ChatThread.php`; `app/Providers/AppServiceProvider.php`; `routes/web.php`; `app/Http/Controllers/PresenceHeartbeatController.php`; `app/Support/ChatPresence.php`; `tests/Feature/Chat/ChatAbuseResistanceTest.php`; `tests/Feature/Chat/ChatTest.php`.

Exact line range:
`app/Livewire/ChatThread.php:20-125`, `280-345`; `app/Providers/AppServiceProvider.php:64-83`; `routes/web.php:46-52`; `app/Http/Controllers/PresenceHeartbeatController.php:10-25`; `app/Support/ChatPresence.php:15-21`, `57-67`; `tests/Feature/Chat/ChatAbuseResistanceTest.php:28-259`.

Transaction boundary:
Limiter checks execute before chat's database transaction. Typing and presence use cache only. No rejected send reaches the thread transaction or unread-email scheduling.

Lock order:
Accepted send locks `ChatThread`; downstream unread scheduling uses its thread/user state after the thread lock. The limiter itself takes no database lock.

Database constraint:
No schema change was needed. Existing chat ownership/thread/user-state constraints remain authoritative; rate-limit state is cache-backed.

Authorization boundary:
`thread()` resolves by public ID and calls `assertThreadAccess` before limiter evaluation. A client can only move the public Livewire property to another thread they own; each intentionally has an independent quota. Presence requires `auth` and rechecks active client/admin role state.

Tests:
Targeted C suites PASS — `ChatAbuseResistanceTest` 9 tests and `ChatTest` 13 tests. They cover the 31st-send rejection, no message/audit/database-notification on pre-exhausted rejection, user/thread isolation, typing bounding and clear, normal/burst heartbeat, ownership, quick replies, unread/read, and ordinary chat behavior. Source placement additionally proves unread-email scheduling is skipped, although the abuse test does not directly inspect its queued job/state.

Potential regression:
P3 maintainability/runtime concern: `routes/web.php:48-51` registers `ReadOnlySession` and `auth` twice through two consecutive `middleware(...)` calls, with the second adding the throttle. Current tests pass and the throttle is effective, but the duplicated read-only session start is not minimal and can complicate future middleware behavior. No polling, unread, archive, presence-threshold, notifier, or email-coalescing semantics changed.

Domain behavior changed:
NO

Architecture changed:
NO

Audit verdict:
VERIFIED_WITH_MINOR_CONCERN

Confidence:
HIGH

Notes:
The limiter response exposes only remaining wait seconds, not sensitive implementation state. Identical legitimate messages remain allowed because the limit is count-based, not content-based.

## CP7 — Hardening C SEC-003

Status: COMPLETE

## SEC-003

Original audit finding:
The legacy owner payment endpoint accepted an unvalidated method and silently fell back missing/unknown input to BCA.

Original severity:
LOW

Original status:
DEFENSE_IN_DEPTH

Hardening report claim:
The compatibility endpoint now uses the same `StorePaymentRequest` and strict enum parsing as the primary endpoint, retaining ownership, readiness, and durable-payment controls.

Current implementation:
`ApplicationController::payment` type-hints `StorePaymentRequest`, parses with `PaymentMethod::from`, owner-scopes the application lookup, authorizes `submit`, rechecks cancellation, and delegates pricing/state/provider handling to the same `ApplicationWorkflowService::createPayment` used by the primary route. No `?? BCA`, `tryFrom(... ) ?? BCA`, or equivalent fallback remains at this ingress.

Relevant files:
`routes/client.php`; `app/Http/Controllers/Client/ApplicationController.php`; `app/Http/Controllers/Client/PaymentController.php`; `app/Http/Requests/Client/StorePaymentRequest.php`; `app/Services/ApplicationWorkflowService.php`; `tests/Feature/Payments/LegacyPaymentValidationTest.php`.

Exact line range:
`routes/client.php:13-26`; `app/Http/Controllers/Client/ApplicationController.php:145-159`, `191-196`; `app/Http/Controllers/Client/PaymentController.php:47-61`; `app/Http/Requests/Client/StorePaymentRequest.php:9-20`; `app/Services/ApplicationWorkflowService.php:187-260`; `tests/Feature/Payments/LegacyPaymentValidationTest.php:35-182`.

Transaction boundary:
Validation/authorization occurs before the durable-payment transaction. The shared workflow then uses the same Phase 1/2/3 boundary audited under SEC-021.

Lock order:
Shared payment creation locks `Application -> pending Payment` after request validation.

Database constraint:
Payment reference/external IDs remain unique; the request fix required no schema change.

Authorization boundary:
The route is inside `auth`, `verified`, `client`; lookup is explicitly scoped to the authenticated user's ID and the `submit` policy is enforced.

Tests:
Targeted suite PASS — 10 tests covering BCA/BRI/QRIS, unsupported PayPal, missing/empty/unknown/wrong-case values, cross-owner denial, and readiness. No invalid input creates a fallback payment.

Potential regression:
The route is `COMPATIBILITY_ONLY`: scoped search found no active Blade/UI submission to it; active payment UI uses `client.payments.store`. Maintaining two controller ingresses remains a future drift risk, but current validation, policy, and workflow behavior are aligned.

Domain behavior changed:
NO, except the intended rejection of invalid input that previously fell back.

Architecture changed:
NO

Audit verdict:
VERIFIED_CORRECT

Confidence:
HIGH

Notes:
Service prices, payment truth, cancellation gating, and provider availability remain server-authoritative.

## CP8 — Hardening C SEC-015

Status: COMPLETE

## SEC-015

Original audit finding:
The framework's generic serve-enabled `local` disk shared the physical private-document root, leaving an unnecessary framework storage-serving surface adjacent to sensitive files.

Original severity:
LOW

Original status:
DEFENSE_IN_DEPTH

Hardening report claim:
`local` now uses `storage/app/local` with serving disabled; `private` and `quarantine` remain distinct, non-serving, policy-controlled roots.

Current implementation:
Configuration exactly matches the claim. Active application storage calls use only `private` and `quarantine`; `Storage::disk('local')` appears only in the storage-boundary test. No active `Storage::url` or `temporaryUrl` path for sensitive documents was found. `php artisan route:list --name=storage` reports no matching framework storage route.

Relevant files:
`config/filesystems.php`; `app/Services/DocumentFileService.php`; `app/Services/AdminWorkflowService.php`; client/admin document and result controllers; `app/Policies/DocumentPolicy.php`; `app/Policies/ResultDocumentPolicy.php`; `tests/Feature/Files/StorageAliasBoundaryTest.php`.

Exact line range:
`config/filesystems.php:31-64`, `92-94`; `app/Policies/DocumentPolicy.php:8-32`; `app/Policies/ResultDocumentPolicy.php:8-22`; `tests/Feature/Files/StorageAliasBoundaryTest.php:76-204`.

Transaction boundary:
Not applicable to disk configuration. File workflow promotes quarantine content to private storage only after validation/scanning; controller streaming remains policy-gated.

Lock order:
Not applicable to the alias change.

Database constraint:
Stored disk/path metadata is database-backed, but private access depends on policy-gated controllers plus non-serving disk configuration rather than a schema constraint.

Authorization boundary:
Private document/result delivery still authorizes the resource. Document download additionally requires passed scan, active-or-admin, and not deleted. Client result access requires ownership, not-deleted, and `VERIFIED`; admin access remains role-authorized.

Tests:
Targeted suite PASS — 8 tests covering root separation, `serve=false`, authorized streaming, cross-owner denial, direct `/storage` 404, traversal 404, admin access, and result verification gate. The absence of a generic route is treated as one layer; policy tests independently cover object authorization.

Potential regression:
None found. The public disk remains separate at `storage/app/public`; sensitive workflow paths have not migrated to generic local storage.

Domain behavior changed:
NO

Architecture changed:
NO

Audit verdict:
VERIFIED_CORRECT

Confidence:
HIGH

Notes:
No generic serving surface in current routes shares the private or quarantine roots.

## CP9 — Hardening C SEC-022

Status: COMPLETE

## SEC-022

Original audit finding:
Full provider checkout responses and authenticated webhook payloads were retained indefinitely without an explicit minimization or approved retention boundary, enlarging database/backup privacy exposure.

Original severity:
LOW

Original status:
DEFENSE_IN_DEPTH

Hardening report claim:
Provider and webhook payloads are minimized to operational/retry fields, customer PII and provider internals are stripped, a safe dry-run-default pruning command exists, retryable events are preserved, and retention duration/scheduling remain deferred.

Current implementation:
Checkout-response minimization is an allowlist and removes a top-level `customer` object and unknown fields. Payment payload constructed from webhook data is partially normalized. Webhook-ledger minimization is not an allowlist: it clones the entire payload and only reduces `data.customer` to `email`, leaving every other top-level/nested field intact. The webhook payment-update path also saves the raw payload once before saving the minimized payload in the same transaction. Current final row state is minimized, but PostgreSQL still receives a raw JSON write/version.

Relevant files:
`app/Services/XenditPaymentProvider.php`; `app/Http/Controllers/Webhooks/XenditWebhookController.php`; `app/Services/ApplicationWorkflowService.php`; `app/Console/Commands/PrunePaymentPayloads.php`; `app/Console/Commands/ReconcilePaymentCommand.php`; `app/Models/Payment.php`; `app/Models/WebhookEvent.php`; `app/Enums/PaymentStatus.php`; `routes/console.php`; operations runbook; `tests/Feature/Payments/PayloadMinimizationTest.php`; `tests/Feature/Webhooks/XenditWebhookTest.php`.

Exact line range:
`app/Services/XenditPaymentProvider.php:89-149`; `app/Http/Controllers/Webhooks/XenditWebhookController.php:46-60`, `121-170`, `223-320`; `app/Services/ApplicationWorkflowService.php:289-295`, `319-329`; `app/Console/Commands/PrunePaymentPayloads.php:18-136`; `app/Console/Commands/ReconcilePaymentCommand.php:11-30`; `app/Enums/PaymentStatus.php:16-24`; `routes/console.php:1-12`; `docs/08-operations/runbook.md:106-117`; `tests/Feature/Payments/PayloadMinimizationTest.php:38-471`.

Transaction boundary:
Webhook ledger creation occurs before event processing. Event processing locks the ledger/application/payment in one transaction. Within that transaction, payment updates perform a raw `provider_payload` save followed by a minimized save (`XenditWebhookController.php:160-170`). Pruning updates rows outside an explicit encompassing transaction or row lock. Reconciliation does not depend on `provider_payload`; it uses durable payment columns.

Lock order:
Webhook processing: `WebhookEvent -> Application -> Payment`. Pruning uses chunked reads and per-model updates without re-lock/recheck. Reconciliation locks `Payment` only after its provider call.

Database constraint:
Webhook `(provider, event_id)`, payment reference, and payment external ID uniqueness remain intact. JSON payload columns are neither encrypted nor field-constrained.

Authorization boundary:
Webhook storage occurs only after constant-time callback-token validation. Both payload commands are CLI-only. `payments:prune-payloads` requires explicit `--days`, defaults to dry-run unless `--force` is present, rejects values under 30 days, prints counts rather than secrets, and writes an audit event after destructive execution.

Tests:
Targeted suite PASS — 11 payload tests plus 13 webhook tests. They prove final-row redaction of sample nested customer fields, callback/auth headers absent, UI action compatibility, historical shape accessors, dry-run/default/force guards, and preservation of `RECEIVED` plus transiently rejected events. They do not detect transient raw database writes, arbitrary sensitive fields outside `data.customer`, unfiltered nested webhook `actions`, retained name fields, or pruning racing/preceding the `PAID -> REFUND_REQUESTED` transition.

Potential regression:
Three implementation defects remain. (1) P1/P2 privacy: raw webhook payment payload is explicitly saved before its minimized replacement, defeating a strict “never persist raw payload” boundary at the database/WAL level. (2) P2 minimization: webhook ledger payloads copy all fields except most of one nested customer object; webhook-derived payment `actions` are not key-filtered, and `customer_name`/`display_name` are retained although no active reader uses them. (3) P2 pruning correctness: the command treats `PAID` as a terminal payment state, while `PaymentStatus::canTransitionTo` explicitly permits `PAID -> REFUND_REQUESTED`. It also does not lock/recheck each payment after chunk selection, so an active refund transition can race a forced prune. The report's “settled / terminal” classification is therefore inaccurate.

Domain behavior changed:
NO for ordinary payment/webhook state semantics. A forced prune can reduce provider evidence for a paid/refund-eligible record; no automatic run currently occurs.

Architecture changed:
NO

Audit verdict:
PARTIALLY_RESOLVED

Confidence:
HIGH

Notes:
Current field inventory:

| Store | Current retained field/shape | Classification |
|---|---|---|
| Checkout `provider_payload` | IDs/reference/status/type/country/currency/amounts/channel/expiry/timestamps/description | Required for operation, reconciliation display, or audit; `description` has no active reader and is an audit-only candidate |
| Checkout `provider_payload.actions` | action/type/descriptor/url/value/QR string/VA number | Required for current payment UI and instruction continuity |
| Checkout `provider_payload.channel_properties` | expiry plus `customer_name`/`display_name` | Expiry operational; names are `UNNECESSARY_PII` in current source because no active reader was found |
| Webhook-event payload | Entire authenticated payload except `data.customer` reduced to email | Required retry/validation fields are present, but the broad remainder is `UNKNOWN` or potentially `UNNECESSARY_PII` |
| Webhook-event customer email / legacy payer email | Email used by current payer-identity validation | `REQUIRED_FOR_RETRY` and `REQUIRED_FOR_RECONCILIATION` under current validation |
| Webhook-derived payment payload | ID/reference/status/currency/amount/channel/actions/expiry/updated and optional name properties | Core fields operational/audit; unfiltered actions and names include `UNKNOWN`/`UNNECESSARY_PII` surface |

Pruning and reconciliation sets do not overlap for ordinary reconciliation: `payments:reconcile` accepts only `PENDING`; pruning excludes `PENDING` and preserves `RECEIVED`/transient webhook events. Force mode tombstones payload only and preserves rows, statuses, references, event IDs, and application status. No source, scheduler, CI, or deployment search found an automatic `payments:prune-payloads` invocation; `schedule:list` shows only daily `files:purge`. The 30-day minimum is documented as a command safety floor, not an approved retention policy. A local no-force `--days=30` run was dry-run only, found zero eligible rows, and modified nothing.

## CP10 — Cross-hardening regression

Status: COMPLETE

### Global lock-order audit

| Path | Current lock/write order | Assessment |
|---|---|---|
| Result verification | `Application -> ResultDocument` | Consistent with result completion |
| Application completion | `Application -> eligible ResultDocument rows` | Consistent with result verification |
| Review assignment | `Application -> ChatThread` | No active reverse `ChatThread -> Application` lock path found |
| Application transition | `Application` | Nested calls reacquire the same parent row; no reverse child-first order found in this path |
| Payment creation | `Application -> Payment` in phase 1; `Payment` only in phases 2/3 | Provider call is outside the database transaction, but phase-3 status validation is incomplete (SEC-021) |
| Cancellation | `Application -> Payment rows` | Consistent with the main application/payment path |
| Webhook | `WebhookEvent -> Application -> Payment` | No current path holding `Payment` and then waiting for `WebhookEvent`; however this order is not globally parent-first |
| OTP issue | `User -> AuthChallenge writes` | Stable-parent serialization; verification locks only the selected challenge |
| Chat send/email delivery | `ChatThread -> ChatThreadUserState` | Consistent within chat; actual notification delivery is after the lock transaction |
| Client document upload | `Application -> ApplicationRequirement -> active Document writes` | Opposes document review order |
| Admin document review | `Document -> Application -> ApplicationRequirement write` | Opposes upload order and creates a concrete deadlock cycle |
| Expired-file purge | No row locks; physical delete before database tombstone | Can race result completion/verification and document access |

Concrete opposing pair: `DocumentWorkflowService::upload()` locks `Application`, then `ApplicationRequirement`, then updates active `Document` rows (`app/Services/DocumentWorkflowService.php:39-55`). `review()` locks `Document`, then `Application`, then updates the requirement (`app/Services/DocumentWorkflowService.php:88-123`). Concurrent upload/review of the same requirement can therefore form `Application waits Document` / `Document waits Application`. This predates Hardening B/C, but the current Hardening B claim that critical workflows were reconciled to deterministic parent-first ordering is too broad. This is a **P2 residual concurrency defect**, not a regression introduced by the reviewed patch.

### Transaction and external-I/O audit

| Operation | External/slow work relative to transaction | Result |
|---|---|---|
| Payment checkout | Provider request after durable intent transaction and before final persistence transaction | Correct direction, but asymmetric success/failure handling is unsafe (SEC-021) |
| Payment reconciliation | Provider lookup before the payment row lock | No long provider call under lock; invokes provider before confirming the latest locked state/application eligibility |
| Document/result file scan | Quarantine write and malware scan before database transaction | No scanner/provider wait under database row locks |
| File purge | Physical storage delete followed by database update, without row lock | Filesystem and database state can diverge/race |
| OTP notification | Registered with `DB::afterCommit`; notification is queued | No mail transport under the user lock |
| Result/application notifications | Database notification intent may be created during workflow transaction; queued notification objects use `afterCommit` | No synchronous mail transport under row locks |
| Chat unread email scheduling | Pending token is saved while caller holds the thread transaction; delayed job dispatch uses `afterCommit` | Coalescing marker and job publication preserve commit order |
| Chat unread email delivery | `ChatThread -> ChatThreadUserState` transaction resolves recipient/context, then sends notification after commit (`ChatUnreadEmailService.php:58-100`) | No mail transport under chat locks |
| Webhook notifications | Queued/after-commit notifications from webhook workflow | No provider or mail round-trip under webhook row locks |

No new synchronous Xendit, mail, malware-scanner, or storage-copy call was found inside the newly hardened B/C row-lock sections. The material transaction regressions are state reconciliation and lock ordering rather than lock duration.

### Operational command security

| Command | Safe default / scope | Finding |
|---|---|---|
| `payments:reconcile {reference}` | Exact reference required; only PENDING payment is mutated; never marks PAID, downgrades application state, or reopens a record | Source-safe but operator-invoked provider lookup happens before locked recheck; asymmetric FAILED payment from SEC-021 cannot be recovered |
| `payments:prune-payloads --days=N [--force]` | `--days` required, values below 30 rejected, dry-run by default, CLI only, force writes an audit event | Safe invocation boundary; eligibility incorrectly includes refund-capable PAID records and lacks per-row lock/recheck |
| `files:purge` | Scheduled daily | Existing no-lock physical-delete-first race with result/document workflows remains |

No destructive prune was executed. The audit ran only `payments:prune-payloads --days=30` without `--force`; it reported zero eligible rows and made no changes. No live provider request or real email was sent.

### Hardening A and D compatibility

- Hardening A regression suite PASS: webhook retry/idempotency, Livewire eligibility revocation, and password-reset enumeration/locale behavior remained intact.
- Hardening D regression suite PASS: malware-scanner production guard, production-security command, CSP/security headers, and general security regression coverage remained intact.
- Targeted A/D compatibility result: **42 tests, 292 assertions**, all passing.
- Hardening B/C did not weaken the callback token check, password-reset neutral response, private storage policies, eligibility middleware, scanner production guard, CSP, session/cookie checks, or production configuration validator.

### Test quality and documentation accuracy

Implementation defects and test-coverage gaps are separated below:

| Type | Finding |
|---|---|
| Implementation defect | SEC-021 phase-3 persistence can attach a successful checkout to a payment already changed to FAILED by a competing failed provider attempt, leaving an unrecoverable FAILED payment |
| Implementation defect | SEC-022 writes raw webhook payment payload before overwriting it with minimized data; webhook-ledger minimization remains broad; forced pruning may target PAID/refund-capable payments |
| Implementation defect | Result/file purge and opposing document lock order remain outside the claimed concurrency safety boundary |
| Minor implementation concern | Presence route registers the same session/auth middleware twice |
| Test gap | Concurrency tests use real parallel processes but no deterministic barrier, so they prove observed outcomes rather than every adversarial interleaving |
| Test gap | No asymmetric payment-provider success/failure race test, purge-vs-completion test, upload-vs-review deadlock test, raw-intermediate-payload test, arbitrary-payload allowlist test, or prune-vs-refund test exists |
| Documentation mismatch | Hardening B targeted tests currently pass **24 tests, 94 assertions**, not the documented 95-assertion total |
| Documentation overstatement | Hardening B describes payment durable-intent recovery as closing the failure window, but reconcile is PENDING-only and cannot recover the new FAILED-plus-external-ID state |
| Documentation overstatement | Hardening C says PII/provider internals are stripped, while broad webhook fields, actions, and unnecessary names remain and a raw payment-payload write still occurs |
| Documentation overstatement | “Settled/terminal” pruning language includes PAID even though the enum permits `PAID -> REFUND_REQUESTED` |

### Product, domain, and architecture preservation

- Scope remains NPWP Perseorangan and NPWP Badan Usaha; Lapor Pajak remains COMING_SOON.
- Application, document, payment, result, cancellation, unread, archive, presence, and email-recipient rules were not changed by this audit.
- Blade/Livewire/server-rendered architecture remains unchanged; no SPA, WebSocket, or frontend API architecture was introduced.
- Private document/result authorization remains policy-gated and non-public.
- Database uniqueness constraints, public IDs, payment/provider identity checks, and PostgreSQL as the authoritative datastore remain in place.
- This independent audit modified no production source, test, schema, route, configuration, dependency, or previous hardening report.

## CP11 — Final verdict

Status: COMPLETE

### B/C verification matrix

| ID | Original finding | Hardening claim | Source verdict | Test verdict | Regression | Final verdict |
|---|---|---|---|---|---|---|
| SEC-010 | Completion could race result verification/rejection | Parent-first locks, invariant recheck, idempotent history/notification | Core admin paths are correct; scheduled purge can later invalidate the verified-primary-result predicate | 5 concurrency tests pass; no purge interleaving case | No B regression; pre-existing residual path | `PARTIALLY_RESOLVED` |
| SEC-011 | Concurrent review start could split assignment/actor | Application lock serializes assignment and transition | Assignment and transition actor remain atomic; no active reverse lock path found | 5 concurrency tests pass | None found | `VERIFIED_CORRECT` |
| SEC-012 | Duplicate active drafts | Explicitly deferred pending product rule | No uniqueness/idempotency rule added; duplicate creation remains possible | Existing workflow behavior passes | None; correctly unchanged | `DEFERRED_CORRECTLY` |
| SEC-013 | Concurrent OTP issue/resend could bypass cooldown | Stable user-row lock and in-lock cooldown recheck | User serialization closes issuance race; challenge use remains single-use | 5 concurrency tests pass | None found | `VERIFIED_CORRECT` |
| SEC-021 | Provider success could roll back without durable local intent | Durable reference before provider I/O plus reconciliation command | Durable reference is real, but competing provider failure can mark the shared payment FAILED before a success persists, creating an unrecoverable state | 9 hardening tests pass; asymmetric outcome is untested | **New integrity regression** | `REGRESSION_INTRODUCED` |
| SEC-024 | Chat/presence abuse had insufficient bounds | Per-user/thread rate limits and presence throttle | Limits precede side effects and preserve normal behavior; heartbeat middleware is duplicated | 9 abuse tests plus chat/presence suites pass | Minor redundant middleware only | `VERIFIED_WITH_MINOR_CONCERN` |
| SEC-003 | Legacy payment route silently defaulted invalid method | Strict FormRequest/enum parity | Invalid/missing/case-manipulated values fail; owner/policy/readiness checks remain | 10 tests pass | None found | `VERIFIED_CORRECT` |
| SEC-015 | Generic local storage root overlapped sensitive storage | Separate non-serving roots and policy streaming | Roots/routes/policies are distinct; no generic sensitive serving path found | 8 storage-boundary tests pass | None found | `VERIFIED_CORRECT` |
| SEC-022 | Raw provider/webhook payload retained indefinitely | Minimize fields and add guarded pruning/reconciliation compatibility | Checkout allowlist works, but webhook minimization remains broad, raw webhook payment payload is written transiently, and pruning includes refund-capable PAID records | 24 payload/webhook tests pass; important adversarial cases absent | Partial/privacy and pruning defects remain | `PARTIALLY_RESOLVED` |

### Required questions

1. **SEC-010 — Can a COMPLETED application ever lack a valid VERIFIED primary result through the reviewed write paths?** The hardened verify/reject/complete paths prevent it at completion time. Across all current writers, **yes**: `files:purge` can later delete/tombstone the verified primary result without coordinating with the application/result locks, so the model predicate can become false after completion.
2. **SEC-011 — Can assignment and transition actor diverge under concurrency?** **No** in the current reviewed paths. The application lock serializes both values in one transaction, and competing admins cannot overwrite the winner.
3. **SEC-012 — Was any unapproved duplicate-draft business rule introduced?** **No.** No uniqueness, reuse, or replacement rule was added; the original risk remains explicitly deferred.
4. **SEC-013 — Can concurrent OTP issuance bypass cooldown or leave multiple intended usable challenges?** **No** through the current issue/resend entry points. The stable user lock and in-lock cooldown recheck serialize issuance; older type-matching challenges are invalidated.
5. **SEC-021 — Can provider success still be lost because the local reference was not durable?** **No for that original failure mode:** the reference is committed before provider I/O. However, provider success can still become unusable through the newly identified competing-success/failure race that leaves the same durable payment `FAILED` with provider details.
6. **SEC-021 — Can `payments:reconcile` fabricate or downgrade payment truth?** **No.** It only enriches a still-PENDING payment from the provider lookup and never marks PAID, downgrades a status, or reopens an application. It also cannot recover the new FAILED state above.
7. **SEC-024 — Can a rate-limited chat send create message/audit/notification side effects?** **No.** The limiter rejection occurs before the transaction and side-effect calls.
8. **SEC-024 — Can ordinary chat behavior still operate normally?** **Yes.** Normal send, typing, unread, presence, quick reply, notifier, and email-coalescing suites pass.
9. **SEC-003 — Can invalid legacy payment input still silently become BCA?** **No.** Missing, unknown, empty, and case-manipulated values fail validation/enum conversion without a BCA fallback.
10. **SEC-015 — Does any generic serving surface still share the private document root?** **No source or route evidence found.** Private/quarantine/local/public roots are distinct, sensitive disks are non-serving, and delivery remains policy-controlled.
11. **SEC-022 — Did minimization remove fields required by webhook retry/payment reconciliation?** **No known required field was removed.** Current retry, identity validation, payment action display, and reconciliation tests pass; required customer email and canonical payment identifiers remain. The defect is excessive retention, not excessive removal.
12. **SEC-022 — Can pruning remove payload for a retryable/incomplete event?** **Not under its current explicit eligibility queries:** PENDING payments, RECEIVED events, and transient rejected events are excluded. It can prune PAID payment evidence while that payment remains eligible for a future refund transition.
13. **SEC-022 — Is any retention period automatically enforced?** **No.** No scheduler, CI, deployment, or cron source invokes `payments:prune-payloads`; the 30-day minimum is only a command guard.
14. **Cross-cutting — Are any opposing lock orders present?** **Yes.** Client document upload is `Application -> Document write`, while admin document review is `Document -> Application`, forming a concrete deadlock pair. Purge also operates without the corresponding workflow locks.
15. **Cross-cutting — Does any B/C DB transaction perform external provider/mail I/O under lock?** **No newly hardened B/C path does.** Provider I/O is outside the payment transactions; queued mail is dispatched after commit or sent after the lock transaction. File scanning also precedes its database transaction.
16. **Cross-cutting — Did either hardening phase change product/domain semantics beyond its approved scope?** **No intentional product-scope or state-machine expansion was found.** The SEC-021 race and SEC-022 pruning classification are security/correctness defects, not approved domain changes.

### Final verdict counts

- `VERIFIED_CORRECT`: **4**
- `VERIFIED_WITH_MINOR_CONCERN`: **1**
- `PARTIALLY_RESOLVED`: **2**
- `NOT_RESOLVED`: **0**
- `REGRESSION_INTRODUCED`: **1**
- `NEEDS_RUNTIME_VERIFICATION`: **0**
- `DEFERRED_CORRECTLY`: **1**

Counts cover the nine requested root findings, not individual source/test occurrences.

### Prioritized corrective register

| Priority | Concern | Required next action (separate implementation phase) |
|---|---|---|
| P1 | SEC-021 asymmetric concurrent provider outcomes can leave a successful checkout on a FAILED, non-reconcilable payment | Define authoritative phase-2/phase-3 conflict handling, preserve provider truth, and add a deterministic concurrency test |
| P2 | SEC-022 raw webhook payment-payload write and broad ledger/action/name retention | Minimize before every persistence boundary using explicit allowlists; add adversarial unknown-field coverage |
| P2 | SEC-022 pruning includes PAID despite valid refund transitions and lacks locked eligibility recheck | Restrict approved terminal states and lock/recheck each row before destructive tombstoning |
| P2 | SEC-010 scheduled file purge can invalidate the completed-result predicate | Define retention semantics for completed results and coordinate purge with workflow locks/state |
| P2 | Opposing document upload/review lock order | Standardize parent-first ordering and add a deterministic interleaving test |
| P2 | Concurrency suites have no explicit barriers | Add deterministic orchestration around the security-critical interleavings above |
| P3 | Presence heartbeat route repeats session/auth middleware | Remove duplicate registration after confirming effective middleware order remains unchanged |
| P3 | Duplicate PHP array keys and stale hardening test-count claims | Remove ambiguous duplicate literals and reconcile documentation with executable evidence |

### Final verification

- `php artisan optimize:clear`: PASS.
- `php artisan view:cache`: PASS.
- `npm run build`: PASS — Vite 6.4.3, 57 modules transformed.
- `php artisan test --no-coverage`: PASS — **359 tests, 2,291 assertions** in 137.56 seconds.
- `vendor/bin/pint --test`: PASS.
- `git diff --check`: PASS.
- `composer audit --locked`: PASS — no advisories.
- `npm audit --omit=dev`: PASS — 0 vulnerabilities.
- Final `git status --short`: pre-existing Hardening B/C/D and related changes remain present and untouched; this audit artifact is the only intentional file added by this task.

### Overall conclusion

Hardening B contains verified improvements for result concurrency, review assignment, OTP issuance, and durable payment identity, and correctly leaves duplicate-draft policy undecided. It nevertheless requires a targeted security correction because SEC-021 introduces a high-impact payment-state race; SEC-010 also remains incomplete across the scheduled purge path.

Hardening C correctly enforces legacy payment validation and storage separation, and its abuse limits are effective with one minor middleware concern. SEC-022 is only partial: strict minimization is not met at every persistence boundary and pruning eligibility conflicts with the refund-capable payment lifecycle.

Corrective implementation is **required**, but it must be a separate, explicitly approved task. No fix was made during this audit.

Final classification:

**HARDENING B/C INDEPENDENT AUDIT COMPLETE — SECURITY CORRECTIONS REQUIRED**
