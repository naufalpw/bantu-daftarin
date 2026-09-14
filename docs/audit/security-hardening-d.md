# Security Hardening D — Production & Operational Acceptance

Date: 2026-09-09

Scope: SEC-016, SEC-027, SEC-028, SEC-029, SEC-032

Audit source: `docs/audit/security-audit.md`

Previous hardening records: `docs/audit/security-hardening-a.md`, `docs/audit/security-hardening-b.md`, `docs/audit/security-hardening-c.md`

Production source modified: YES

Domain logic modified: NO

Migration: NO expected

Dependencies: NO expected

## Progress

| Checkpoint | Status | Findings addressed | Runtime acceptance | Tests |
|---|---|---:|---|---|
| CP0 Baseline and scope | COMPLETE | 5 targeted | NOT_TESTED | Baseline: 343 passed, 1 failed; 2,120 assertions; build passed |
| CP1 Malware configuration guard | COMPLETE | SEC-016 | NOT_TESTED in production | 19 tests / 49 assertions passed with document workflow |
| CP2 Malware runtime health | COMPLETE | SEC-016 | NOT_AVAILABLE locally | Bounded checker tests passed; local testing-driver check passed |
| CP3 Effective production configuration | COMPLETE | SEC-027 | Production NOT_TESTED | 6 tests / 18 assertions passed |
| CP4 Security email/log exposure | COMPLETE | SEC-028 | Production delivery/log ACL NOT_TESTED | 9 focused tests / 135 assertions passed |
| CP5 Browser headers | COMPLETE | SEC-029 partial | Browser compatibility pending | 54 targeted tests / 486 assertions; build passed |
| CP6 Operational controls | COMPLETE | SEC-032 | Production NOT_TESTED | Command/schedule discovery passed |
| CP7 Acceptance matrix | COMPLETE | SEC-016/027/028/029/032 | Production acceptance pending | Repository evidence classified below |
| CP8 Final verification | COMPLETE | SEC-016/027/028/029/032 | Production acceptance pending | 357 passed, 1 pre-existing failure; 2,285 assertions |

## CP0 — Baseline and scope

Status: COMPLETE

### Existing worktree

The worktree already contained Security Hardening B/C changes before Hardening D. They were preserved. The initial Git index was a zero-byte corrupt file; it was moved to `.git/index.corrupt-20260909-015909` and the index was rebuilt from `HEAD` with `git read-tree HEAD`. No working-tree content was reset or reverted.

Pre-existing modified or untracked paths:

- `app/Http/Controllers/Client/ApplicationController.php`
- `app/Http/Controllers/Webhooks/XenditWebhookController.php`
- `app/Livewire/ChatThread.php`
- `app/Models/Application.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/AdminWorkflowService.php`
- `app/Services/ApplicationWorkflowService.php`
- `app/Services/AuthOtpService.php`
- `app/Services/XenditPaymentProvider.php`
- `config/filesystems.php`
- `routes/web.php`
- `tests/Feature/Applications/ApplicationWorkflowTest.php`
- `tests/Feature/Authentication/OtpTest.php`
- Hardening B/C commands, tests, and checkpoint documents shown by the CP0 `git status --short` output.

### Baseline commands

- `php artisan test --no-coverage`: **343 passed, 1 failed, 2,120 assertions**. The pre-existing failure was `Tests\Feature\Admin\AdminOperationsUiTest::admin chat uses contextual headers without fake presence`: expected `Sedang tidak aktif`, rendered `Terakhir aktif hari ini`. Chat/presence behavior is outside Hardening D and was not changed to mask the baseline failure.
- `npm run build`: **PASS** (Vite 6.4.3, 57 modules transformed).
- `npm audit --omit=dev`: **PASS**, 0 vulnerabilities.
- `composer audit --locked`: **PASS**, no advisories.

### Target findings

| Finding | Severity | Hardening D target | Initial runtime status |
|---|---|---|---|
| SEC-016 | MEDIUM | Reject the testing scanner in production; add a bounded CLI health check | `clamdscan` NOT_AVAILABLE on this host |
| SEC-027 | HIGH | Redacted effective-production configuration check with non-zero exit on unsafe production settings | Production environment NOT_TESTED |
| SEC-028 | HIGH | Reject log/array mail delivery in production and document synthetic log acceptance | Production mail delivery NOT_TESTED |
| SEC-029 | Defense in depth | Inventory inline execution, tighten CSP where compatible, assign HSTS to an authoritative TLS layer | Production browser/TLS NOT_TESTED |
| SEC-032 | MEDIUM | Executable operational acceptance procedure and honest evidence matrix | Production operations NOT_TESTED |

### Source-grounded initial observations

- The upload pipeline already quarantines content and fails closed when the scanner reports unavailable or infected. This behavior is preserved.
- `.env.example` defaults `MALWARE_SCAN_DRIVER=clamav`, but the effective local environment intentionally uses `testing`; `clamdscan` is not installed on this Windows host.
- Effective local configuration is development-only: `APP_ENV=local`, debug enabled, HTTP URL and non-secure session cookie. Those values are not evidence of a production misconfiguration.
- The effective local mailer is SMTP, but `.env.example` remains intentionally development-oriented with the log mailer. Production must reject `log` and `array` mail transports.
- Private and quarantine disks are non-serving and outside `public`; a duplicate-key artifact in the pre-existing `local` disk configuration resolves safely at runtime but should be normalized while validating the storage boundary.
- Active executable inline scripts were found in OTP, application creation, personal registration, and the application workspace. Inline DOM event handlers were found in an admin archive action and a registration upload control. Inline styles and dynamic style attributes also exist, so `style-src 'unsafe-inline'` cannot yet be removed safely.
- HSTS ownership cannot be proven from this repository because the production TLS terminator/reverse proxy is unavailable. It must not be enabled for local HTTP.

### Scope protections

- SEC-012 remains deferred.
- SEC-022 provider-payload retention duration remains `RETENTION PERIOD DEFERRED — BUSINESS/OPERATIONS CONFIRMATION REQUIRED`; no automated payment-payload pruning schedule is introduced.
- Authentication, payment authority, chat, notification delivery, domain transitions, private-file authorization, and PostgreSQL schema are outside this patch.

## CP1 — Malware configuration guard

Status: COMPLETE

- `AppServiceProvider` now rejects `MALWARE_SCAN_DRIVER=testing` when the effective application environment is `production`.
- The driver selection is an explicit allowlist: `clamav` and `testing`. Unknown values fail closed instead of silently selecting another implementation.
- Non-production automated tests may still resolve `TestingMalwareScanner`; no browser/manual-development workflow is removed.
- The existing upload pipeline behavior is unchanged: unavailable and infected scan results are rejected, quarantine content is deleted, and only a clean result is promoted to private storage.
- Targeted verification: `MalwareScannerHardeningTest` plus `DocumentWorkflowTest` — **19 passed, 49 assertions**.

## CP2 — Malware runtime health

Status: COMPLETE

### Implementation

- Added `php artisan security:check-malware`, a bounded CLI-only health check. It is not executed on ordinary HTTP requests.
- For ClamAV it checks command responsiveness, signature timestamp age, a temporary clean sample, and the standard synthetic antivirus test sample. Temporary samples are removed in all completion paths.
- The maximum accepted signature age is explicit through `CLAMAV_SIGNATURE_MAX_AGE_HOURS` (default 48 hours).
- Results use `PASS`, `FAIL`, `NOT_AVAILABLE`, and `NOT_VERIFIABLE`. The command exits non-zero unless the overall result is `PASS`.
- Output contains check names and generic diagnostics only; it does not print environment secrets, scanned application paths, or file contents.

### Runtime evidence

- Local effective driver: `testing`, allowed because `APP_ENV=local`; the command returned `PASS` for this non-production boundary.
- `clamdscan` binary on the audit host: **NOT_AVAILABLE** (`Get-Command clamdscan` returned no command).
- Local real-engine clean/EICAR/signature acceptance: **NOT_AVAILABLE**.
- Staging/production ClamAV daemon, signature freshness, and clean/EICAR execution: **NOT_TESTED**.

This checkpoint completes the repository guard and health-check implementation. It does not convert missing production runtime evidence into a production PASS.

## CP3 — Effective production configuration

Status: COMPLETE

### Implementation

- Added `php artisan security:check-production`. The command reads Laravel's effective configuration, emits only redacted presence/status information, and exits non-zero when any required production control is `FAIL`.
- It validates effective environment, debug, HTTPS URL, APP key presence, server-side sessions, Secure/HttpOnly/SameSite cookie attributes, mail transport and SMTP credential presence, asynchronous queue use, private/quarantine storage, ClamAV selection, signature-age configuration, document retention presence, Xendit driver/secret/callback-token presence, PostgreSQL selection, and non-debug active log channels.
- Nested failover/round-robin mailers are traversed so a hidden `log` or `array` member fails acceptance.
- Active stacked log channels are traversed so a nested debug-level channel fails acceptance.
- TLS termination and HSTS remain `NOT_VERIFIABLE` in command output because they require deployment response evidence.
- `.env.example` remains usable for local development but now labels the production environment, secure-cookie, and log-mailer boundaries explicitly.
- The duplicate-key artifact in the pre-existing `local` filesystem entry was removed. Its effective root remains `storage/app/local`, `serve=false`; private/quarantine roots and behavior are unchanged.

### Verification

- Safe synthetic production configuration: no `FAIL` results; TLS/HSTS correctly remain `NOT_VERIFIABLE`.
- Synthetic production with debug enabled, log mailer, and testing scanner: command exits non-zero.
- Missing APP key/Xendit credentials and unsafe storage: fail closed.
- Command-output test confirms configured APP key, SMTP password, Xendit secret, and callback token values are never printed.
- Effective local command run: **10 failures**, as expected for a development environment; output printed no secret values. This is not a production assessment.
- Targeted verification: `ProductionSecurityCheckTest` — **6 passed, 18 assertions**.

## CP4 — Security email and log exposure

Status: COMPLETE

- Production acceptance rejects direct `log` and `array` mail transports and also rejects an unsafe transport nested inside a failover/round-robin configuration.
- The application source logging inventory found structured event/error metadata only. It did not find application-owned logger calls that explicitly include OTP values, password-reset tokens/URLs, verification URLs, rendered mail bodies, Xendit credentials, or document content.
- This source result does not make the Laravel log mail transport safe: that transport intentionally renders complete messages into logs. It remains allowed only as an explicitly documented local-development option and is rejected by the production checker.
- Synthetic tests confirm queued password-reset state does not expose its plaintext token property and the production-check output does not expose configured secret values.
- Transactional email HTML/text, subject/preheader, and after-commit behavior were regression-tested and not modified.
- Focused verification: production mail checks plus authentication/notification regressions — **9 passed, 135 assertions**.

### Production acceptance still required

- Confirm the effective production mailer is an authenticated TLS transport.
- Send synthetic OTP/reset/verification fixtures in an isolated staging environment.
- Search the application's retained logs for those exact synthetic fixtures and record a negative result without recording the fixtures in this document.
- Verify log ACL, rotation, retention, redaction, and centralized-log access. Current status: **NOT_TESTED**.

## CP5 — Browser headers and CSP

Status: COMPLETE

Finding status: **PARTIALLY_RESOLVED — PRODUCTION BROWSER/TLS ACCEPTANCE PENDING**

### Inline-execution inventory and correction

The source inventory found executable inline scripts in:

- `resources/views/auth/otp.blade.php`
- `resources/views/client/applications/create.blade.php`
- `resources/views/client/applications/show.blade.php`
- `resources/views/client/registration/personal.blade.php`

It also found inline DOM event handlers in:

- `resources/views/admin/applications/show.blade.php`
- `resources/views/components/registration-document-card.blade.php`

The existing behavior was moved into the Vite-managed `resources/js/app.js` bundle and connected through existing/new data attributes. The inert testimonial JSON script element was changed to a `template` element. A source regression test now rejects executable inline script elements and common inline DOM event attributes in Blade views.

### Effective policy

- `script-src` is now exactly `'self'`; `script-src 'unsafe-inline'` was removed.
- `style-src 'unsafe-inline'` remains intentionally because active dynamic inline styles and Livewire style output still require compatibility work. No dishonest claim is made that CSP is nonce/hash-complete.
- Existing `default-src`, same-origin images, `frame-ancestors 'none'`, `object-src 'none'`, `base-uri 'self'`, and `form-action 'self'` restrictions remain.
- `nosniff`, `DENY`, strict-origin referrer policy, and permissions policy remain.
- Exact policy/header values and absence of local HSTS are covered by tests.

### HSTS ownership

HSTS is assigned to the authoritative production TLS terminator/reverse proxy, not emitted by the Laravel application in this phase. This avoids applying HSTS to local HTTP and avoids duplicate/conflicting policies. `includeSubDomains` and `preload` are not approved without verified domain/TLS coverage.

Production acceptance must prove the TLS layer returns the approved HSTS header over HTTPS and redirects HTTP to HTTPS. Current status: **NOT_TESTED**.

### Verification

- `npm run build`: PASS.
- Security/header plus OTP, application creation/cancellation, personal/business workspace, and admin operations regressions: **54 passed, 486 assertions**.
- The baseline chat/presence test that failed during the full-suite baseline passed in this isolated rerun; no chat/presence source was changed by Hardening D.
- Browser console/network compatibility for the bundled OTP countdown, business-type field, cancellation dialog, file autosubmit, camera flows, Livewire, and Alpine: **NOT_TESTED at this checkpoint**.

## CP6 — Operational controls

Status: COMPLETE

`docs/08-operations/runbook.md` is now an executable production acceptance procedure covering:

- deployment ordering and redacted configuration gates;
- HTTPS/proxy/CSP/HSTS verification;
- ClamAV engine, signature, clean-sample, and synthetic-detection acceptance;
- managed queue worker lifecycle, lag/failure alerts, and retry evidence;
- scheduler freshness and document-purge dry-run evidence;
- private/quarantine ownership, permissions, non-serving boundary, and disk monitoring;
- least-privilege PostgreSQL role/network/TLS checks;
- encrypted backup handling and an isolated restore drill;
- application/centralized-log ACL, rotation, redaction, and synthetic-secret search;
- alert delivery tests for authentication, webhook, queue, scheduler, scanner, storage, database, and 5xx events;
- production mail delivery/DNS/log non-disclosure acceptance.

Command discovery confirms both `security:check-production` and `security:check-malware` are registered. `schedule:list` confirms the existing daily `files:purge` schedule. No new scheduler entry was added.

The payment payload pruning guard remains intact: invoking `payments:prune-payloads` without `--days` stops before data access and reports that no approved retention policy exists. No automatic provider-payload pruning schedule was introduced.

## CP7 — Production acceptance matrix

Status: COMPLETE

Status vocabulary: `PASS`, `FAIL`, `NOT_TESTED`, `NOT_AVAILABLE`, `NOT_VERIFIABLE`, `DEFERRED`. A repository test or documented command is not promoted to a production `PASS`.

| Control | Repository evidence | Staging | Production | Evidence | Status |
|---|---|---|---|---|---|
| `APP_ENV=production` | Effective-config checker implemented and tested | NOT_TESTED | NOT_TESTED | Redacted checker output required | NOT_TESTED |
| `APP_DEBUG=false` | Unsafe synthetic production config exits non-zero | NOT_TESTED | NOT_TESTED | Redacted checker output required | NOT_TESTED |
| HTTPS | URL check and proxy/TLS procedure documented | NOT_TESTED | NOT_TESTED | External redirect, certificate, no-loop, forwarded-proto evidence required | NOT_TESTED |
| Secure cookie | Effective-config check tested | NOT_TESTED | NOT_TESTED | Actual HTTPS `Set-Cookie` required | NOT_TESTED |
| HttpOnly cookie | Effective-config check tested | NOT_TESTED | NOT_TESTED | Actual HTTPS `Set-Cookie` required | NOT_TESTED |
| SameSite | Effective-config check accepts Lax/Strict | NOT_TESTED | NOT_TESTED | Actual HTTPS `Set-Cookie` required | NOT_TESTED |
| Non-log mailer | Log/array/nested-log guards tested | NOT_TESTED | NOT_TESTED | Redacted config, synthetic delivery, and negative retained-log search required | NOT_TESTED |
| ClamAV | Production testing/unknown drivers fail closed; bounded health command tested | NOT_TESTED | NOT_TESTED | Actual command, signature, clean, and EICAR PASS required | NOT_AVAILABLE locally |
| Queue worker | Async configuration guard implemented | NOT_TESTED | NOT_TESTED | Managed worker status, test job, lag/failure alert and retry required | NOT_TESTED |
| Scheduler | Daily `files:purge` visible in local `schedule:list` | NOT_TESTED | NOT_TESTED | OS scheduler freshness and observed execution required | NOT_TESTED |
| Private storage | Local effective roots distinct, non-serving, outside public | NOT_TESTED | NOT_TESTED | Host ownership/ACL/no-web-route and upload-flow evidence required | NOT_TESTED |
| PostgreSQL least privilege | Verification SQL documented; local/test driver is pgsql | NOT_TESTED | NOT_TESTED | Role flags and scoped grants required | NOT_TESTED |
| PostgreSQL network/TLS | Procedure documented | NOT_TESTED | NOT_TESTED | Firewall/allowlist and effective TLS evidence required | NOT_TESTED |
| Encrypted backup | Encryption/key-separation procedure documented | NOT_TESTED | NOT_TESTED | Encrypted artifact, ACL, retention, checksum evidence required | NOT_TESTED |
| Restore test | Isolated restore procedure documented | NOT_TESTED | NOT_TESTED | Completed restore drill and synthetic smoke test required | NOT_TESTED |
| Log safety | Structured application logger inventory and synthetic secret-state tests pass | NOT_TESTED | NOT_TESTED | ACL/rotation/retention plus negative synthetic-fixture search required | NOT_TESTED |
| Monitoring | Required signals and alert drill documented | NOT_TESTED | NOT_TESTED | Delivered synthetic alerts and acknowledgment record required | NOT_TESTED |
| CSP | Exact automated header test passes; script unsafe-inline removed; public browser smoke passed | NOT_TESTED | NOT_TESTED | Authenticated public/client/admin/Livewire console and network acceptance required | NOT_TESTED |
| HSTS | Assigned to authoritative external TLS layer | NOT_VERIFIABLE | NOT_VERIFIABLE | Actual HTTPS response with approved max-age required | NOT_VERIFIABLE |
| Document retention | Production upload already fails closed without a positive value | DEFERRED | DEFERRED | Approved business/legal duration and purge evidence required | DEFERRED |
| SEC-022 provider/webhook retention | Explicit-days, dry-run-default command; no schedule | DEFERRED | DEFERRED | Business/tax/legal approval required | DEFERRED |

### Acceptance conclusion

- Repository/code controls: implemented and testable.
- This local host: suitable only for development; real ClamAV is unavailable and production configuration is not loaded.
- Staging: no evidence supplied, therefore `NOT_TESTED`/`NOT_VERIFIABLE` as listed.
- Production: no evidence supplied, therefore `NOT_TESTED`/`NOT_VERIFIABLE` as listed.
- Production readiness cannot be classified as fully accepted from this checkout alone.

## CP8 — Final verification

Status: COMPLETE

### Finding status

| Finding | Repository hardening | Runtime acceptance | Final Hardening D status |
|---|---|---|---|
| SEC-016 | Production driver guard and bounded engine health check complete | Real ClamAV NOT_AVAILABLE locally; staging/production NOT_TESTED | CODE COMPLETE, PRODUCTION ACCEPTANCE PENDING |
| SEC-027 | Redacted effective-config gate complete | Staging/production NOT_TESTED | CODE COMPLETE, PRODUCTION ACCEPTANCE PENDING |
| SEC-028 | Log/array/nested-log production rejection and non-disclosure tests complete | Delivery, retained-log search, and log ACL NOT_TESTED | CODE COMPLETE, PRODUCTION ACCEPTANCE PENDING |
| SEC-029 | Executable inline scripts/handlers removed; `script-src 'self'`; exact headers tested | Public local browser smoke PASS; authenticated CSP and production TLS/HSTS NOT_TESTED | PARTIALLY RESOLVED, CSP/INFRASTRUCTURE ACCEPTANCE PENDING |
| SEC-032 | Executable operations runbook and evidence matrix complete | Worker/scheduler/storage/DB/backup/monitoring production evidence NOT_TESTED | PROCEDURE COMPLETE, PRODUCTION ACCEPTANCE PENDING |

**CODE SECURITY HARDENING:** COMPLETE for the approved Hardening D repository scope.

**STAGING SECURITY ACCEPTANCE:** NOT_TESTED.

**PRODUCTION SECURITY ACCEPTANCE:** PENDING.

### Final commands

- `php artisan optimize:clear`: PASS.
- `php artisan view:cache`: PASS.
- `npm run build`: PASS (Vite 6.4.3, 57 modules).
- `php artisan test --no-coverage`: **357 passed, 1 failed, 2,285 assertions**. The sole failure is the same out-of-scope presence-text assertion present at baseline: `AdminOperationsUiTest::admin chat uses contextual headers without fake presence`. It expected `Sedang tidak aktif` while the shared presence cache rendered a recent-activity label. The test passes in isolation (**1 passed, 14 assertions**). Hardening D did not change chat/presence behavior.
- Security/production/malware tests after the final scanner-health adjustment: **13 passed, 32 assertions**.
- `vendor/bin/pint --test`: PASS.
- `git diff --check`: PASS; Git emitted only a pre-existing CRLF normalization warning for `public/images/figma/phase3/chat/check-sent.svg`.
- `npm audit --omit=dev`: PASS, 0 vulnerabilities (rerun with advisory-network access after the sandboxed endpoint failed).
- `composer audit --locked`: PASS, no security advisories (rerun with Packagist access after the sandboxed endpoint failed).

### Browser acceptance performed

- Local landing page loaded under the tightened CSP.
- The Vite-bundled testimonial carousel advanced from item 1 to item 2, proving the external application bundle executed.
- Login and registration pages rendered normally.
- No browser console warning/error was observed on the landing, login, or registration page.

Not performed: authenticated OTP countdown, Personal/Business conditional fields, cancellation dialog, file/camera flows, admin Livewire/Alpine/polling/toast, actual response header capture at a production TLS layer, and responsive production acceptance. These remain manual staging/production items.

### Files changed by Hardening D

- `.env.example`
- `app/Console/Commands/CheckMalwareScanner.php`
- `app/Console/Commands/CheckProductionSecurity.php`
- `app/Http/Middleware/SecurityHeaders.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/MalwareScannerHealthCheck.php`
- `app/Services/ProductionSecurityCheck.php`
- `config/files.php`
- `config/filesystems.php`
- `docs/08-operations/runbook.md`
- `docs/audit/security-hardening-d.md`
- `resources/js/app.js`
- `resources/views/admin/applications/show.blade.php`
- `resources/views/auth/otp.blade.php`
- `resources/views/client/applications/create.blade.php`
- `resources/views/client/applications/show.blade.php`
- `resources/views/client/registration/personal.blade.php`
- `resources/views/components/registration-document-card.blade.php`
- `resources/views/home.blade.php`
- `tests/Feature/Security/MalwareScannerHardeningTest.php`
- `tests/Feature/Security/ProductionSecurityCheckTest.php`
- `tests/Feature/Security/SecurityRegressionTest.php`

Other modified/untracked files shown by Git belong to the pre-existing Hardening B/C worktree and were preserved.

### Regression and scope confirmation

- Hardening A authorization/eligibility controls: preserved; related suites pass.
- Hardening B concurrency/payment integrity controls: preserved; related suites pass.
- Hardening C chat abuse, storage alias, payment validation, payload minimization, and pruning guards: preserved; related suites pass.
- SEC-012: remains deferred.
- SEC-022 retention policy: remains deferred; no automatic provider/webhook pruning schedule was added.
- Application state machine, payment authority, chat unread/archive/edit/polling/presence/notifier behavior, email coalescing/delivery rules, private-file policy, and PostgreSQL schema were not changed.
- Production evidence was not fabricated. Queue configuration was not treated as worker uptime; schedule definition was not treated as scheduler execution; filesystem configuration was not treated as host permission proof; PostgreSQL selection was not treated as least-privilege/network/TLS proof; backup documentation was not treated as a successful restore; HSTS was not claimed without TLS-layer evidence.
- No migration and no dependency were added.

## Final classification

**SECURITY HARDENING D CODE COMPLETE WITH PRODUCTION ACCEPTANCE PENDING**

## Final test reliability reconciliation — 2026-09-09

The remaining `AdminOperationsUiTest::admin chat uses contextual headers without fake presence` failure was a test-environment cache leak, not a chat/presence product defect.

- Reproduction before correction: the test passed alone (**1 passed, 14 assertions**) and its containing file passed (**10 passed, 82 assertions**), while the smallest reproduced ordering in this reconciliation—`ActivityTest` followed by `AdminOperationsUiTest` with persisted presence entries from an earlier test run—failed (**13 passed, 1 failed, 99 assertions**).
- Root cause: `CACHE_STORE=array` was already configured for PHPUnit, but the dedicated `cache.presence_store` still resolved to its `file` default. Heartbeat tests therefore left `chat-presence:user:*` entries in persistent file cache. PostgreSQL test refreshes could later reuse the same synthetic user IDs, causing an independent admin UI test to read a prior run's presence timestamp.
- Isolation correction: PHPUnit now sets `CHAT_PRESENCE_CACHE_STORE=array`. `ChatPresenceTest` verifies this effective test configuration instead of masking it with a per-test config mutation. No global cache flush was added, and the production cache default/key format is unchanged.
- Repeated isolated verification: **5 consecutive passes**, each with **14 assertions**.
- Containing file: **10 passed, 82 assertions**.
- Former reproducing order: **14 passed, 104 assertions**.
- Related presence/chat/admin suite: **29 passed, 305 assertions**.
- Final full suite: **359 passed, 2,291 assertions**.
- Production behavior changed: **NO**. Presence thresholds, heartbeat, rendering, polling, notifier/toast, unread/archive/edit behavior, and Hardening A/B/C/D controls remain unchanged.
