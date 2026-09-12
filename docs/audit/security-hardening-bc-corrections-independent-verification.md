# Hardening B/C Corrections Independent Verification

Date: 2026-09-11

Repository/worktree: `C:\Users\nopal\Documents\project-bug-squasher\bantu-daftarin`

Mode: source-grounded, correction-diff-focused, audit only

Correction claims reviewed: `docs/audit/security-hardening-bc-corrections.md`

Prior independent audit: `docs/audit/security-hardening-bc-independent-audit.md`

Production source modified: NO

Tests modified: NO

Migration: NO

Dependencies: NO

Audit artifact: `docs/audit/security-hardening-bc-corrections-independent-verification.md`

## Progress

| Checkpoint | Status | Scope | Evidence |
|---|---|---|---|
| CP0 Baseline / correction diff map | COMPLETE | Required documents, current worktree, executable baseline, correction targets | 368 tests / 2,360 assertions; build, Pint, diff, Composer audit, npm audit passed |
| CP1 SEC-021 payment correction | COMPLETE | Asymmetric outcome, cancellation, reconcile, deterministic test | Core race verified; definitive-failure classification concern; 53 tests / 340 assertions passed |
| CP2 SEC-022 minimization correction | COMPLETE | First persistence, allowlists, binding test, retained fields | 27 tests / 155 assertions passed |
| CP3 SEC-022 pruning correction | COMPLETE | State graph, locked recheck, stale candidate, retention guard | Core pruning policy verified; stale-candidate test-quality concern; 8 tests / 35 assertions passed |
| CP4 SEC-010 purge correction | COMPLETE | Claim, locks, crash windows, private stream acquisition | Core coordination verified; throwing delete does not immediately release claim; 42 tests / 124 assertions passed |
| CP5 Document lock-order correction | COMPLETE | All writers, review/upload, deterministic overlap | Corrected pair verified; unlocked client-delete writer remains; 25 tests / 123 assertions passed |
| CP6 Presence cleanup | COMPLETE | Effective middleware stack and test cache isolation | Exact effective stack verified; 9 tests / 38 assertions passed |
| CP7 Cross-cutting recovery / lock analysis | COMPLETE | Independent lock map, external I/O, A/B/C/D regression | No concrete lock cycle or A/D regression; 51 tests / 475 assertions passed |
| CP8 Regression / final verdict | COMPLETE | Targeted/full suites and final matrix | 368 tests / 2,358 assertions; all required commands passed; correction required for remaining P2 defects |

## Methodology

The correction report is treated as a claim, not evidence. Each concern is traced from current route or command entry points through validation and authorization, transaction boundaries, row-lock order, external-I/O placement, persistence shapes, recovery paths, and existing PostgreSQL tests. No production code, test, migration, dependency, configuration, or previous audit artifact is changed. Safe command execution uses the repository's existing testing configuration and fake providers only.

Verdict vocabulary: `VERIFIED_CORRECT`, `VERIFIED_WITH_MINOR_CONCERN`, `PARTIALLY_CORRECT`, `NOT_CORRECT`, `NEW_REGRESSION_FOUND`, `DEFERRED_SEMANTIC_DECISION`.

## CP0 — Baseline / correction diff map

Status: COMPLETE

### Required source documents

Read completely before source verification: `AGENTS.md`, `DESIGN.md`, `ANTISLOP.md`, `docs/audit/security-audit.md`, Hardening A/B/C/D records, the prior independent B/C audit, and the B/C correction report.

### Existing worktree

The initial `git status --short` showed the uncommitted Hardening B/C/D implementation, tests, documentation, `phpunit.xml` presence-cache isolation, and an unrelated untracked `backupcode_bantudaftarin/` directory. These pre-existing paths are preserved. This audit creates only this document.

### Executable baseline

| Command | Result |
|---|---|
| `git status --short` | PASS; pre-existing dirty worktree recorded and preserved |
| `php artisan test --no-coverage` | PASS: **368 tests, 2,360 assertions**, 0 failures, 133.72 seconds |
| `npm run build` | PASS: Vite 6.4.3, 57 modules transformed |
| `vendor/bin/pint --test` | PASS |
| `git diff --check` | PASS |
| `composer audit --locked` | PASS: no security advisories |
| `npm audit --omit=dev` | PASS: 0 vulnerabilities |

The executable assertion count is two above the correction report's 2,358 and is authoritative for this verification baseline. The test count and failure count match the report.

### Correction source map

| Concern | Primary current implementation | Primary tests/evidence |
|---|---|---|
| SEC-021 payment race | `app/Services/ApplicationWorkflowService.php`, payment gateway/router/provider, webhook controller, reconcile command, cancellation service | `tests/Feature/Payments/PaymentDualWriteHardeningTest.php`, `tests/Support/CoordinatedPaymentGateway.php`, payment/webhook suites |
| SEC-022 first-write minimization/allowlists | `app/Services/PaymentPayloadMinimizer.php`, `app/Http/Controllers/Webhooks/XenditWebhookController.php`, `app/Services/ApplicationWorkflowService.php` | `tests/Feature/Payments/PayloadMinimizationTest.php`, webhook tests |
| SEC-022 pruning | `app/Services/PaymentPayloadPruner.php`, `app/Console/Commands/PrunePaymentPayloads.php` | payload minimization and payment lifecycle tests |
| SEC-010 purge coordination | `app/Services/ExpiredFilePurger.php`, `app/Services/PrivateFileReader.php`, purge command, policies, application/result workflow | `tests/Feature/Files/FilePurgeCoordinationTest.php`, result/document/private-stream tests |
| Document lock order | `app/Services/DocumentWorkflowService.php`, document controller/policy | `tests/Feature/Documents/DocumentLockOrderConcurrencyTest.php`, document workflow tests |
| Presence cleanup | `routes/web.php`, `AppServiceProvider`, presence controller/support, `phpunit.xml` | `tests/Feature/Chat/ChatPresenceTest.php`, chat/admin tests |
| Cross-security regression | Hardening A/B/C/D production paths and controls | existing security, webhook, Livewire, OTP, chat, file, payment, production-check suites |

## CP1 — SEC-021 payment correction

Status: COMPLETE

### Current source behavior

`ApplicationWorkflowService::createPayment` commits the local `Payment` intent and server-generated `BD-{UUID}` reference before invoking `PaymentGateway::createInvoice`. The application lock serializes intent discovery/creation and reuse; the browser supplies only the allowlisted payment method. The provider call occurs after the Phase 1 transaction. Webhook lookup continues to use `payments.reference_id`, and the active Xendit request uses that value as both `reference_id` and `idempotency-key`.

The provider-exception branch re-locks and re-reads the payment. It no longer writes `FAILED`; it records only a recoverable audit event when the row is still a blank `PENDING` intent. A competing successful response is persisted by `persistPaymentCheckout`, which locks the same payment, writes only while status remains `PENDING`, and cannot overwrite `PAID`, `FAILED`, `EXPIRED`, `CANCELLED`, or refund states. The previously demonstrated provider-success/local-`FAILED` race is therefore closed.

### Exception classification finding

The active gateway does **not** distinguish all definitive failures from ambiguous outcomes:

- `XenditPaymentProvider.php:25-28` throws `PaymentGatewayException` before any HTTP request when the secret/channel configuration is absent. This is a definitive no-provider-call failure.
- `XenditPaymentProvider.php:74-76` maps every unsuccessful HTTP result, including definitive 4xx rejection/authentication/validation responses, to the same exception type.
- Transport exceptions at lines 59-71 are ambiguous because a timeout/connection failure may occur after the provider accepted the idempotent request.
- A successful HTTP response with malformed JSON or no payment identity at lines 79-86 is also reasonably treated as ambiguous because provider acceptance cannot be disproved from the local response.

`ApplicationWorkflowService.php:270-278` treats all of these `PaymentGatewayException` instances identically. Consequently, a definitely unsubmitted/rejected request can remain locally `PENDING` until a later create attempt notices local expiry; without a later attempt the stored status is not advanced. Reconcile repeats the same provider operation and has no definitive-error transition. This does not recreate the provider-backed irrecoverable-`FAILED` integrity bug, but it is a concrete availability/status-accuracy regression from collapsing all exception classes into one ambiguous category.

Classification: **P2 `IMPLEMENTATION_DEFECT`**, final concern verdict `VERIFIED_WITH_MINOR_CONCERN` for the SEC-021 race correction. The corrective objective is substantially achieved, but the report's statement that every provider exception is ambiguous is not correct.

### Success persistence and identity

`persistPaymentCheckout` minimizes the payload before opening its persistence transaction, locks the payment, and refuses to mutate a non-`PENDING` row. For a still-`PENDING` row it rejects an existing external identity that differs from the provider response and reuses a fully populated matching checkout. It therefore cannot downgrade provider-authoritative state or replace an existing provider identity.

Minor edge: the non-`PENDING` early return occurs before the external-ID mismatch check. A concurrent webhook-set `PAID` row with a different external identity is preserved safely but the mismatch is silently ignored rather than explicitly refused. No corrupting write follows, so this is recorded as **P3 `IMPLEMENTATION_DEFECT`**, not a restored race.

### Cancellation and reconciliation

Cancellation takes `Application -> Payment(s)` locks and leaves an unconfirmed payment's provider status unchanged. If cancellation commits while provider I/O is in flight, success persistence may enrich the still-`PENDING` payment but does not touch or reopen the application. A later paid webhook records `PAID` provider truth while the application remains `CANCELLED` and writes the existing late-payment audit; no refund assumption is introduced.

`payments:reconcile` accepts an exact local reference and prints only the reference and status. The workflow performs a locked local payment preflight before provider I/O, exits unless the current row is incomplete `PENDING`, calls the provider outside the lock, and delegates to the same locked final persistence. It cannot fabricate `PAID`, downgrade a payment, change application state, or reopen cancellation. It may still make an idempotent provider call for a payment whose application was cancelled because preflight does not inspect application state, but final persistence remains non-reopening.

### Concurrency-test quality

`CoordinatedPaymentGateway` writes per-outcome `ready` markers and requires both markers before either outcome proceeds. The success side additionally waits for `failure.finished`, which the failed caller writes only after `createPayment` has thrown back through its locked local exception handling. The 10 ms sleeps are only bounded marker polling, not the ordering mechanism. The test proves a shared stable reference reached both calls, final `PENDING` plus provider identity/checkout, exactly one payment row, and webhook transition to `PAID`/`PAYMENT_CONFIRMED`. Separate tests cover operator reconciliation and interrupted durable intent.

Targeted payment/cancellation/webhook result: **53 tests, 340 assertions**, all passing.

### CP1 verdict

`VERIFIED_WITH_MINOR_CONCERN`

## CP2 — SEC-022 minimization correction

Status: COMPLETE

### Persistence-path inventory

The active application has three production writes to `payments.provider_payload`: checkout response persistence in `ApplicationWorkflowService`, webhook-derived persistence in `XenditWebhookController`, and the explicit pruning tombstone in `PaymentPayloadPruner`. The only production creation of `webhook_events.payload` is the webhook ledger `firstOrCreate`; the pruner later replaces an eligible payload with a tombstone. In every provider-derived path, the data is transformed before the Eloquent write:

- Checkout: provider response -> `PaymentPayloadMinimizer::checkout` -> locked payment write.
- Webhook ledger: request JSON -> `PaymentPayloadMinimizer::webhookEvent` -> `WebhookEvent::firstOrCreate`.
- Webhook payment: minimized stored event -> `PaymentPayloadMinimizer::paymentFromWebhook` -> locked payment write.
- Prune: locally constructed identifier-only tombstone -> locked row write.

No active `raw -> database -> sanitized -> database` sequence remains. The legacy `XenditPaymentProvider` return literal still contains a duplicate `payload` key, first raw and then its own minimized value, but PHP retains only the latter and `ApplicationWorkflowService` applies the centralized minimizer again before any database call. This is redundant source, not a persistence leak.

### Positive allowlists

`PaymentPayloadMinimizer` uses `Arr::only` at the webhook top level, the nested `data` object, nested `customer`, action entries, and channel properties. Customer retention is limited to email; channel properties are limited to expiry; actions are limited to the current action/type/descriptor/url/value/QR/VA keys. `paymentFromWebhook` constructs a new fixed shape rather than copying the incoming object. Unknown top-level keys, provider metadata, nested data, customer attributes, action metadata, and channel properties are discarded by default.

### SQL-binding regression

`test_unknown_webhook_fields_never_cross_a_database_persistence_boundary` registers `DB::listen` before submitting the webhook and captures every string query binding. It injects eight distinct `never-persist-*` markers across all relevant nesting levels, then checks both final JSON and the complete captured binding stream. Any transient INSERT/UPDATE containing a raw marker would fail even if a later write sanitized the row. This is genuine PostgreSQL persistence-boundary coverage, not only final-model inspection.

### Retained-field sufficiency

SEC-020 retries operate on the minimized ledger payload and retain both currently supported shapes:

- Legacy: top-level ID, external reference, status, amount, currency, payer email, and channel.
- V3: event, `data` payment/payment-request IDs, reference, status, amount/request amount, currency, channel, customer email, actions, channel expiry, and timestamps.

These fields satisfy current event classification, reference/payment lookup, external-ID matching, amount/currency verification, payer/channel validation, status transition, and webhook retry. Payment payloads retain descriptors/values needed by `Payment::virtualAccountNumber()` and `qrString()`, gateway status, supported instructions, and expiry context. Reconciliation itself uses durable payment columns and provider lookup rather than depending on the stored payload.

Unknown future Xendit fields are intentionally discarded. The currently supported legacy and V3 normalization branches do not read a field excluded by the allowlists; no concrete alternate supported shape incompatibility was found.

Targeted minimization/webhook result: **27 tests, 155 assertions**, all passing.

### CP2 verdict

- SEC-022 first-persistence boundary: `VERIFIED_CORRECT`.
- SEC-022 explicit allowlists and current-reader sufficiency: `VERIFIED_CORRECT`.

## CP3 — SEC-022 pruning correction

Status: COMPLETE

### Eligibility and state graph

The command candidate query and the authoritative locked service recheck use the same payment statuses: `FAILED`, `EXPIRED`, `CANCELLED`, and `REFUNDED`. `PAID`, `REFUND_REQUESTED`, and `REFUNDING` are excluded, so payload data needed by an active refund path is retained. This matches `PaymentStatus::canTransitionTo`: refund processing can start only from `PAID`; all four prunable states are terminal in the current graph. The tombstone keeps only local external/reference identifiers and pruning metadata, and deliberately removes actions, QR values, virtual-account numbers, and the original payload.

Webhook pruning is limited to old `PROCESSED` events or `REJECTED` events marked exactly `permanent_validation_failed`. `RECEIVED` and transiently rejected events retain their minimized payload for retry and recovery. Both webhook and payment pruning re-read the row with `lockForUpdate()` and re-evaluate status, age, payload presence, and prior-prune state inside the write transaction. A stale candidate cannot be pruned merely because its ID was selected by the earlier command query.

### Operator and retention guard

The command has no approved default retention period: `--days` is mandatory, values below 30 are rejected, omission of `--force` remains a simulation, and destructive execution requires both an explicit retention age and `--force`. No production scheduler registration for `payments:prune-payloads` was found. Retention therefore remains an explicit business/operations decision rather than an invented automatic policy.

### Stale-candidate test quality

`test_locked_pruner_rejects_a_stale_candidate_that_entered_refund_processing` does prove that the locked service rejects a current `REFUND_REQUESTED` row and leaves its payload intact. Its narrative is weaker than its name: the test captures the ID while the payment is `PAID`, but `PAID` is not eligible for the corrected command query, so it does not literally reproduce “selected by the current candidate query, then entered refund processing.” Under the current state graph, no legitimate transition exists from one of the four eligible terminal states into refund processing. The production locked recheck is still correct and independently visible in source; the test is useful recheck coverage but not a faithful current-query race barrier.

Classification: **P3 `TEST_GAP`**. This does not invalidate the pruning implementation.

Targeted pruning result: **8 tests, 35 assertions**, all passing.

### CP3 verdict

`VERIFIED_WITH_MINOR_CONCERN`

## CP4 — SEC-010 purge correction

Status: COMPLETE

### Claim and lock coordination

`ExpiredFilePurger` resolves identity without a row lock, then takes the common parent-first locks before trusting or mutating the target: `Application -> ApplicationRequirement -> Document` or `Application -> ResultDocument`. It verifies that the locked row still belongs to the initially resolved parents. Eligibility requires an expired retention date, no `deleted_at`, and either no claim or a claim at least 15 minutes old. The claim is committed before filesystem deletion, so policy checks, result verification, result completion, and new private-file acquisition reject the row while deletion is pending.

Result verification locks `Application -> ResultDocument` and explicitly rejects claimed/deleted rows. Completion locks the application and queries only unclaimed, undeleted, scanned, verified primary results. The model-level `hasVerifiedPrimaryResult` predicate uses the same exclusion. Document/result policies also deny a claimed row. Thus a claim that wins first cannot later be accepted as a usable result or newly downloaded.

### Private stream acquisition

`PrivateFileReader` acquires the same parent-first locks, installs the locked application relation, performs the relevant policy authorization, and opens the private stream before the transaction releases its locks. Controllers stream that already-open resource and close it in `finally`. A purge cannot insert a claim between authorization and stream acquisition. An access already admitted and opened may continue while a later purge runs; that is a coherent in-flight access boundary rather than a new unauthorized acquisition.

Minor lifecycle caveat: access-audit persistence occurs after `openDocument`/`openResult` returns. If audit/response construction throws before the streamed callback is invoked, the resource is not explicitly closed on that exception path. PHP request cleanup will normally release it, but source does not provide a controller-level `try/finally` around response construction. Classification: **P3 `IMPLEMENTATION_DEFECT`** (resource lifetime/availability, not authorization bypass).

### Filesystem and database crash windows

The configured `private` disk is the local Flysystem adapter with exceptions enabled. The relevant windows behave as follows:

1. Crash before claim commit: the transaction rolls back; the file and authoritative eligibility remain unchanged, and a later command may select it normally.
2. Crash after claim commit, before delete: the row remains inaccessible and becomes retry-eligible after 15 minutes.
3. Filesystem delete throws/fails: a returned failure releases only the matching claim; an exception or process crash leaves the claim for stale recovery.
4. Crash after physical delete, before DB finalization: a later retry reclaims the row. The active local adapter treats deletion of an already absent path as success, allowing the retry to mark `deleted_at` and audit the purge.
5. DB finalization failure after deletion: the owned claim remains. The same stale-retry path can finalize later without requiring the file to exist.
6. Finalization commit: `deleted_at`, reason, and audit commit together while ownership is still validated. Further access/pruning attempts reject the tombstoned row.
7. Finalization/release by an obsolete worker: `isOwnedClaim` compares the exact claim timestamp, so an older worker cannot finalize or clear a newer claim.

There is a concrete mismatch in the automatic storage-failure branch. `purge()` releases the claim only when `Storage::delete()` returns `false`, but the configured `private` disk has `throw=true`; Laravel rethrows `UnableToDeleteFile` instead of returning `false`. `purge()` has no `catch/finally`, so the normal configured delete-exception path does **not** call `release()` immediately. It does not write a false deletion tombstone and stale recovery can retry after 15 minutes, but the file is unnecessarily inaccessible during that interval and the uncaught exception can stop the command's remaining loop. The existing release test calls `release()` directly and does not inject the configured throwing failure through `purge()`.

Classification: **P2 `IMPLEMENTATION_DEFECT`** with a paired **P2 `TEST_GAP`**. The claim model prevents unsafe exposure, but the correction's stated immediate release/recovery behavior is incomplete for the actual disk configuration.

The 15-minute timestamp is a lease, not a unique token. An operation that genuinely remains active longer than 15 minutes can be reclaimed. With the configured local disk, single-file deletion is expected to be short and duplicate deletion is idempotent, and timestamp ownership prevents the older worker from mutating the new claim. Nevertheless, there is no heartbeat or formal upper bound proving an active operation cannot outlive the lease. Classification: **P3 `RECOVERY_GAP` / operational assumption**.

### Scheduler and test quality

`files:purge` remains scheduled daily. The command performs a non-locking candidate scan, but every destructive candidate is revalidated by the locked purger, which is the required safe pattern.

The coordination suite proves claim-first denial for completion, verification/rejection, and new authorized access, plus explicit release. Existing workflow coverage proves physical deletion and audit. It does not inject the configured throwing storage failure through `purge()`, inject crashes in the claim/delete/finalize windows, exercise stale-claim recovery after an already completed physical delete, or run a true concurrent purge-versus-download barrier. The successful crash-recovery properties are supported by current source and local-adapter semantics but are not all executable regression claims.

Targeted purge/private access/result/document result: **42 tests, 124 assertions**, all passing.

### CP4 verdict

`PARTIALLY_CORRECT`

## CP5 — Document lock-order correction

Status: COMPLETE

### Corrected upload and review paths

`DocumentWorkflowService::upload` locks `Application -> ApplicationRequirement -> all existing Document versions`, rechecks the locked parent/requirement relationship and current upload eligibility, derives the next version under those locks, deactivates every active existing version, creates one new active `PENDING` version, and resets the requirement to `PENDING`. The initial malware scan/private storage operation occurs before the database transaction; on any transaction failure the newly stored file is deleted.

`DocumentWorkflowService::review` reads only immutable identity fields before its transaction. It then locks `Application -> ApplicationRequirement -> target Document`, verifies all parent associations again, and makes every mutable eligibility decision from the locked instances: application stage, active flag, scan state, review state, deletion claim, and deletion tombstone. It writes the document decision, requirement status, review ledger, and audit in the same transaction. It no longer trusts a stale incoming `Document` for authoritative decisions and does not take a `Document -> Application` lock order.

### Writer inventory and remaining unlocked path

Production writers found for document active/review/requirement state are the upload and review service paths, the retention purger's deletion fields, and `Client\DocumentController::destroy` for client removal of an active pre-payment/revision document. Purge and private access use the same parent-first order. No production path was found that first locks a `Document` and then waits on its `Application`.

The client `destroy` path, however, is not incorporated into that locking discipline. It loads and authorizes a document, checks `active` and `canClientUpload` on unlocked relationships, deletes the physical file, and only then saves `active=false`/deletion fields without a database transaction or locked recheck. If the application/document becomes ineligible while filesystem deletion is in flight, this path can act on the stale earlier decision—for example, a stage/review transition can complete before the stale client deletion saves. This is not the former opposing-lock deadlock, but it is a concrete remaining time-of-check/time-of-use integrity gap in a writer explicitly included by the “all writers” audit.

Classification: **P2 `IMPLEMENTATION_DEFECT`**. It appears pre-existing rather than introduced by this correction, but the correction report's broader implication that all document mutation paths share one coordinated hierarchy is overstated.

### Deterministic overlap test

`DocumentLockOrderConcurrencyTest` uses two separate process workers and file markers. The upload worker starts an outer transaction, acquires the application lock, emits `application.locked`, and waits for the review worker's `review.started` marker. The review worker cannot merely start after upload completes: it announces its attempt only after observing the held-lock marker and then blocks at the common application row. The polling sleeps only wait for explicit markers.

After serialization, upload succeeds, stale review is rejected from authoritative locked state, the old document is inactive and remains unreviewed, exactly one version-2 active document exists, and the requirement is `PENDING`. These assertions cover no deadlock, valid requirement/document state, one active version, stale-review rejection, and no lost review update. The test wraps production upload in an outer transaction to establish the barrier before production's own file-storage step; it is therefore strong lock-order coverage, though not a model of the exact production I/O timing.

Targeted document/application result: **25 tests, 123 assertions**, all passing.

### CP5 verdict

`PARTIALLY_CORRECT`

## CP6 — Presence cleanup

Status: COMPLETE

### Effective route stack

Current source registers the heartbeat once at `POST /presence/heartbeat`, explicitly removes `StartSession`, and adds one `ReadOnlySession`, one `auth`, and one `throttle:presence-heartbeat`. The resolved `route:list -vv` middleware confirms exactly one instance of each relevant middleware in this order within the web stack:

`EncryptCookies -> AddQueuedCookiesToResponse -> ReadOnlySession -> ShareErrorsFromSession -> ValidateCsrfToken -> Authenticate -> ThrottleRequests:presence-heartbeat -> SubstituteBindings`.

`StartSession` is absent. The surrounding cookie, CSRF, error-sharing, and binding middleware remain normal web-group behavior. The named limiter remains 60 requests per minute keyed by authenticated user ID (falling back to IP), and the controller still enforces the existing active-admin/client behavior.

### Test isolation and coverage

`phpunit.xml` explicitly fixes `CHAT_PRESENCE_CACHE_STORE=array`, and the suite verifies the resolved `ChatPresence` store is ephemeral. This prevents persistent file-cache last-seen values from leaking between test runs.

The middleware regression test counts `ReadOnlySession` exactly once and asserts `StartSession` absent. It functionally exercises authentication, active-admin rejection, and heartbeat behavior, but it does not explicitly count the resolved Authenticate and Throttle middleware classes. Current route source and `route:list -vv` independently establish those counts. This is a narrow **P3 `TEST_GAP`**, not an implementation defect.

Targeted presence result: **9 tests, 38 assertions**, all passing.

### CP6 verdict

`VERIFIED_CORRECT`

## CP7 — Cross-cutting recovery / lock analysis

Status: COMPLETE

### Independently reconstructed lock-order map

| Current path | Lock order from current source | Notes |
|---|---|---|
| Result verification | `Application -> ResultDocument` | Locked result eligibility is rechecked |
| Application completion | `Application -> eligible ResultDocument rows` | Claimed/deleted results excluded |
| Result upload | No row lock during scan/storage/create; transition later locks `Application` | No child lock is held while waiting for application |
| Document upload | `Application -> ApplicationRequirement -> Document versions` | Eligibility/version state rechecked under locks |
| Document review | `Application -> ApplicationRequirement -> Document` | Immutable identity only before transaction |
| Document/result purge | `Application -> [ApplicationRequirement] -> Document/ResultDocument` | Claim/finalize/release share order; delete is between committed phases |
| Document/result private access | `Application -> [ApplicationRequirement] -> Document/ResultDocument` | Stream is opened before lock release |
| Client document destroy | No row locks | Remaining stale-decision gap; no opposing lock cycle |
| Payment creation intent | `Application -> active Payment lookup/write` | Provider call follows commit |
| Payment checkout persistence | `Payment` | Writes only still-`PENDING` row |
| Cancellation | `Application -> Payment rows` | Payment states are re-read under locks |
| Webhook settlement | `WebhookEvent -> Application -> Payment` | No production path holding payment then requesting webhook-event lock |
| Payment reconciliation | `Payment` preflight; later `Payment` finalization | Provider I/O occurs between transactions |
| Payload pruning | `Payment` or `WebhookEvent` | Locked authoritative eligibility recheck |
| OTP issue/resend | `User -> challenge query/write` | Notification registered after commit |
| OTP verify | `AuthChallenge` | No subsequent user row lock |
| Chat send | `ChatThread -> ChatThreadUserState` plus message write | Delayed email scheduling uses same thread/state order |
| Chat email execution | `ChatThread -> ChatThreadUserState` | Notification delivery after transaction |
| Chat edit/delete | `ChatThread -> ChatMessage` | No reverse message-to-thread path found |
| Chat archive/unarchive | `ChatThread -> ChatThreadUserState` | Same parent-first chat order |

No concrete opposing row-lock cycle was found in the corrected result, document upload/review, purge/access, payment, OTP, or chat paths. The unlocked client document deletion finding is a stale-authority race, not a deadlock cycle.

### External-I/O boundaries

- Xendit checkout/reconciliation HTTP runs after durable intent/preflight transactions and before a separate locked finalization; it is not executed while holding application/payment locks.
- Malware scan plus quarantine/private copy for document and result uploads occurs before workflow row locks. Failure cleanup runs after a failed transaction/operation.
- Purge filesystem deletion occurs after claim commit and before a separate finalization transaction.
- Private local `readStream` is intentionally opened while the parent/file locks are held, but byte delivery occurs after the transaction. This is a bounded coordination exception needed to close the authorization-to-open race.
- OTP notification is registered through `DB::afterCommit`; chat unread mail uses delayed `afterCommit`; result/application notifications inspected remain queued or are invoked outside their lock-producing payload transaction. No synchronous mail transport was found inside a reviewed row-lock transaction.

No newly corrected path performs Xendit HTTP, malware scanning, or bulk response streaming under a row lock.

### Hardening A regression

Current source preserves the four requested controls:

- SEC-005: password reset always returns `passwords.sent_if_registered`; known and unknown accounts receive the same public acknowledgement.
- SEC-006 and SEC-023: `EnsureAdmin`, `EnsureClient`, and email verification are registered as Livewire persistent middleware; snapshot-reuse tests reject inactive admin users/profiles, inactive clients, and unverified clients.
- SEC-020: only processed/permanently rejected events are terminal; existing `RECEIVED` and transient/legacy rejected events are resumed from the minimized stored ledger payload; concurrent delivery remains idempotent.

### Hardening D regression

The production security checker, fail-closed ClamAV/testing-driver binding, bounded malware health check, security-header middleware, strict `script-src 'self'` CSP, mail transport/nested failover rejection, secret-redacted command output, and operational command registrations remain present. This repository verification does not convert Hardening D's staging/production `NOT_TESTED`/`NOT_VERIFIABLE` controls into deployment acceptance.

Targeted Hardening A/webhook/chat/D regression result: **51 tests, 475 assertions**, all passing.

The application and payment enum graphs have no current diff. No migration or dependency change is present in the correction diff.

### CP7 verdict

Core cross-hardening regression: `VERIFIED_CORRECT`. Overall correction set remains subject to the P2 findings in CP1 and CP5 and the test/recovery concerns already recorded.

## CP8 — Regression / final verdict

Status: COMPLETE

### Executable verification

| Command | Result |
|---|---|
| `php artisan optimize:clear` | PASS |
| `php artisan view:cache` | PASS |
| `npm run build` | PASS: Vite 6.4.3, 57 modules transformed |
| `php artisan test --no-coverage` | PASS: **368 tests, 2,358 assertions**, 0 failures, 100.63 seconds |
| `vendor/bin/pint --test` | PASS |
| `git diff --check` | PASS |
| `composer audit --locked` | PASS: no security advisories |
| `npm audit --omit=dev` | PASS: 0 vulnerabilities |

The final suite matches the correction report's 368/2,358 count. The initial baseline in this independent run produced 2,360 assertions with the same 368 tests and zero failures; concurrency/branch execution can change PHPUnit's assertion total without changing the test inventory. Both actual results are recorded rather than silently normalized.

### Targeted evidence summary

| Scope | Result |
|---|---|
| Payment race, cancellation, webhook | 53 tests / 340 assertions passed |
| Payload minimization/webhook persistence | 27 tests / 155 assertions passed |
| Pruning-specific filter | 8 tests / 35 assertions passed |
| Purge/private access/result/document | 42 tests / 124 assertions passed |
| Document lock order/workflow/application | 25 tests / 123 assertions passed |
| Presence | 9 tests / 38 assertions passed |
| Hardening A/webhook/chat/D regression | 51 tests / 475 assertions passed |

These targeted groups overlap; their counts must not be summed as unique suite totals.

### Verification matrix

| Correction | Source Verdict | Test Verdict | Regression | Final Verdict |
|---|---|---|---|---|
| SEC-021 payment race | Provider success cannot be overwritten; durable intent and safe final write verified | Deterministic asymmetric barrier and reconciliation tests pass | Definitive pre-call/4xx failures share ambiguous exception and may remain stored `PENDING`; non-PENDING identity mismatch is silent | `VERIFIED_WITH_MINOR_CONCERN` for the original race; P2 follow-up required |
| SEC-022 raw persistence | Minimization precedes every provider-derived database write | SQL-binding sentinel coverage passes | Duplicate PHP `payload` literal is redundant but effective value is minimized | `VERIFIED_CORRECT` |
| SEC-022 allowlist | Explicit positive allowlists at all relevant nesting levels | Current legacy/V3/readers covered | Unknown future provider shape is intentionally unsupported until reviewed | `VERIFIED_CORRECT` |
| SEC-022 prune/refund | `PAID` and refund-active states excluded; locked recheck matches query | Pruning tests pass | Stale-candidate test starts from a status not selectable by current query | `VERIFIED_WITH_MINOR_CONCERN` |
| SEC-010 purge coordination | Claim-first coordination and local crash recovery mostly correct | Claim denial, release primitive, physical purge, access suites pass | Throwing delete bypasses immediate release; crash/stale recovery lacks direct tests; fixed lease and response-setup resource concerns | `PARTIALLY_CORRECT` |
| Document lock order | Upload/review/purge/access parent-first order verified | Deterministic upload-review overlap passes | Client destroy remains an unlocked stale-decision writer | `PARTIALLY_CORRECT` |
| Presence middleware | One read-only session, auth, throttle; no StartSession | Presence and route-stack tests pass | Test explicitly counts only ReadOnlySession, not auth/throttle classes | `VERIFIED_CORRECT` |

### Remaining findings

| Priority | Type | Source | Finding | Required disposition |
|---|---|---|---|---|
| P2 | `IMPLEMENTATION_DEFECT` | `app/Services/XenditPaymentProvider.php:26,74`; `app/Services/ApplicationWorkflowService.php:270-278` | Definitive no-call and HTTP rejection failures are indistinguishable from ambiguous outcomes, so durable status can remain `PENDING` without another client action | Separate correction: explicit provider outcome taxonomy and state/retry policy without weakening idempotency |
| P2 | `IMPLEMENTATION_DEFECT` | `app/Services/ExpiredFilePurger.php:27-32`; `config/filesystems.php:33-38` | Configured `throw=true` deletion errors throw past the `false`-return release branch, leaving the claim until stale recovery and aborting the command loop | Catch configured filesystem failure, release only the owned claim, preserve failure/audit truth; add failure injection |
| P2 | `IMPLEMENTATION_DEFECT` | `app/Http/Controllers/Client/DocumentController.php:41-55` | Client document removal checks mutable eligibility without locks, deletes physical content, then writes active/deletion state | Move authoritative decision/write under common parent-first coordination and define safe filesystem phases |
| P2 | `TEST_GAP` | `tests/Feature/Files/FilePurgeCoordinationTest.php` | No configured throwing-delete, crash-after-delete/stale-retry, or true purge-vs-stream barrier test | Add deterministic synthetic failure/concurrency coverage in a correction phase |
| P3 | `IMPLEMENTATION_DEFECT` | `app/Services/ApplicationWorkflowService.php:309-317` | A non-`PENDING` early return occurs before external-ID mismatch validation | Decide whether mismatch must audit/throw while preserving terminal provider truth |
| P3 | `TEST_GAP` | `tests/Feature/Payments/PayloadMinimizationTest.php:606-638` | “Stale selected candidate” begins as `PAID`, which current query never selects | Rename/reframe as locked recheck test or construct a truthful supported race |
| P3 | `RECOVERY_GAP` | `app/Services/ExpiredFilePurger.php:16,123-125` | Fixed 15-minute lease has no heartbeat or proven delete-duration bound | Document/enforce operational bound or use stronger lease ownership if required |
| P3 | `IMPLEMENTATION_DEFECT` | `app/Services/PrivateFileReader.php:33,52`; client response construction | Open stream is closed in streaming callback, but response/audit setup exception before callback has no explicit close | Wrap post-open response setup with resource cleanup |
| P3 | `TEST_GAP` | `tests/Feature/Chat/ChatPresenceTest.php:164-171` | Test counts ReadOnlySession only; auth/throttle uniqueness is source/route-list verified but not asserted | Extend narrow route-stack assertion if desired |
| — | `BUSINESS_DECISION` | Result retention policy | Whether a `COMPLETED` application must satisfy `hasVerifiedPrimaryResult()` forever after approved retention deletion remains undefined | `DEFERRED_SEMANTIC_DECISION` / `[BUSINESS CONFIRMATION REQUIRED]` |

### Required explicit answers

1. **Can a concurrent failed provider attempt overwrite provider success?** No. Both paths re-read the locked row; failure does not mutate provider state and success writes only still-`PENDING` intent.
2. **Can a definitive provider rejection be incorrectly left `PENDING` forever?** Yes at the stored-status level if no later create/reconciliation succeeds. A local expiry timestamp exists, but no autonomous transition distinguishes a definite pre-call/4xx rejection from an ambiguous request; the next client create can expire it, while an untouched row can remain stored `PENDING` indefinitely.
3. **Can a provider-backed payment become unrecoverably `FAILED`?** Not through the corrected checkout-exception branch. An authenticated webhook can still legitimately transition provider truth to `FAILED` under the unchanged graph.
4. **Can `payments:reconcile` fabricate or downgrade provider truth?** No. It is restricted to incomplete `PENDING`, performs provider I/O outside locks, and uses the guarded final persistence.
5. **Can cancellation during provider I/O reopen the application/payment flow?** No. Checkout persistence cannot change application state; late webhook payment truth is recorded without reopening a cancelled application.
6. **Does raw provider/webhook payload ever reach PostgreSQL before minimization?** No in every current provider-derived write path inspected.
7. **Are unknown fields persisted by default?** No. Nested positive allowlists discard them.
8. **Does minimized payload still satisfy SEC-020 retry?** Yes for all currently supported legacy and V3 normalization/verification fields.
9. **Can prune affect `PAID`/refund-active payments?** No through the current command or locked pruner eligibility.
10. **Does prune lock/recheck stale candidates?** Yes, both payment and webhook rows are re-read with `lockForUpdate` and re-evaluated.
11. **Can retryable webhook payload be pruned?** No. `RECEIVED` and transient `REJECTED` rows are excluded.
12. **Can `files:purge` expose DB/file divergence after any crash point?** It can temporarily leave an access-denied claimed row after the file is gone, but the configured local adapter permits stale retry/finalization and the claim prevents that missing file from being exposed as usable. The unsafe exposure race is closed; recovery is incomplete on throwing delete failures because immediate release is skipped.
13. **Can an active purge claim be stolen too early?** Not before 15 minutes. After that fixed interval another worker may reclaim it even if the original filesystem operation is still active; no heartbeat/maximum-duration proof makes the stronger absolute claim valid.
14. **Can completion/verification/access race the purge claim?** The reviewed new operations serialize on common parent/file locks. Claim-first denies them; operation/stream-open-first completes before a later claim. An already opened stream may finish after lock release by design.
15. **Does `PrivateFileReader` hold DB locks only through safe stream acquisition, not entire response delivery?** Yes. It authorizes and opens under locks, commits, then streams outside the transaction. Normal callback closure is correct; response/setup exceptions before callback have the P3 resource-cleanup gap noted above.
16. **Does any document path still use an opposing child-first lock order?** No such lock order was found. Client destroy is still unsafe in a different way: it uses no authoritative row locking.
17. **Can upload/review still deadlock under the reviewed paths?** The former opposing cycle is removed, and the deterministic barrier test completes with a valid serialized result. No concrete cycle remains for those paths.
18. **Is heartbeat middleware registered exactly once?** Yes for each relevant middleware: `ReadOnlySession`, auth, and named throttle; `StartSession` is absent.
19. **Did corrections change application/payment state graphs?** No current enum diff or changed transition matrix was found.
20. **Did corrections introduce migration/dependency?** No.
21. **Is the only unresolved question still the post-retention `COMPLETED`-result semantic decision?** No. It remains the only unresolved *business-semantic* decision, but the P2 implementation/test defects above are also unresolved engineering issues.

### Artifact and worktree integrity

This independent verification modified no production source, tests, migration, dependency manifest/lockfile, or prior audit/hardening record. The only intentional artifact is this document. The final `git status --short` continues to show the pre-existing Hardening B/C/D worktree and unrelated `backupcode_bantudaftarin/`; none was reverted or rewritten. Safe tests used synthetic data, the configured fake/test services, and the isolated PostgreSQL test database.

### Final verdict

The high-impact provider-success-overwritten-by-failure race, raw payload persistence, nested allowlists, refund-active pruning exclusion, main purge authorization race, corrected upload/review deadlock, presence duplication, and requested Hardening A/D regressions are substantively fixed. Full and targeted suites pass.

Independent source review nevertheless proves three actionable P2 implementation gaps: definitive provider rejection is not classified separately, configured throwing filesystem deletion bypasses immediate claim release, and client document removal remains an unlocked stale-decision writer. These prevent a fully verified or minor-concern classification. The completed-result-after-retention question remains separately deferred to business/operations and is not used to invent a domain rule.

Final classification:

**B/C CORRECTIONS INDEPENDENT VERIFICATION COMPLETE — CORRECTION REQUIRED**
