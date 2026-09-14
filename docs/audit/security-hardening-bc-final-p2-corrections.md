# Security Hardening B/C — Final P2 Corrections

Date: 2026-09-11  
Scope: only SEC-021 provider outcome taxonomy, throwing-delete purge recovery, and client document-destroy locking/TOCTOU  
Classification: **B/C FINAL P2 CORRECTIONS COMPLETE WITH DEFERRED RETENTION BUSINESS DECISION**

This checkpoint records the bounded final P2 correction pass requested after `security-hardening-bc-corrections-independent-verification.md`. It does not create a Hardening E phase and does not change the application/payment state graphs, provider selection, durable reference model, webhook/cancellation/refund behavior, retention duration, SEC-012, or completed-result retention policy.

## CP0 — Baseline

Status: COMPLETE

The required baseline was executed before this correction pass:

| Command | Result |
|---|---|
| `git status --short` | PASS as an inventory step. The checkout was already dirty with the in-progress Hardening B/C/D implementation and an unrelated untracked `backupcode_bantudaftarin/`; those pre-existing changes were preserved. |
| `php artisan test --no-coverage` | PASS — **368 tests, 2,358 assertions**, 0 failures, 86.40 seconds. |
| `npm run build` | PASS — Vite 6.4.3, 57 modules transformed. |
| `vendor/bin/pint --test` | PASS. |
| `git diff --check` | PASS. |
| `composer audit --locked` | PASS — no security vulnerability advisories. |
| `npm audit --omit=dev` | PASS — 0 vulnerabilities. |

No baseline assertion count was normalized or edited. PostgreSQL `bantu_daftarin_mvp_test` remained the test database.

## CP1 — Provider outcome taxonomy

Status: COMPLETE

### Correction

A small internal taxonomy now distinguishes `PaymentGatewayDefinitiveException` from `PaymentGatewayAmbiguousException`, both remaining within the existing `PaymentGatewayException` boundary. Controllers receive no provider internals.

`XenditPaymentProvider` classifies the currently supported create-payment outcomes as follows. The mapping is deliberately a status-and-known-error-code allowlist, not a blanket rule that every 4xx is definitive. It is based on Xendit's current [Create Payment Request API](https://docs.xendit.co/apidocs/create-payment-request) and [eWallet error documentation](https://docs.xendit.co/ewallet/integrations/errors), inspected on 2026-09-11.

| Outcome | Classification | Local behavior |
|---|---|---|
| Missing secret/channel or unsupported provider before HTTP | Definitive no-call | No request is sent; a durable, still-unbacked `PENDING` intent is locked and changed to `FAILED`. |
| Known validation rejection (`400` plus an allowlisted validation code) | Definitive provider rejection | Same locked `PENDING -> FAILED` behavior, only while no provider identity/payload exists. |
| Known authentication/config rejection (`401`/`403` plus an allowlisted auth/config code) | Definitive provider rejection | Same guarded behavior; secret/config values are absent from exceptions, logs, and UI. |
| Rate limit (`PAYMENT_REQUEST_RATE_LIMITED`, `429`, or an unknown rate-limit response) | Ambiguous, conservatively | Keep `PENDING` and the existing `reference_id`. |
| Provider `5xx` or other non-allowlisted HTTP failure | Ambiguous | Keep `PENDING` and the existing `reference_id`. |
| Timeout or connection/transport failure (`ConnectionException`) | Ambiguous | Keep `PENDING` and the existing `reference_id`. |
| Successful HTTP response without a usable array body or payment identity | Ambiguous | Keep `PENDING` and the existing `reference_id`. |

The definitive finalizer locks and re-reads the exact payment. It mutates only the same reference while it is still `PENDING` and has no `external_id`, checkout URL, or provider payload. It therefore cannot overwrite `PAID`, `CANCELLED`, `EXPIRED`, provider-backed `FAILED`, refund states, or any checkout identity.

The success path also locks and re-reads. In the deterministic success-versus-definitive-failure interleaving, a definitive local failure may commit first, but the later provider success is stronger evidence and may supersede only that audit-marked, identity-free local failure for the same reference. The final row is `PENDING` with provider identity, never `FAILED` plus provider checkout identity. Ambiguous failures remain recoverable for retry, reconciliation, or webhook processing. `payments:reconcile` uses the same taxonomy, does not create a new reference, and ignores terminal states.

### Focused evidence

- `PaymentProviderOutcomeTest`: missing pre-call configuration, known validation/authentication rejection, synthetic timeout, real Laravel connection exception, malformed successful response, rate-limit/5xx ambiguity, stable reference, and reconcile taxonomy.
- `PaymentDualWriteHardeningTest`: deterministic success-versus-ambiguous and success-versus-definitive races, one durable row/reference, no hybrid `FAILED` row, webhook recovery, cancellation preservation, and operator reconciliation.
- Serial focused result: **19 tests, 89 assertions**, all passing.
- All provider calls in these tests use Laravel HTTP fakes or synthetic test gateways; no real Xendit request was made.

## CP2 — Purge throwing-delete recovery

Status: COMPLETE

`ExpiredFilePurger` now catches only the configured Laravel/Flysystem deletion boundary, `League\Flysystem\UnableToDeleteFile`, around the filesystem delete. On that catch it re-locks the same parent-first hierarchy and clears `deletion_scheduled_at` only if the exact claim timestamp is still owned by that worker, then rethrows for command-level reporting. The existing `false` return path remains ownership-safe.

The purge command catches that narrow per-candidate failure, reports it, increments a failure count, continues evaluating unrelated eligible files, and returns a non-zero command status if any candidate failed. It does not set `deleted_at` and does not emit `file.purged` for a failed delete.

The injected throwing-adapter test proves the claim exists at delete time, the owned claim is immediately released, `deleted_at` stays null, the record remains eligible, no success audit is written, and the next candidate is still purged. A separate timestamp-ownership test proves an obsolete worker cannot release a newer claim. A stale-claim/missing-physical-file retry proves a successful delete plus database finalization retry is safe and auditable.

Serial focused result: **7 tests, 33 assertions**, all passing.

Crash semantics are not overstated: a catchable adapter exception gets immediate release; process or machine death still relies on stale-claim recovery.

**15-minute lease: UNCHANGED — OUT OF SCOPE.**

## CP3 — Client document destroy locking

Status: COMPLETE

The controller retains route/model identification, `DocumentPolicy` authorization, service invocation, and redirect response. `DocumentWorkflowService::destroy` now owns the authoritative workflow:

1. Read only immutable document/parent identifiers from a fresh identity query.
2. In a short transaction, lock `Application -> ApplicationRequirement -> Document`, validate all relationships, recheck client role/ownership, active/deleted state, claim ownership/expiry, and current upload/removal eligibility, then persist a deletion claim.
3. Delete the private file outside the database transaction.
4. In a second short transaction, reacquire the same hierarchy, require the exact owned claim, finalize `active=false`, `deleted_at`, and the deletion reason, and write `document.deleted`.
5. On the narrow filesystem exception or a `false` delete, reacquire the hierarchy and release only the still-owned claim; do not emit a false success audit.

The service does not trust stale `active`, review, requirement, application-stage, or deletion fields supplied by the incoming model. Locking supplements rather than replaces the controller policy and locked ownership checks.

### Deterministic evidence

- Normal allowed deletion, cross-owner denial, inactive denial, and stage becoming ineligible before the lock.
- Review-first rejects later destroy; destroy-first leaves a deleted version that review rejects.
- A PostgreSQL process-barrier test overlaps destroy and upload and proves no deadlock, exactly one active new version, monotonically increasing version number, and a valid `PENDING` requirement.
- A purge claim blocks a destroy claim; exact claim ownership remains authoritative.
- Access admitted and stream-opened first may finish after deletion, while a new access attempt is denied after the claim/finalization boundary.
- Throwing filesystem delete restores the claim state and leaves the active database record/audit unchanged.

Serial focused result: **10 tests, 50 assertions**, all passing.

### Lock-order register

| Path | Row-lock order | External I/O boundary |
|---|---|---|
| Client document destroy | `Application -> ApplicationRequirement -> Document` | Single-file delete occurs between two short transactions. |
| Document upload | `Application -> ApplicationRequirement -> Document versions` | Malware scan/private store occurs before row locks; transaction failure cleans the staged file afterward. |
| Document review | `Application -> ApplicationRequirement -> Document` | No provider/mail/malware/filesystem stream under the locks. |
| Expired document purge | `Application -> ApplicationRequirement -> Document` | Single-file delete occurs after claim commit and before locked finalization. |
| Private document access | `Application -> ApplicationRequirement -> Document` | Authorization and safe stream acquisition occur under locks; response delivery occurs after commit. |
| Result verify/complete/purge/access | `Application -> ResultDocument` | Existing short coordinated boundaries remain unchanged. |

There is no opposing `Document -> Application` lock path in the corrected workflow.

## CP4 — Cross-regression

Status: COMPLETE

One serial cross-security run covered 20 focused files and passed **135 tests, 845 assertions** in 66.52 seconds.

| Area | Re-verified evidence |
|---|---|
| Hardening A — SEC-005, SEC-006, SEC-020, SEC-023 | Neutral password-reset response/locale; persistent Livewire eligibility middleware; webhook retry, ledger, and concurrent idempotence. |
| Hardening B — SEC-010, SEC-011, SEC-013, SEC-021 | Result completion, review assignment, OTP concurrency, durable payment intent and both asymmetric provider races. |
| Hardening C — SEC-024, SEC-003, SEC-015, SEC-022 | Chat abuse limits; strict legacy payment validation; private/local storage separation; payload minimization/pruning safety and webhook compatibility. |
| Hardening D | Production checker, fail-closed malware guard/health check, exact security headers/CSP regression, and mailer/nested failover guard. |
| Final P2 additions | Provider taxonomy, throwing-delete recovery/continuation, document destroy authorization/claim/lock order/concurrency. |

Source inspection confirms no corrected path performs Xendit HTTP, mail delivery, or malware scanning while holding database row locks. The destroy/purge paths use a bounded single-file filesystem phase and do not stream a full response under locks.

Deferred items remain deferred:

- SEC-012 application draft concurrency/business semantics.
- SEC-022 production provider/webhook retention duration.
- Completed-result retention business decision.

**P3 findings: OUT OF SCOPE — OPTIONAL P3 CLEANUP AFTER P2 VERIFICATION.** The non-`PENDING` external-ID mismatch handling, pruning-test naming/reframing, 15-minute lease heartbeat/bound, `PrivateFileReader` response-setup cleanup, and presence auth/throttle test-count assertion were intentionally not changed.

An initial attempt to run multiple `RefreshDatabase` suites concurrently was invalid because they raced while rebuilding the same PostgreSQL test schema. It produced duplicate/missing-table errors only. All authoritative targeted and cross-regression results above were rerun serially and passed.

## CP5 — Final verification

Status: COMPLETE

| Command | Result |
|---|---|
| `php artisan optimize:clear` | PASS — config, application cache, compiled services, events, routes, and views cleared. |
| `php artisan view:cache` | PASS — Blade templates cached successfully. |
| `npm run build` | PASS — Vite 6.4.3, 57 modules transformed, production assets built. |
| `php artisan test --no-coverage` | PASS — **389 tests, 2,457 assertions**, 0 failures, 114.71 seconds. |
| `vendor/bin/pint --test` | PASS. |
| `git diff --check` | PASS. |
| `composer audit --locked` | PASS — current Packagist-backed rerun found no security vulnerability advisories. The first restricted-network invocation exited 0 from available metadata but reported a connection timeout, so it was rerun with network access for authoritative current evidence. |
| `npm audit --omit=dev` | PASS — 0 vulnerabilities. |

No migration or dependency was introduced by this pass. The final executable requirement is satisfied with zero failures.

## Final matrix

| P2 Defect | Before | Correction | Tests | Final |
|---|---|---|---|---|
| Definitive provider failure classification | Every gateway exception remained ambiguous, so proven no-call/rejection failures could remain `PENDING`. | Bounded definitive/ambiguous exception taxonomy; locked guarded failure finalizer; success may supersede only same-reference, identity-free local definitive failure. | Provider outcome matrix plus deterministic ambiguous/definitive races, webhook/cancel/reconcile regressions. | COMPLETE |
| Throwing filesystem purge recovery | `throw=true` adapter exception bypassed the `false` release branch, left the claim until lease recovery, and aborted the command loop. | Catch narrow Flysystem delete exception, ownership-safe immediate release, per-file continuation, non-zero aggregate exit. | Throwing adapter, claim ownership, continuation, retry/finalization, audit assertions. | COMPLETE |
| Client document destroy TOCTOU | Mutable authorization/state was read unlocked; physical delete preceded authoritative recheck. | Service-owned two-transaction claim pattern with `Application -> Requirement -> Document` locks and owned failure recovery. | Allowed/denied/stale/review/upload/purge/access/storage-failure and no-deadlock coverage. | COMPLETE |

## Explicit answers

1. **Can a definitive no-call/provider rejection remain indefinitely `PENDING`?** No. A still-`PENDING`, same-reference, identity-free intent is locked and changed to `FAILED`.
2. **Are ambiguous provider outcomes still preserved as recoverable `PENDING`?** Yes, with the original stable reference.
3. **Can a failed provider attempt overwrite concurrent success?** No. Both finalizers lock/re-read; terminal or provider-backed truth is preserved.
4. **Can a provider-backed checkout become `FAILED` incorrectly?** No through these local failure paths; definitive failure requires all provider identity/payload fields to be empty, and the race test rejects the hybrid state.
5. **Can a filesystem delete exception leave a claim until lease expiry?** Not for the catchable configured Flysystem delete exception; it is immediately ownership-safely released. A process crash still uses the stale lease.
6. **Can an old purge worker release a newer claim?** No. Exact claim timestamp ownership is required.
7. **Does one file-delete failure abort unrelated purge work?** No. The command continues per candidate and returns failure globally.
8. **Can client document destroy act on stale application/review state?** No. It re-fetches and rechecks under parent-first locks before claiming deletion.
9. **Does client destroy now use `Application -> Requirement -> Document`?** Yes, in claim, release, and finalization transactions.
10. **Can destroy race upload/review/purge/access without stale-authority corruption?** The corrected shared lock/claim boundaries prevent stale mutation; deterministic ordering tests cover each interaction, including a real PostgreSQL overlapping destroy/upload process test.
11. **Were payment/application state graphs changed?** No.
12. **Was any migration/dependency introduced?** No.
13. **Are the remaining P3 findings intentionally untouched?** Yes; they are explicitly out of scope for this pass.
14. **Does the completed-result retention question remain deferred?** Yes; it remains a business/operations decision.

## Final classification

**B/C FINAL P2 CORRECTIONS COMPLETE WITH DEFERRED RETENTION BUSINESS DECISION**

Stop after this pass and wait for independent verification. No P3 cleanup or new hardening phase is included.
