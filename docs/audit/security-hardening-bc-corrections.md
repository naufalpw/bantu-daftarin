# Hardening B/C Targeted Security Corrections

Date: 2026-09-09

Scope: SEC-021 payment race; SEC-022 minimization and pruning; SEC-010 purge coordination; document lock order; presence middleware duplication; factual Hardening B/C reconciliation

Authoritative correction source: `docs/audit/security-hardening-bc-independent-audit.md`

Production source modified: YES

Domain/state graphs modified: NO

Migration: NO expected

Dependencies: NO expected

## Progress

| Checkpoint | Status | Scope | Tests |
|---|---|---|---|
| CP0 Baseline | COMPLETE | Baseline and correction targets | 359 tests / 2,292 assertions; build, Pint, diff, and audits passed |
| CP1 SEC-021 payment race | COMPLETE | Asymmetric success/failure outcome and reconcile | 52 tests / 333 assertions passed |
| CP2 SEC-022 minimization | COMPLETE | Minimize before persistence; explicit allowlists | 54 tests / 290 assertions passed |
| CP3 SEC-022 pruning/refund | COMPLETE | Safe states and per-row locked recheck | 43 tests / 229 assertions passed |
| CP4 SEC-010 purge coordination | COMPLETE | File/DB ordering and result locks | 30 tests / 85 assertions passed |
| CP5 Document lock order | COMPLETE | Parent-first upload/review order | 30 tests / 97 assertions passed |
| CP6 Minor cleanup/documentation | COMPLETE | Presence middleware and factual records | 18 tests / 220 assertions passed |
| CP7 Cross-security regression | COMPLETE | A/B/C/D and lock/I/O review | 117 tests / 761 assertions passed |
| CP8 Final verification | COMPLETE | Full required command set and final matrix | 368 tests / 2,358 assertions passed; build, Pint, diff, audits passed |

## CP0 — Baseline

Status: COMPLETE

### Existing worktree

The initial `git status --short` showed the uncommitted Hardening B/C/D implementation and related tests/documents already present. Every pre-existing change is preserved. No reset, checkout, migration, dependency update, real provider call, real email, or destructive prune was performed.

### Baseline verification

- `php artisan test --no-coverage`: PASS — **359 tests, 2,292 assertions** in 101.43 seconds.
- `npm run build`: PASS — Vite 6.4.3, 57 modules transformed.
- `vendor/bin/pint --test`: PASS.
- `git diff --check`: PASS.
- `composer audit --locked`: PASS — no advisories.
- `npm audit --omit=dev`: PASS — 0 vulnerabilities.
- Latest independent-audit reference was 359 tests / 2,291 assertions; current executable baseline contains one additional assertion and is authoritative for this correction pass.

### Approved target map

| Target | Independent-audit verdict | Correction boundary |
|---|---|---|
| SEC-021 | P1 / `REGRESSION_INTRODUCED` | Preserve durable intent and stable reference; make success/failure persistence conflict-safe; keep provider I/O outside transactions |
| SEC-022 raw persistence | P2 / `PARTIALLY_RESOLVED` | Minimize before every database write; never stage raw provider data in PostgreSQL |
| SEC-022 broad minimization | P2 / `PARTIALLY_RESOLVED` | Explicit webhook/action allowlists; retain only fields with current readers or documented audit need |
| SEC-022 prune/refund | P2 / `PARTIALLY_RESOLVED` | Exclude refund-capable PAID; transactionally lock/recheck each destructive candidate |
| SEC-010 purge coordination | P2 residual | Coordinate `Application -> ResultDocument`; fix physical-delete-first boundary without inventing retention duration |
| Document lock order | P2 residual | Standardize upload/review to `Application -> ApplicationRequirement -> Document` |
| Presence middleware | P3 | Remove duplicate route middleware while preserving read-only session, auth, throttle order |
| Hardening B/C documentation | P3 | Reconcile stale counts and overstatements only; preserve original audit and independent audit history |

### Explicit exclusions

- SEC-012 remains deferred; no duplicate-draft rule or constraint is introduced.
- Application and payment state graphs remain unchanged.
- Xendit gateway implementation/selection, OTP, authentication, chat semantics, document/result decisions, cancellation rules, and retention duration remain unchanged.
- `docs/audit/security-audit.md` and `docs/audit/security-hardening-bc-independent-audit.md` remain immutable.

## CP1 — SEC-021 payment race

Status: COMPLETE

Chosen conflict rule: a provider exception is an ambiguous checkout-attempt outcome and must not transition the durable intent from `PENDING` to `FAILED`. The stable reference stays reconcilable. A successful response is persisted only while the locked row is still the same logical `PENDING` intent; provider-authoritative `PAID`, `FAILED`, `EXPIRED`, `CANCELLED`, or refund states are never downgraded/reopened. Existing external identity may only be reused when it matches the returned identity.

Implementation: `ApplicationWorkflowService` now locks and re-reads the payment on every failure/success persistence path. An ambiguous exception records `payment.checkout_attempt_failed` without falsifying provider truth or consuming the durable `PENDING` recovery state. Checkout success is persisted through one shared locked method that rejects mismatched external identity, minimizes the payload before the write, and refuses to mutate any non-PENDING state. `reconcilePayment` performs a locked local preflight before provider I/O and the same locked final persistence afterward.

Deterministic regression: `CoordinatedPaymentGateway` uses explicit ready/finished file markers so two PostgreSQL processes reach the provider boundary together, the failed attempt completes its local handling before the successful attempt returns, and the result does not depend on a sleep race. Targeted payment/cancellation/webhook suite: **52 tests, 333 assertions**, all passing.

## CP2 — SEC-022 strict persistence minimization

Status: COMPLETE

All checkout and webhook payloads now pass through `PaymentPayloadMinimizer` before the first database write. Checkout, webhook event, payment-from-webhook, customer, action, and channel-property data each use explicit allowlists; unknown provider fields are dropped rather than accepted by broad negative filtering. The retained fields cover current payment readers, reconciliation identifiers, expiration, customer matching, and supported action rendering.

The regression suite also listens to database query bindings and proves synthetic forbidden marker values never cross the PostgreSQL persistence boundary, rather than checking only the final stored row. Targeted payload/webhook/payment suite: **54 tests, 290 assertions**, all passing.

## CP3 — SEC-022 pruning/refund correctness

Status: COMPLETE

Pruning now separates broad candidate discovery from authoritative mutation. Each webhook event or payment is re-read with `lockForUpdate()` inside its own short transaction, then its terminal status, age, and prior-prune marker are revalidated before replacement. The retained tombstone contains identifiers only and never preserves provider action values.

`PAID`, `REFUND_REQUESTED`, and `REFUNDING` are excluded because their payloads can still support refund/reconciliation work. Eligible payment states are limited to `FAILED`, `EXPIRED`, `CANCELLED`, and `REFUNDED`; completed/permanently invalid webhook events remain the only event candidates. A stale-candidate regression changes a previously selected payment to `REFUND_REQUESTED` before the locked pruner runs and proves the payload is retained. Targeted pruning/payment/concurrency suite: **43 tests, 229 assertions**, all passing.

## CP4 — SEC-010 purge coordination

Status: COMPLETE

The source and operational documentation define configured `retention_until` deletion but do not decide whether `COMPLETED` must preserve `hasVerifiedPrimaryResult()` forever after an approved retention deletion. That narrow question remains **DEFERRED — RETENTION BUSINESS DECISION**; this correction does not create an indefinite retention rule or change the configured duration.

The concurrency boundary is corrected independently of that decision. `ExpiredFilePurger` claims an eligible row while holding the same parent-first locks as workflow writers (`Application -> ResultDocument`, or `Application -> ApplicationRequirement -> Document`). The committed claim makes the file ineligible for verification, completion, and new policy-authorized downloads before physical deletion begins. Storage deletion occurs outside the claim transaction. Success finalizes the database tombstone and audit under the same locks; failure releases the owned claim, and claims abandoned for 15 minutes are retryable. `PrivateFileReader` takes the same bounded locks while authorizing and opening a stream, so purge cannot remove the path between authorization and stream acquisition.

Deterministic tests exercise purge-claim-first interleavings against completion, verification/rejection, and authorized result access, plus failure-claim release. Existing result concurrency, document purge, private streaming, and authorization tests remain green. Targeted suite: **30 tests, 85 assertions**.

Failure/recovery rule: a failed storage deletion restores `deletion_scheduled_at = null`; a process interruption after claim commit can be retried after the bounded 15-minute stale-claim interval. A process interruption after storage deletion but before finalization leaves access fail-closed through the persisted claim and is recoverable by the same retry path.

## CP5 — Document workflow lock order

Status: COMPLETE

Upload and review now use one hierarchy: `Application -> ApplicationRequirement -> Document`. Upload explicitly locks all existing versions after its parent locks before deactivating them and creating the next version. Review resolves only immutable identifiers before the transaction, then locks and validates the authoritative parent, requirement, and document in that order; it no longer trusts or locks the incoming `Document` first.

The PostgreSQL regression uses explicit file barriers: the upload process holds the application lock before the review process announces its attempt, guaranteeing overlap. Upload completes without deadlock; the stale review is rejected after recheck; exactly one active version remains; requirement and review state are valid. Targeted document/admin/purge suite: **30 tests, 97 assertions**.

## CP6 — Minor cleanup and documentation

Status: COMPLETE

The presence heartbeat route now registers `ReadOnlySession`, `auth`, and `throttle:presence-heartbeat` exactly once after removing the duplicated middleware chain. A route-stack regression asserts a single read-only-session middleware and continued removal of `StartSession`; heartbeat authorization and the 60-request throttle remain unchanged. Presence/chat targeted suite: **18 tests, 220 assertions**.

Small dated reconciliation notes were appended to `security-hardening-b.md` and `security-hardening-c.md`. They retain the original reports as historical checkpoints while explicitly superseding the ambiguous-provider-failure claim, the overbroad perpetual SEC-010 wording, the broad payload allowlist, PAID pruning, retained action secrets, and stale current-total interpretations. `security-audit.md` and `security-hardening-bc-independent-audit.md` were not modified.

## CP7 — Cross-security regression

Status: COMPLETE

Hardening A (`SEC-005`, `SEC-006`, `SEC-020`, `SEC-023`), verified Hardening B (`SEC-011`, `SEC-013`), Hardening C (`SEC-024`, `SEC-003`, `SEC-015`), and Hardening D production checks were rerun with the corrected payment, pruning, purge, document, and presence suites. Result: **117 tests, 761 assertions**, all passing. `SEC-012` remains deferred.

### Current lock-order register

| Path | Lock order | External work boundary |
|---|---|---|
| Result verification/completion/purge/access | `Application -> ResultDocument` | Result upload scan/storage occurs before workflow locks; purge delete occurs after claim commit; stream acquisition is bounded to the locked authorization boundary |
| Document upload/review/purge/access | `Application -> ApplicationRequirement -> Document` | Malware scan and private copy occur before upload transaction; failed post-store transaction cleanup occurs after rollback |
| Payment intent/cancellation/webhook | `Application -> Payment(s)` when both are needed; response persistence/reconcile may lock `Payment` alone | Provider call occurs between durable-intent commit and locked response persistence |
| Payment/webhook pruning | `Payment` alone or `WebhookEvent` alone | No provider I/O |
| Webhook settlement | `WebhookEvent -> Application -> Payment` | Authenticated payload is ledgered/minimized before processing; no provider HTTP call |
| OTP issue | `User`, then challenge mutation | Notification dispatch uses after-commit behavior |
| OTP verify | `AuthChallenge` | No mail transport under lock |
| Chat send | `ChatThread`, then message/state writes | Existing queued notification behavior remains unchanged |

No concrete opposing lock cycle remains in the corrected payment, result, document, prune, purge, OTP, or chat scope. No newly corrected path performs Xendit HTTP, mail transport, or malware scanning while holding database row locks. `PrivateFileReader` holds parent/file locks only through authorization and opening the local private stream—the minimum boundary needed to prevent purge from deleting between authorization and stream acquisition—and releases them before the response streams bytes.

## CP8 — Final verification

Status: COMPLETE

### Required commands

| Command | Result |
|---|---|
| `php artisan optimize:clear` | PASS |
| `php artisan view:cache` | PASS |
| `npm run build` | PASS — Vite 6.4.3, 57 modules transformed |
| `php artisan test --no-coverage` | PASS — **368 tests, 2,358 assertions** |
| `vendor/bin/pint --test` | PASS after formatting the affected webhook regression imports/strict-types declaration |
| `git diff --check` | PASS |
| `composer audit --locked` | PASS — no security advisories |
| `npm audit --omit=dev` | PASS — 0 vulnerabilities |

The new concurrency tests passed in the full suite; no flaky failure was accepted as baseline. No migration, package-lock change, Composer dependency change, real provider call, real email, or real user file was introduced.

## Final correction matrix

| Concern | Independent Audit | Correction Result |
|---|---|---|
| SEC-021 asymmetric payment race | P1 | **CORRECTED** — ambiguous failure preserves `PENDING`; locked success/failure persistence cannot overwrite provider-authoritative truth |
| SEC-022 raw persistence | P2 | **CORRECTED** — minimization occurs before the first database write |
| SEC-022 broad minimization | P2 | **CORRECTED** — explicit allowlists reject unknown fields by default; database-binding regression proves forbidden markers never cross persistence |
| SEC-022 prune/refund race | P2 | **CORRECTED** — refund-capable states excluded; each destructive candidate locked and rechecked; action secrets removed from tombstones |
| SEC-010 purge coordination | P2 | **CORRECTED WITH NARROW DEFERRED SEMANTIC DECISION** — claim/lock/delete/finalize is coordinated; perpetual post-retention availability for completed results remains a business decision |
| Document lock order | P2 | **CORRECTED** — upload/review/purge/access share parent-first order; deterministic overlap test proves no opposing-order deadlock |
| Presence middleware duplicate | P3 | **CORRECTED** — one read-only session, auth, and heartbeat throttle registration |

## Explicit answers

1. **Can a failed concurrent provider attempt overwrite a successful checkout?** No. Both paths re-read the locked payment; ambiguous failure does not mutate state, and success only fills the same still-`PENDING` intent without replacing a different provider identity.
2. **Can a provider-backed payment become irrecoverably `FAILED` because checkout threw?** No. A checkout exception remains a recoverable `PENDING` intent. An authenticated provider webhook may still legitimately transition it to `FAILED` according to the unchanged payment graph.
3. **Can reconcile fabricate or downgrade payment truth?** No. It only operates on `PENDING`, performs provider I/O outside locks, and its final locked write refuses non-`PENDING` state or mismatched identity.
4. **Is raw webhook/provider payload persisted before minimization?** No in the corrected checkout and webhook persistence paths.
5. **Are unknown webhook fields persisted by default?** No. Explicit nested allowlists are authoritative.
6. **Can pruning affect refund-capable `PAID` payments?** No. `PAID`, `REFUND_REQUESTED`, and `REFUNDING` are excluded.
7. **Does pruning lock and recheck before destructive write?** Yes, per row in a short transaction.
8. **Can `files:purge` race completion or result verification through stale physical/database state?** No in the corrected concurrency boundary. The purge claim uses `Application -> ResultDocument`, becomes visible before storage deletion, and both workflow operations reject claimed rows. Whether a completed result may later be unavailable after its approved retention date remains deferred because no policy defines that lifetime.
9. **Can document upload/review deadlock through the former opposing row order?** The concrete opposing order is removed. Both now take `Application -> ApplicationRequirement -> Document`, and the barrier-driven PostgreSQL test completes without deadlock.
10. **Did any correction alter product state graphs?** No.
11. **Did any correction introduce a migration or dependency?** No.

## Files changed by this correction pass

### Production

- `app/Services/ApplicationWorkflowService.php`
- `app/Services/PaymentPayloadMinimizer.php`
- `app/Services/PaymentPayloadPruner.php`
- `app/Http/Controllers/Webhooks/XenditWebhookController.php`
- `app/Console/Commands/PrunePaymentPayloads.php`
- `app/Services/ExpiredFilePurger.php`
- `app/Services/PrivateFileReader.php`
- `app/Console/Commands/PurgeExpiredFiles.php`
- `app/Services/AdminWorkflowService.php`
- `app/Services/DocumentWorkflowService.php`
- `app/Models/Application.php`
- `app/Policies/DocumentPolicy.php`
- `app/Policies/ResultDocumentPolicy.php`
- `app/Http/Controllers/Client/DocumentController.php`
- `app/Http/Controllers/Client/ResultDocumentController.php`
- `routes/web.php`

### Tests/support

- `tests/Support/CoordinatedPaymentGateway.php`
- `tests/Feature/Payments/PaymentDualWriteHardeningTest.php`
- `tests/Feature/Payments/PayloadMinimizationTest.php`
- `tests/Feature/Webhooks/XenditWebhookTest.php` (constructor parity and final formatting)
- `tests/Feature/Files/FilePurgeCoordinationTest.php`
- `tests/Feature/Documents/DocumentLockOrderConcurrencyTest.php`
- `tests/Feature/Chat/ChatPresenceTest.php`

### Documentation

- `docs/audit/security-hardening-bc-corrections.md`
- `docs/audit/security-hardening-b.md` (dated factual reconciliation note only)
- `docs/audit/security-hardening-c.md` (dated factual reconciliation note only)

Unrelated existing dirty-worktree changes were preserved. `docs/audit/security-audit.md` and `docs/audit/security-hardening-bc-independent-audit.md` remain unchanged by this correction pass.

## Final status

**HARDENING B/C SECURITY CORRECTIONS COMPLETE WITH DEFERRED RETENTION SEMANTIC DECISION**

Deferred question: after an approved retention deletion, must a previously completed application continue satisfying `hasVerifiedPrimaryResult()` indefinitely, or is that invariant required only at completion time? No answer or retention duration was invented in this pass.
