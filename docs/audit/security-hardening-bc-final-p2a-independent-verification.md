# B/C Final P2-A Independent Verification

Date: 2026-09-11

Mode: independent audit only; no fix or refactor

Scope: only provider-authoritative `FAILED` provenance and the exact sequence `local definitive failure -> same-status provider FAILED webhook -> delayed checkout success`. The correction report was treated as a claim. Current source and executable behavior were evaluated independently.

Final classification: **B/C FINAL P2-A INDEPENDENT VERIFICATION COMPLETE — VERIFIED**

## Executive result

The remaining P2-A defect is corrected. A valid authenticated provider `FAILED` webhook received while the local payment is already `FAILED` now persists a non-empty minimized provider-evidence payload. The delayed-success persistence path locks the same payment row, sees that evidence, fails the local-only provenance guard, and returns the authoritative `FAILED` row without writing provider checkout identity, URL, payload, or `PENDING` status.

The same-status webhook does not create a `FAILED -> FAILED` business transition. Duplicate delivery of the same event ID is terminally idempotent after the first `PROCESSED` result. The deterministic three-process regression genuinely orders local failure, same-status webhook processing, then delayed success through explicit markers rather than elapsed-time assumptions.

No new P1 or P2 regression was identified in the constrained audit. The previously documented non-`PENDING`/provider-payload-only external-ID mismatch caveat remains P3 and was not reopened or used to broaden this verdict.

Only this report was created. Application source, tests, prior reports, P2-B, P2-C, P3 findings, retention policy, migrations, and dependencies were not modified by this verification.

## Baseline

The worktree was already dirty with the accumulated Hardening B/C/D and final P2 changes plus an untracked backup directory. That state was inventoried and preserved.

| Command | Independent result |
| --- | --- |
| `git status --short` | Completed; pre-existing modified and untracked files present. |
| `php artisan test --no-coverage` | PASS: **391 tests, 2,490 assertions**, 0 failures, 60.32s. |
| `npm run build` | PASS: Vite 6.4.3, 57 modules, 2.85s. |
| `vendor\bin\pint --test` | PASS. |
| `git diff --check` | PASS. |
| `composer audit --locked` | PASS: no known security vulnerability advisories. |
| `npm audit --omit=dev` | PASS: 0 vulnerabilities. |

The observed assertion count differs by one from the correction report while the test count and zero-failure result match. The final run below produced another assertion-count variation. This is consistent with the already documented process-test counting variation; no behavioral test disappeared or failed.

## Same-status provider evidence

### Authentication and validation order

`XenditWebhookController::handle()` verifies the configured callback token with `hash_equals` before it reads or persists the request payload (`app/Http/Controllers/Webhooks/XenditWebhookController.php:37-43`). Only then is the event payload reduced through `PaymentPayloadMinimizer::webhookEvent()` and inserted into the ledger (`:45-58`).

Inside the ledger transaction, processing normalizes the stored payload, resolves the durable payment reference, and locks `Application -> Payment`. It checks amount and currency, an already persisted external provider identity, payer identity, and channel before computing payment evidence (`XenditWebhookController.php:119-157`). Invalid semantic input is converted to a permanent `REJECTED` ledger result and cannot reach the payment save (`:190-201`).

### Same-status branch

The controller computes `PaymentPayloadMinimizer::paymentFromWebhook()` after validation (`XenditWebhookController.php:156-157`). Its branch behavior is now separated correctly:

- a different allowed target performs the existing business transition and saves minimized evidence (`:158-163`);
- a different disallowed target is audited as ignored (`:164-165`);
- equal target/current status, or no mapped target, saves only `provider_payload` (`:166-168`).

For the audited legacy `FAILED` payload, minimized evidence contains provider `id`, durable `reference_id`, provider status, amount, currency, and channel when supplied. Unknown fields and raw customer data do not cross the payment persistence boundary. The webhook ledger itself also contains the separately allowlisted event payload rather than the raw request (`app/Services/PaymentPayloadMinimizer.php:34-76,78-98`).

The same-status save does not write `status`, `failed_at`, an application status, or a history row. Processing then marks the event `PROCESSED` and records the one intended `payment.webhook_processed` audit (`XenditWebhookController.php:181-188`). Therefore no synthetic `FAILED -> FAILED` transition exists.

### Provider identity conflict

When `Payment.external_id` already contains provider identity, any non-empty incoming identity set must contain that exact value or the webhook is rejected before evidence persistence (`XenditWebhookController.php:146-148`). The focused regression seeds an existing `FAILED` payment with external identity and provider payload, sends a different provider ID, and proves HTTP 422, unchanged `FAILED` status, unchanged external identity, unchanged provider payload, a `REJECTED` event, and no audit side effect (`tests/Feature/Webhooks/XenditWebhookTest.php:258-298`). Matching identity may proceed and enrich the minimized evidence.

In the exact P2-A race, the first same-status webhook stores its provider ID inside the minimized payment evidence while leaving the `external_id` column null. This is sufficient and deliberate for the P2-A provenance guard; the regression explicitly proves that the delayed checkout identity is not copied over. The previously accepted different-event/non-`PENDING` identity caveat is P3 and remains outside this audit.

## Supersede guard and race proof

`persistPaymentCheckout()` contains the only source write found that can directly restore a `FAILED` payment to `PENDING` (`app/Services/ApplicationWorkflowService.php:315-325`). It is not a normal state transition. It is reached only when status is exactly `FAILED` and `canSupersedeLocalDefinitiveFailure()` succeeds.

The guard requires:

- empty stored external identity;
- empty checkout URL;
- empty provider payload/evidence;
- the locked row and in-flight payment object to have the same durable reference;
- any provider-returned reference to match that same durable reference; and
- an exact-payment `payment.checkout_definitive_failed` audit marker.

Evidence: `ApplicationWorkflowService.php:398-416`. The local definitive finalizer itself also locks and re-reads the exact payment, requires `PENDING`, the same reference, and no provider identity/URL/payload before writing `FAILED` plus the marker and `PENDING -> FAILED` audit (`:349-377`). Ambiguous outcomes instead retain `PENDING` and write only the recoverable-attempt audit (`:379-395`).

The correction is race-safe without a schema change. Both webhook evidence persistence and delayed checkout persistence lock the same payment row. If the webhook commits first, `provider_payload` is non-empty and the guard returns false. If checkout success commits first, the later webhook observes `PENDING` and performs the normal provider-authoritative `PENDING -> FAILED` transition. The required regression deliberately tests the former ordering.

A scoped search of current application source found no other `FAILED -> PENDING` write. Initial payment creation writes `PENDING`, but that is not resurrection. `reconcilePayment()` returns early for any non-`PENDING` payment and cannot invoke the bypass for a terminal row.

## Deterministic three-event regression

`PaymentDualWriteHardeningTest::test_provider_failed_webhook_between_local_failure_and_delayed_success_remains_authoritative()` uses three process workers (`tests/Feature/Payments/PaymentDualWriteHardeningTest.php:214-339`):

1. Checkout A and checkout B both publish reference-bearing ready markers.
2. Checkout B throws a definitive exception. `createPayment()` runs and commits its finalizer before the catch writes `failure.finished` with the durable reference.
3. The webhook worker waits for `failure.finished`, so the payment is already locally `FAILED`. It submits a valid authenticated `FAILED` webhook twice under one event ID, verifies `PROCESSED` status and provider evidence, then writes `webhook.finished`.
4. The synthetic success gateway waits for `webhook.finished`, writes `success.released`, returns the invoice, and only then allows checkout A to enter local persistence (`tests/Support/CoordinatedPaymentGateway.php:19-47`).

The short `usleep` calls only poll for explicit marker existence and do not determine ordering. Marker publication determines the sequence and each wait has a bounded timeout.

Assertions establish the same reference in both checkout workers, the webhook worker, and all markers; one payment row; one local definitive marker; one `PROCESSED` webhook event; minimized provider ID/reference/status; two HTTP 200 duplicate responses; success release after webhook completion; final `FAILED`; no delayed-success external identity or checkout URL; no supersede or checkout-created audit; one state-change audit total (the original local `PENDING -> FAILED`); application still `AWAITING_PAYMENT`; zero application histories; and zero notifications (`PaymentDualWriteHardeningTest.php:303-339`).

The final parent assertions do not re-read every provider-payload key after success release, but source control flow closes that gap: the non-empty payload makes the guard false and the non-`PENDING` branch returns the locked row before any invoice field is written (`ApplicationWorkflowService.php:318-347`). This is not a security-relevant test omission.

## Control case

`test_concurrent_success_supersedes_an_earlier_definitive_local_failure_without_hybrid_state()` remains present and passes (`PaymentDualWriteHardeningTest.php:162-212`). It coordinates both provider attempts, waits for the local definitive finalizer to finish, and then releases success without inserting webhook evidence. The same reference is retained, one payment row remains, final status is provider-backed `PENDING`, `failed_at` is cleared, and exactly one technical supersede audit exists.

This confirms that the fix did not disable the intentionally bounded recovery path. The direct write bypasses `PaymentStatus::canTransitionTo()` only for the proven local-only failure of the same checkout/reference; it is not represented or described as a normal business transition.

## Duplicate webhook and SEC-020

The three-event regression delivers the same `FAILED` event twice. The event ledger unique key is `(provider, event_id)`. After the first transaction marks it `PROCESSED`, the duplicate returns `already_processed` before payment/application processing (`XenditWebhookController.php:96-104`). The test proves one ledger row, one webhook-processed audit, one total state-change audit, no application history, no notification, and unchanged final provider-authoritative `FAILED` state.

Existing focused tests also continue to prove:

- a `RECEIVED` event resumes from the original minimized ledger payload;
- legacy retryable `REJECTED` is reconciled once;
- transient internal failure remains `RECEIVED`, returns 500, and succeeds on retry;
- permanent validation rejection remains terminal and cannot be replaced with a new payload;
- sequential and concurrent duplicates create one logical transition.

SEC-020 retry and idempotency behavior is therefore unchanged by the P2-A correction.

## Payment outcome taxonomy and state graph

The exception boundary remains `PaymentGatewayDefinitiveException` and `PaymentGatewayAmbiguousException`, both extending `PaymentGatewayException`. Missing provider configuration and allowlisted, status-bounded validation/authentication rejections are definitive. Connection loss, unknown responses, malformed success, rate limit, and 5xx remain ambiguous. `createPayment()` and `reconcilePayment()` continue to apply the same split.

`PaymentStatus::canTransitionTo()` is unchanged. `PENDING` may transition to `PAID`, `FAILED`, `EXPIRED`, or `CANCELLED`; `FAILED` remains terminal with no normal outgoing transition (`app/Enums/PaymentStatus.php:16-24`). `git diff` showed no change to the enum.

## Focused cross-regression

The payment provider taxonomy, payment dual-write, Xendit webhook, concurrent webhook duplicate, cancellation, reconciliation, and SEC-020 retry suites were executed serially in one command:

`php artisan test --no-coverage tests\Feature\Payments\PaymentProviderOutcomeTest.php tests\Feature\Payments\PaymentDualWriteHardeningTest.php tests\Feature\Webhooks\XenditWebhookTest.php tests\Feature\Webhooks\XenditWebhookConcurrencyTest.php tests\Feature\Applications\ApplicationCancellationTest.php`

Result: PASS, **46 tests, 325 assertions**, 0 failures, 14.86s. No real Xendit request was sent.

P2-B, P2-C, P3 findings, and retention policy were not re-audited or modified.

## Final full verification

| Command | Independent result |
| --- | --- |
| `php artisan optimize:clear` | PASS: config, cache, compiled, events, routes, and views cleared. |
| `php artisan view:cache` | PASS: Blade templates cached successfully. |
| `npm run build` | PASS: Vite 6.4.3, 57 modules, 2.06s. |
| `php artisan test --no-coverage` | PASS: **391 tests, 2,491 assertions**, 0 failures, 73.22s. |
| `vendor\bin\pint --test` | PASS. |
| `git diff --check` | PASS. |
| `composer audit --locked` | PASS: no known security vulnerability advisories. |
| `npm audit --omit=dev` | PASS: 0 vulnerabilities. |

No migration, schema change, or dependency change was added. `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `database/migrations/`, and `PaymentStatus.php` have no current diff.

## Required answers

1. **Can provider/webhook-authoritative `FAILED` still be reopened to `PENDING`?** No. Same-status webhook evidence makes the local-only guard fail; the delayed success returns the locked `FAILED` row unchanged.
2. **Can purely local definitive `FAILED` still be reconciled by stronger delayed provider success?** Yes, only for the same payment/reference with the exact local marker and no external identity, checkout URL, or provider evidence.
3. **Does same-status `FAILED` webhook persist provider evidence?** Yes. It persists the minimized provider ID, reference, status, and allowed supporting fields in `provider_payload` after validation.
4. **Does it avoid `FAILED -> FAILED` business transitions?** Yes. Same-status processing writes only evidence; the regression has one state-change audit total from the earlier local `PENDING -> FAILED` finalizer.
5. **Can mismatched provider identity overwrite existing provider truth?** No within the required P2-A path and existing identity semantics: an existing `Payment.external_id` mismatch is rejected with status/payload unchanged, and a duplicate event ID cannot replace its ledger/evidence. The known different-event/provider-payload-only non-`PENDING` caveat remains P3 and was not reopened.
6. **Is duplicate `FAILED` webhook idempotent?** Yes. It produces one event, one intended processing audit, no duplicate transition/history/notification, and no evidence corruption.
7. **Is SEC-020 retry behavior unchanged?** Yes. `RECEIVED`, transient, legacy retry, permanent rejection, and duplicate behavior all pass.
8. **Is the `PaymentStatus` graph unchanged?** Yes. `FAILED` remains terminal in the business graph.
9. **Any migration/dependency added?** No.
10. **Any new P2/P1 regression found?** No within this constrained verification.

## Final classification

**B/C FINAL P2-A INDEPENDENT VERIFICATION COMPLETE — VERIFIED**

Stop here. No P3 fix, broader B/C audit, or additional hardening phase was performed.
