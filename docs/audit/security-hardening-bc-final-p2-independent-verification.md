# Security Hardening B/C — Final P2 Independent Verification

Date: 2026-09-11  
Mode: independent source-level audit only  
Scope: P2-A provider outcome taxonomy, P2-B throwing filesystem purge recovery, and P2-C client document-destroy locking/TOCTOU only  
Final classification: **B/C FINAL P2 INDEPENDENT VERIFICATION COMPLETE — CORRECTION REQUIRED**

## Executive result

The purge exception recovery and client document-destroy corrections are correct in current source and are supported by the requested deterministic tests. The provider taxonomy itself, its bounded HTTP mapping, the definitive-failure finalizer, and the two tested checkout races are substantially correct. The new `FAILED -> PENDING` reconciliation escape hatch is not sufficiently bounded against later webhook truth, however.

A concrete three-event interleaving remains possible:

1. A local definitive checkout finalizer changes the payment to identity-free `FAILED` and records `payment.checkout_definitive_failed`.
2. A genuine provider `FAILED` webhook for that same reference is processed while the payment is already `FAILED`. Because the incoming target equals the current status, the webhook handler does not persist its computed provider payload, although it marks the event processed.
3. A delayed provider-success response from the competing checkout call then finds the row still identity-free and payload-free plus the earlier local audit marker, passes `canSupersedeLocalDefinitiveFailure()`, directly reopens it to `PENDING`, and persists checkout identity.

That contradicts the required invariant that webhook-confirmed/provider-authoritative `FAILED` truth can never be superseded. It is a P2 implementation defect and the missing regression case is a P2 test gap. No correction was made in this audit.

## Audit boundaries and method

The required engineering/design instructions and the complete prior audit chain were read before evaluation. The correction report was treated only as a set of claims. Current source, route entry points, enum transition graphs, installed Laravel/Flysystem behavior, and tests were independently traced. Targeted database suites were run serially because they share the PostgreSQL test database.

The Xendit mapping was checked against the current official [Create a payment request API](https://docs.xendit.co/apidocs/create-payment-request) and official [Xendit error-simulation documentation](https://docs.xendit.co/docs/simulate-error-scenarios). No real provider request was sent and no secret was printed.

Only this report was created. Production source, tests, migrations, dependencies, configuration, and prior audit documents were not edited.

## Baseline

The checkout was already dirty with the in-progress Hardening B/C/D work and an unrelated untracked `backupcode_bantudaftarin/`. Those changes were preserved.

| Command | Independent result |
|---|---|
| `git status --short` | Completed as inventory; pre-existing modified/untracked files present. |
| `php artisan test --no-coverage` | PASS — **389 tests, 2,458 assertions**, 0 failures. |
| `npm run build` | PASS — Vite 6.4.3, 57 modules transformed. |
| `vendor/bin/pint --test` | PASS. |
| `git diff --check` | PASS. |
| `composer audit --locked` | PASS — no security vulnerability advisories. |
| `npm audit --omit=dev` | PASS — 0 vulnerabilities. |

The baseline assertion count is the observed value, not the 2,457 previously reported. The final full run below observed 2,456 assertions; both runs had the same 389 tests and zero failures. The counts were not normalized.

## P2-A — Provider outcome taxonomy

### Taxonomy structure

`PaymentGatewayDefinitiveException` and `PaymentGatewayAmbiguousException` both extend the existing `PaymentGatewayException` boundary. `PaymentGatewayRouter` treats an unsupported provider as a definitive pre-call failure. `XenditPaymentProvider` checks the supported channel and local secret before constructing the HTTP request and throws a definitive exception when either is absent (`app/Services/XenditPaymentProvider.php:25-30`). `PaymentProviderOutcomeTest` verifies that this path sends no HTTP request.

Transport and response handling is conservative:

- Laravel `ConnectionException` becomes ambiguous (`XenditPaymentProvider.php:71-74`).
- A non-success response is definitive only when both status and an allowlisted error code match; otherwise it is ambiguous (`XenditPaymentProvider.php:76-88`).
- A non-array success response or a success response without a provider payment identity is ambiguous (`XenditPaymentProvider.php:90-98`).
- Rate-limit and 5xx codes are not in the definitive allowlist.

### Actual definitive HTTP allowlist

The current source uses this exact status-plus-code mapping (`XenditPaymentProvider.php:109-135`):

| HTTP status | Recognized definitive codes |
|---|---|
| 400 | `INVALID_VALUE_ERROR`, `API_VALIDATION_ERROR`, `CARD_EXPIRED`, `INVALID_PAYMENT_DETAILS`, `INVALID_TOKEN` |
| 401 | `INVALID_API_KEY`, `INVALID_MERCHANT_CREDENTIALS`, `INVALID_TOKEN` |
| 403 | `REQUEST_FORBIDDEN_ERROR`, `CHANNEL_NOT_ACTIVATED`, `UNSUPPORTED_CONTENT_TYPE`, `SKIP_3DS_FORBIDDEN`, `INVALID_MERCHANT_SETTINGS`, `ACCOUNT_ACCESS_BLOCKED` |
| 404 | `DATA_NOT_FOUND`, `CALLBACK_URL_NOT_FOUND` |

The current `/v3/payment_requests` documentation directly lists the five 400 codes above, including `PAYMENT_REQUEST_RATE_LIMITED` as a separate rate-limit error; the source correctly leaves that code ambiguous. It also directly lists `SKIP_3DS_FORBIDDEN`, `INVALID_MERCHANT_SETTINGS`, and `ACCOUNT_ACCESS_BLOCKED` at 403, and server/channel/issuer failures at 500/503; the source correctly leaves 5xx ambiguous. The remaining auth/config/not-found combinations are documented Xendit rejection families and are bounded by their expected status. An unknown code at 400, 401, 403, or any other 4xx does not become definitive merely because of its status.

The current v3 table also contains 409 conflict outcomes. They are intentionally not treated as definitive here. In particular, conflict/idempotency outcomes cannot safely disprove earlier provider acceptance, so ambiguity is the conservative result.

**Taxonomy verdict apart from recovery provenance: `VERIFIED_CORRECT`.**

### Definitive failure finalizer

`createPayment()` commits the durable intent under `Application -> Payment` locks, performs provider HTTP after that transaction, and catches definitive and ambiguous exceptions separately (`ApplicationWorkflowService.php:200-281`). `reconcilePayment()` uses the same exception split (`ApplicationWorkflowService.php:284-307`).

Before writing `PENDING -> FAILED`, `finalizeDefinitiveCheckoutFailure()`:

- locks and re-reads the exact payment;
- requires current `PENDING` status;
- verifies the same reference;
- requires empty `external_id`, checkout URL, and provider payload;
- only then writes `FAILED`, `failed_at`, the definitive marker, and state-change audit.

Evidence: `ApplicationWorkflowService.php:351-377`. A success already persisted as provider identity/payload or any terminal status is therefore not overwritten by this local failure finalizer.

**Finalizer verdict: `VERIFIED_CORRECT`.**

### Critical recovery-path regression

`PaymentStatus::FAILED` is terminal in the normal graph (`app/Enums/PaymentStatus.php:16-24`). The checkout success path deliberately bypasses that graph with a direct `forceFill(['status' => PENDING])` at `ApplicationWorkflowService.php:318-325`. Such a technical reconciliation bypass is acceptable only if its provenance guard excludes every provider-authoritative failure.

The guard requires empty provider identity/URL/payload, the same local payment reference, a non-conflicting provider reference when supplied, and existence of any `payment.checkout_definitive_failed` audit for the payment (`ApplicationWorkflowService.php:397-416`). It does not bind the marker to a unique checkout-call identity, nor does it rule out a later processed provider webhook.

The webhook handler exposes a concrete failure of that proof:

- It computes a minimized provider payment payload at `XenditWebhookController.php:156-157`.
- It saves that payload when a different target transition is allowed (`:158-163`) or when there is no target status (`:166-168`).
- When `targetStatus` equals the payment's current status, neither branch runs. The payload and provider identity remain absent.
- It nevertheless marks the webhook event `PROCESSED` and records `payment.webhook_processed` (`:182-187`).

Therefore an already locally marked `FAILED` row can receive and process a same-reference provider `FAILED` webhook while retaining exactly the blank fields required by `canSupersedeLocalDefinitiveFailure()`. A delayed checkout success can then reopen that webhook-confirmed failure. The persisted webhook ledger/audit is not consulted by the supersede guard.

This is not an unrestricted resurrection of every `FAILED` row: a normal `PENDING -> FAILED` webhook writes non-empty provider payload and is blocked, and a row with external identity/URL/payload is blocked. It is nonetheless a real provider-authoritative/webhook-confirmed subset, which is enough to violate the explicit P2 requirement.

**Source verdict: `NEW_REGRESSION_FOUND`.**

### Race-test evaluation

The two process tests use real deterministic marker barriers:

- Both provider calls write their reference-bearing ready marker and wait for both calls to enter (`tests/Support/CoordinatedPaymentGateway.php:20-22`).
- The success worker additionally waits for `failure.finished` (`:32-44`).
- Each failure worker writes `failure.finished` only after `createPayment()` has thrown, so its local failure handling/finalizer has returned (`PaymentDualWriteHardeningTest.php:125-154` and `:173-188`).

The ambiguous race asserts the same durable reference in both workers and both markers, one payment row, provider identity/URL, later webhook reconciliation to `PAID`, and application confirmation (`PaymentDualWriteHardeningTest.php:156-180`). The definitive race asserts the same reference, one row, final provider-backed `PENDING`, no `failed_at`, and one supersede audit (`:190-208`). Separate tests confirm cancellation stays `CANCELLED` and a later reconcile does not downgrade webhook-confirmed `PAID` (`:269-321`).

Those tests prove the two intended local success/failure interleavings. They do **not** insert a provider `FAILED` webhook after the definitive finalizer and before the delayed success response. Existing webhook tests cover `PENDING -> FAILED` and rejection of a late failure against `PAID`, but not the same-status branch plus an earlier local definitive marker.

**Test verdict: `PARTIALLY_CORRECT` — existing tests pass and prove their stated local races, but the security-critical webhook interleaving is absent.**

### Reconciliation behavior

`payments:reconcile` resolves an exact existing reference and delegates to `reconcilePayment()`. The workflow briefly locks/re-reads the payment, returns unchanged unless it is incomplete `PENDING`, performs provider I/O outside the transaction, applies the same definitive/ambiguous exception taxonomy, and persists only provider checkout evidence (`ApplicationWorkflowService.php:284-307`). It does not mint a new logical reference, invent `PAID`, downgrade a terminal payment, or reopen a cancelled application. The dangerous `FAILED` bypass is reached only by an already in-flight success calling the common checkout persistence method, not by the reconcile command because reconcile returns early for `FAILED`.

### Required P2-A answers

| Question | Answer |
|---|---|
| Can a definitive no-call failure remain indefinitely `PENDING`? | **No** in handled execution: the guarded finalizer changes the same still-unbacked intent to `FAILED`. |
| Can a known definitive provider rejection remain indefinitely `PENDING`? | **No** for the bounded recognized rejection pairs; the same guarded finalizer runs. Unknown/conflict outcomes remain ambiguous intentionally. |
| Can an unknown/ambiguous failure incorrectly become `FAILED`? | **No** through the inspected provider/create/reconcile taxonomy. |
| Can concurrent failure overwrite success? | **No** through the local definitive or ambiguous finalizers; both re-read under the payment lock and preserve persisted provider truth. |
| Can provider-backed `FAILED` be resurrected? | **Yes, for a provider-authoritative failure confirmed by a same-status webhook after the local marker but before delayed success.** The webhook ledger proves provider involvement even though the handler fails to copy that proof into the payment row. |
| Is the `FAILED -> PENDING` recovery narrowly provenance-bound? | **Partly, but not sufficiently.** Identity/payload/reference/audit checks narrow it, but they do not exclude later processed webhook truth. |
| Does webhook provider truth remain authoritative? | **No** in the concrete same-status interleaving above. |

**P2-A final verdict: `NEW_REGRESSION_FOUND`.**

## P2-B — Throwing filesystem purge recovery

### Exception boundary

The private and quarantine disks are configured with `'throw' => true` (`config/filesystems.php:32-46`). Laravel's installed `FilesystemAdapter::delete()` catches Flysystem `UnableToDeleteFile` and rethrows that exact type when exception throwing is enabled (`vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemAdapter.php:571-589`, `:1091-1094`). The installed local adapter throws `League\Flysystem\UnableToDeleteFile` only when unlink fails and the file still exists; an already absent path is a successful no-op (`vendor/league/flysystem-local/LocalFilesystemAdapter.php:136-148`).

`ExpiredFilePurger::purge()` catches only `UnableToDeleteFile` around delete (`app/Services/ExpiredFilePurger.php:28-34`). It does not catch `Throwable`, `Exception`, or arbitrary programming failures. The command uses the same narrow per-candidate catch (`app/Console/Commands/PurgeExpiredFiles.php:40-47`).

### Claim recovery and continuation

Claim, release, and finalize all call the same parent-first locker. For `Document`, it locks `Application -> ApplicationRequirement -> Document`; for `ResultDocument`, it locks `Application -> ResultDocument` (`ExpiredFilePurger.php:105-127`). Release clears only a non-deleted row whose `deletion_scheduled_at` equals this worker's exact claim timestamp (`:94-101`, `:141-144`). Thus an old worker cannot clear a newer claim.

On a caught exception or a `false` delete result, the purger releases the owned claim and never enters finalization. `deleted_at` remains null and no `file.purged` audit is written. The command reports the candidate, increments failures, continues the inner loop, continues later model classes, and returns failure when any candidate failed (`PurgeExpiredFiles.php:20-62`).

If physical deletion succeeds but the process dies or database finalization fails, the claim remains. After the unchanged 15-minute stale threshold, a worker can reclaim it. The installed local adapter treats the absent path as successfully deleted; finalization then records the tombstone and one success audit. This is also covered by the stale-claim/absent-file test.

### Test quality

`FilePurgeCoordinationTest::test_throwing_delete_releases_only_owned_claim_and_command_continues_with_failure_exit()` injects a throwing `FilesystemAdapter` through the actual `files:purge -> ExpiredFilePurger::purge()` path. It asserts the first claim exists at delete time, throws `UnableToDeleteFile`, observes immediate release and unchanged `deleted_at`, confirms no success audit, processes a second candidate successfully, and requires a failed command exit (`tests/Feature/Files/FilePurgeCoordinationTest.php:102-139`). Separate tests prove obsolete-worker ownership safety and stale absent-file finalization (`:141-168`).

### Required P2-B answers

| Question | Answer |
|---|---|
| Can the configured delete exception leave the claim until the 15-minute lease? | **No** for the handled `UnableToDeleteFile`; its owned claim is released immediately. A process death still intentionally relies on stale recovery. |
| Can an old worker clear a new claim? | **No**; exact timestamp ownership is required under the same locks. |
| Can one failed delete abort unrelated candidates? | **No** for the configured delete exception; the command catches per candidate and continues. |
| Can the command return success after a candidate failure? | **No**; any recorded failure produces a non-zero exit. |
| Can delete-success/finalize-failure recover safely? | **Yes**; stale reclaim plus absent-file no-op permits database finalization and audit. |

**Source verdict: `VERIFIED_CORRECT`. Test verdict: `VERIFIED_CORRECT`. P2-B final verdict: `VERIFIED_CORRECT`.**

The accepted 15-minute lease/heartbeat design was not reopened.

## P2-C — Client document destroy locking / TOCTOU

### Controller and authoritative service boundary

There is one client delete route, `DELETE /documents/{documentId}`, bound to `DocumentController::destroy` (`routes/client.php:31`). The controller resolves `public_id`, invokes `DocumentPolicy::delete`, calls `DocumentWorkflowService::destroy`, and returns the redirect (`app/Http/Controllers/Client/DocumentController.php:40-47`). The policy requires a client who owns the document's application (`app/Policies/DocumentPolicy.php:34-37`). No alternate client destroy entry point with direct `Storage::delete()` was found.

The service performs a fresh identity-only query selecting `id`, `application_id`, and `application_requirement_id` (`DocumentWorkflowService.php:154-158`). It does not trust the incoming model's mutable active/review/application/requirement/deletion state.

### Locked authorization, claim, release, and finalization

The common `lockDocumentHierarchy()` acquires `Application -> ApplicationRequirement -> Document` and revalidates both parent relationships (`DocumentWorkflowService.php:236-250`). Claim, release, and finalization all use it (`:160-188`, `:203-218`, `:252-266`).

Under those locks, claim re-establishes:

- authenticated actor is a client and owns the locked application;
- the locked requirement belongs to the locked application;
- the locked document belongs to both locked parents;
- document is active and not deleted;
- no live claim exists (or the configured claim is stale);
- the current locked application and requirement still satisfy `canClientUpload()`, which is the current removal eligibility rule.

Only after those checks does it save `deletion_scheduled_at`; the first transaction returns and commits before the filesystem delete begins (`DocumentWorkflowService.php:160-191`). A stale pre-lock decision therefore cannot authorize physical deletion.

On `UnableToDeleteFile` or `false`, release reacquires the same hierarchy and clears only this worker's exact claim while `deleted_at` is still null (`:192-201`, `:252-273`). No deletion state or success audit is written. After successful delete, finalization reacquires the same hierarchy, requires exact claim ownership, writes `active=false`, `deleted_at`, `client_deleted_before_payment`, and `document.deleted` (`:203-218`). An obsolete worker cannot finalize or release a replacement claim.

### Coordination with adjacent flows

- Upload performs file validation/scan/store before locks, then locks `Application -> ApplicationRequirement -> existing Document versions`; it serializes version selection, deactivates prior active versions, creates the next monotonically increasing version, and resets the requirement to `PENDING` (`DocumentWorkflowService.php:34-93`).
- Review locks `Application -> ApplicationRequirement -> Document` and rejects inactive, deleted, or claimed documents (`:96-145`).
- Purge uses the same hierarchy and exact-claim ownership for documents (`ExpiredFilePurger.php:105-127`).
- Private access locks the same hierarchy and authorizes only unclaimed/undeleted documents before acquiring the stream (`app/Services/PrivateFileReader.php:18-37`; `DocumentPolicy.php:16-23`). A stream already admitted may finish after delete; a new request is denied.

### Concurrency and active-version invariant

`DocumentDestroyHardeningTest` deterministically covers normal delete, cross-owner/inactive denial, application stage becoming ineligible, review-first, destroy-first, throwing delete, purge claim ownership, and access-first/new-access denial (`tests/Feature/Documents/DocumentDestroyHardeningTest.php:27-181`).

`DocumentLockOrderConcurrencyTest::test_overlapping_client_destroy_and_upload_serialize_to_one_active_new_version()` uses `Concurrency::driver('process')`, explicit marker barriers, and the PostgreSQL-backed test database (`tests/Feature/Documents/DocumentLockOrderConcurrencyTest.php:113-180`). It asserts destroy and upload both complete without deadlock, the old row is inactive and deleted, exactly one new active row exists, version is 2, review is `PENDING`, and requirement status is `PENDING`. A second true process test covers the shared upload/review ordering.

The process test deliberately holds the application lock in an outer transaction to impose the ordering, so it is a lock-order/serialization proof rather than a literal reproduction of the service's between-transaction filesystem interval. The claim-gap interactions are covered by the deterministic service-level review/purge/access tests and the source's exact-claim rechecks. This limitation does not invalidate the P2 correction.

### Required P2-C answers

| Question | Answer |
|---|---|
| Can destroy use stale eligibility? | **No**; mutable eligibility is re-read and checked under the parent-first locks before the durable claim. |
| Can a cross-owner client reach the destructive workflow? | **No**; policy rejects it, and the service independently rechecks client role and locked application ownership. |
| Can review win and a stale destroy still delete? | **No**; review changes current eligibility/state under the same hierarchy and the later claim is rejected. |
| Can destroy win and stale review mutate the deleted document? | **No**; the live claim blocks review, and finalized inactive/deleted state also blocks it. |
| Can upload/destroy create duplicate active versions? | **No** in current locking/version logic and the true process test; exactly one monotonically newer active version remains. |
| Can purge/destroy steal each other's claims? | **No**; both use the same hierarchy and exact timestamp ownership. |
| Can private access open a newly claimed/deleted path? | **No**; new access serializes and policy rejects claimed/deleted rows. An already opened stream may finish as intended. |
| Is the former unlocked delete path completely gone? | **Yes**; the sole route delegates to the locked service workflow and no alternate controller delete path was found. |

**Source verdict: `VERIFIED_CORRECT`. Test verdict: `VERIFIED_CORRECT`. P2-C final verdict: `VERIFIED_CORRECT`.**

## Cross-check

### Independently reconstructed lock map

| Path | Locks held together, in order | External I/O boundary |
|---|---|---|
| Payment create intent | `Application -> latest PENDING Payment` | Xendit call after intent transaction; checkout persistence later locks only `Payment`. |
| Payment reconcile | Brief `Payment` re-read; later checkout persistence locks `Payment` | Provider call between the two short transactions. |
| Webhook | `WebhookEvent -> Application -> Payment` | No provider HTTP; notification transport is queued/after-commit. |
| Cancellation | `Application -> Payment rows (id order)` | No provider I/O under locks. |
| Document destroy | `Application -> ApplicationRequirement -> Document` in claim/release/finalize | Single-file delete between transactions. |
| Document upload | `Application -> ApplicationRequirement -> Document versions` | Validation, quarantine/private storage, and malware scan before row locks; cleanup after failure. |
| Document review | `Application -> ApplicationRequirement -> Document` | No filesystem/provider transport under locks. |
| File purge — document | `Application -> ApplicationRequirement -> Document` | Single-file delete between claim and finalize transactions. |
| File purge — result | `Application -> ResultDocument` | Single-file delete between claim and finalize transactions. |
| Private document access | `Application -> ApplicationRequirement -> Document` | Safe stream acquisition occurs under locks; response delivery occurs after commit. |
| Private result access | `Application -> ResultDocument` | Safe stream acquisition occurs under locks; response delivery occurs after commit. |
| Result verification/completion | `Application -> ResultDocument(s)` | No scanner/provider transport; queued notification transport occurs after commit. |
| Result upload | No explicit row lock in this path | Quarantine write, malware scan, and private write precede database persistence. |

No concrete opposing row-lock pair was found among the inspected corrected payment/document/purge/access/result/webhook/cancellation paths. The webhook's leading `WebhookEvent` lock is not opposed by a path that holds the corresponding payment/application row and then waits for the same event row.

### External I/O

Xendit HTTP is outside database row-lock transactions. Destroy and purge use short claim/finalization transactions with one filesystem delete between them. Document and result malware scanning/storage happen before their database write sections. No synchronous mail transport or malware scan was introduced under row locks. `PrivateFileReader` still acquires the filesystem stream during its locked response-setup transaction, while actual response streaming happens afterward; that accepted cleanup concern remains P3 and unchanged.

### State graphs and bounded scope

The application and payment enum transition matrices retain their declared lifecycles. `FAILED`, `EXPIRED`, `CANCELLED`, and `REFUNDED` remain terminal in `PaymentStatus`. The new recovery write bypasses `canTransitionTo()` directly; as explained above, its intended technical purpose is narrow, but its provenance proof is insufficient against a later same-status webhook.

The following accepted P3 topics were not changed or used to block the P2 verdicts:

- non-`PENDING` external-ID mismatch behavior;
- pruning test naming/reframe;
- the 15-minute lease/heartbeat design;
- `PrivateFileReader` response-setup cleanup;
- explicit presence auth/throttle test counts.

The following business items remain deferred without an invented rule:

- SEC-012 application draft concurrency/business semantics;
- SEC-022 production provider/webhook retention duration;
- post-retention semantics for a `COMPLETED` application's result.

## Targeted tests — serial

| Target | Command | Result |
|---|---|---|
| P2-A | `php artisan test --no-coverage tests/Feature/Payments/PaymentProviderOutcomeTest.php tests/Feature/Payments/PaymentDualWriteHardeningTest.php` | PASS — **19 tests, 89 assertions**, 19.97s. |
| P2-B | `php artisan test --no-coverage tests/Feature/Files/FilePurgeCoordinationTest.php` | PASS — **7 tests, 33 assertions**, 1.91s. |
| P2-C | `php artisan test --no-coverage tests/Feature/Documents/DocumentDestroyHardeningTest.php tests/Feature/Documents/DocumentLockOrderConcurrencyTest.php` | PASS — **10 tests, 50 assertions**, 4.66s. |

Passing P2-A tests do not negate the source finding because the exact same-status webhook interleaving is not represented.

## Final full verification

| Command | Result |
|---|---|
| `php artisan optimize:clear` | PASS — all reported cache groups cleared. |
| `php artisan view:cache` | PASS — Blade templates cached successfully. |
| `npm run build` | PASS — Vite 6.4.3, 57 modules transformed, built in 2.11s. |
| `php artisan test --no-coverage` | PASS — **389 tests, 2,456 assertions**, 0 failures, 84.47s. |
| `vendor/bin/pint --test` | PASS. |
| `git diff --check` | PASS. |
| `composer audit --locked` | PASS — no security vulnerability advisories. |
| `npm audit --omit=dev` | PASS — 0 vulnerabilities. |

The executable zero-failure requirement is met. It does not close the source-level P2-A regression that lacks a test.

## Final findings

| Priority | Type | Finding | Evidence/impact |
|---|---|---|---|
| P2 | `IMPLEMENTATION_DEFECT` | The `FAILED -> PENDING` checkout-success bypass can supersede a provider-authoritative failure confirmed by a same-status webhook. | Same-status webhook skips payment-payload persistence at `XenditWebhookController.php:158-168`; supersede consults only blank row fields plus any earlier local marker at `ApplicationWorkflowService.php:397-416`. |
| P2 | `TEST_GAP` | No deterministic test inserts `FAILED` webhook processing between the local definitive finalizer and delayed provider success. | Existing 19-test provider suite covers the two local races and normal webhook reconciliation, but not this three-event interleaving. |
| P2 | `DOCUMENTATION_MISMATCH` | The correction report states that webhook/provider-backed `FAILED` can never be superseded, but current source does not enforce that for the same-status webhook branch. | `security-hardening-bc-final-p2-corrections.md:47-49,159,167-168` conflicts with the source trace above. |

No P0 or P1 issue was identified within the constrained three-target audit. No new P2 defect was found in P2-B or P2-C. Deferred retention/draft questions remain `BUSINESS_DECISION`, not implementation findings in this pass.

## Final matrix

| P2 Target | Source Verdict | Test Verdict | New Regression | Final |
|---|---|---|---|---|
| Provider taxonomy | `NEW_REGRESSION_FOUND` | `PARTIALLY_CORRECT` | **Yes — provider-authoritative same-status `FAILED` webhook can be superseded by delayed success** | `NEW_REGRESSION_FOUND` |
| Purge exception recovery | `VERIFIED_CORRECT` | `VERIFIED_CORRECT` | No | `VERIFIED_CORRECT` |
| Client destroy TOCTOU | `VERIFIED_CORRECT` | `VERIFIED_CORRECT` | No | `VERIFIED_CORRECT` |

## Final classification

**B/C FINAL P2 INDEPENDENT VERIFICATION COMPLETE — CORRECTION REQUIRED**

Stop here. No production fix, P3 cleanup, or additional hardening phase was performed.
