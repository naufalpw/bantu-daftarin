# B/C Final P2-A Correction — Provider-Authoritative Failed Webhook Provenance

Date: 2026-09-11

Scope: only the remaining P2-A sequence `local definitive failure -> same-status provider FAILED webhook -> delayed checkout success`. The final independent verification is the authority for this correction. P2-B, P2-C, P3, and the deferred business-retention decisions are not changed here.

## CP0 — Baseline

The existing worktree was already dirty with the prior Hardening B/C/D and final P2 work. Those pre-existing changes and the untracked backup directory were preserved.

Executed before this correction:

| Command | Result |
| --- | --- |
| `git status --short` | Recorded; existing modified/untracked hardening files present. |
| `php artisan test --no-coverage` | PASS — 389 tests, 2,457 assertions, 0 failures, 58.32s. |
| `npm run build` | PASS — Vite 6.4.3, 57 modules, 2.55s. |
| `vendor\bin\pint --test` | PASS. |
| `git diff --check` | PASS. |
| `composer audit --locked` | PASS — no known security vulnerability advisories. |
| `npm audit --omit=dev` | PASS — 0 vulnerabilities. |

No baseline migration or dependency change was required.

## CP1 — Provenance analysis

The source-level defect matched the independent verification:

1. A durable `PENDING` payment and stable reference were created before the provider call.
2. A definitive local checkout rejection caused `finalizeDefinitiveCheckoutFailure()` to commit `PENDING -> FAILED` and record `payment.checkout_definitive_failed`.
3. A later authenticated provider `FAILED` webhook for the same reference passed the existing payment/application, amount, currency, provider identity, payer, and channel checks.
4. Because its target and current status were both `FAILED`, the webhook event became `PROCESSED`, but the old same-status branch did not persist the computed minimized payment payload.
5. A delayed success then entered `persistPaymentCheckout()`. The row still appeared local-only: no external ID, no checkout URL, no provider payload, same reference, and a local definitive-failure audit marker.
6. `canSupersedeLocalDefinitiveFailure()` therefore allowed the narrowly scoped technical recovery to set the row back to `PENDING`.

The status comparison was incorrectly being used both to decide whether a business transition existed and whether provider evidence should be stored. A same-status provider webhook is not a state transition, but it is authoritative provenance.

## CP2 — Provider-authoritative webhook persistence

`XenditWebhookController` now separates those responsibilities:

- A valid webhook that changes status still follows the existing transition rules and stores only `PaymentPayloadMinimizer::paymentFromWebhook()` output.
- A valid webhook with no mapped target still stores the minimized provider evidence as before.
- A valid webhook whose mapped target already equals the payment status now also stores that same minimized evidence, without synthesizing a status transition.
- A webhook targeting a different but disallowed state still follows `payment.webhook_ignored`; it is not allowed to overwrite the payment through the same-status evidence path.

Evidence persistence remains after all existing webhook checks and the transaction locks. Invalid callback tokens never enter processing. Invalid reference/application relationships, amount, currency, existing provider identity, payer identity, or channel cause permanent rejection before the payment save. The identity-conflict regression proves a mismatched provider ID receives HTTP 422, leaves the existing identity and payload unchanged, and records the event as `REJECTED`.

The webhook ledger continues to store the existing minimized event payload, not the raw request. A duplicate already-processed event returns idempotently from the ledger and does not replace its original minimized payload.

## CP3 — Supersede guard

No generic `FAILED -> PENDING` state-graph edge was added. `PaymentStatus::canTransitionTo()` is unchanged and terminal `FAILED` still has no normal outgoing transition.

The retained bypass is a technical reconciliation for one precise race: a delayed successful response for the same logical checkout may supersede a proven local definitive checkout-attempt failure. `canSupersedeLocalDefinitiveFailure()` requires all of the following:

- same locked payment and durable reference;
- matching provider reference when the success payload supplies one;
- `payment.checkout_definitive_failed` exists for that exact payment;
- no stored external provider identity;
- no stored checkout URL; and
- no stored provider payload/evidence.

The same-status webhook correction is sufficient as the narrow race-safe defense. Both webhook handling and delayed-success persistence lock the same `Payment` row. If the webhook commits first, its non-empty minimized provider payload makes the local-only guard return false. If delayed success commits first, the subsequently locked webhook observes `PENDING` and applies the normal provider-authoritative `PENDING -> FAILED` transition. No schema, new state, or event-ledger query is needed.

## CP4 — Deterministic three-event regression

`PaymentDualWriteHardeningTest::test_provider_failed_webhook_between_local_failure_and_delayed_success_remains_authoritative()` uses three real process workers and explicit filesystem barriers:

1. Checkout A reaches the synthetic provider success but publishes `success.ready` and blocks.
2. Checkout B publishes `failure.ready`, receives a definitive rejection, commits local `FAILED`, and only then publishes `failure.finished` with the durable reference.
3. The webhook worker waits for `failure.finished`, processes authenticated `FAILED` twice using the same event ID, verifies the event/evidence, and publishes `webhook.finished`.
4. Checkout A waits for `webhook.finished`, publishes `success.released`, returns success, and attempts local persistence only after authoritative webhook processing.

Assertions prove one stable reference and one payment row throughout; the local definitive marker exists; the webhook event is `PROCESSED`; minimized provider ID/reference/status evidence is present; both duplicate deliveries return HTTP 200; delayed success was released after webhook completion; final payment remains `FAILED`; provider identity and checkout URL from the delayed success are not written; application remains `AWAITING_PAYMENT`; no application history or notification is produced; and there is no duplicate state-change, supersede, or checkout-created audit.

The adjacent existing regression still proves the intended local-only case: without any webhook/provider evidence, delayed success for the same reference can reconcile the local definitive failure to a provider-backed `PENDING` checkout.

Targeted executable results:

- `PaymentDualWriteHardeningTest`: PASS — 12 tests, 86 assertions.
- `XenditWebhookTest`: PASS — 14 tests, 76 assertions.
- Payment/webhook/cancellation matrix: PASS — 46 tests, 325 assertions.

The payment matrix covers definitive no-call, validation, and authentication/configuration rejections; timeout, transport, rate-limit, 5xx, and malformed-success ambiguity; success versus ambiguous and local definitive failure; webhook `PENDING -> FAILED`; late failure versus `PAID`; cancellation; and `payments:reconcile`. No real Xendit call was made.

## CP5 — Cross-regression

A single serial targeted command covered the named cross-hardening areas and passed with 159 tests, 1,086 assertions, and 0 failures:

- Hardening A: SEC-005, SEC-006, SEC-020, SEC-023.
- Hardening B: SEC-010, SEC-011, SEC-013, SEC-021.
- Hardening C: SEC-024, SEC-003, SEC-015, SEC-022.
- P2-B: purge claim failure recovery, including throwing-delete continuation.
- P2-C: client document destroy and parent-first concurrency lock ordering.
- Hardening D: production configuration, malware scanner guard, CSP, mailer guard, and security headers.

SEC-020-specific tests confirm callback authentication, original minimized ledger payload preservation, `RECEIVED` resumption, transient retry, permanent rejection, sequential duplicate idempotency, and concurrent duplicate delivery remain intact.

This correction did not edit P2-B or P2-C implementation/tests. SEC-012, the SEC-022 retention duration, and post-retention `COMPLETED` result semantics remain deferred. The listed P3 issues remain untouched.

## CP6 — Final verification

Final verification is recorded after the correction checkpoint and source changes are complete:

| Command | Result |
| --- | --- |
| `php artisan optimize:clear` | PASS — config, cache, compiled, events, routes, and views cleared. |
| `php artisan view:cache` | PASS — Blade templates cached successfully. |
| `npm run build` | PASS — Vite 6.4.3, 57 modules, 3.62s. |
| `php artisan test --no-coverage` | PASS — 391 tests, 2,489 assertions, 0 failures, 81.83s. |
| `vendor\bin\pint --test` | PASS. |
| `git diff --check` | PASS. |
| `composer audit --locked` | PASS — no known security vulnerability advisories. |
| `npm audit --omit=dev` | PASS — 0 vulnerabilities. |

No migration and no dependency were added.

Final classification: **B/C FINAL P2-A CORRECTION COMPLETE**.
