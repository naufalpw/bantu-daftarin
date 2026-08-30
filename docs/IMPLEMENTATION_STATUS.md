# Bantu Daftarin Implementation Status

Tanggal ledger: 2026-08-26  
Repository: current checkout `bantu-daftarin`

Legenda: `[x]` terbukti dari source dan validasi yang sudah dijalankan, `[ ]` masih actionable, `[BLOCKED]` membutuhkan external/business/legal decision yang tidak aman untuk ditebak.

## Phase 0 — Foundation baseline

- [x] AGENTS.md tersedia dan menjadi engineering guide.
- [x] Product, domain lifecycle, architecture, security, database, integration, UX, testing, operations, ADR, dan open questions terdokumentasi.
- [x] Modular monolith boundary Client/Admin/Webhook tersedia.
- [x] Scope MVP terkunci pada NPWP Perseorangan dan NPWP Badan Usaha.
- [x] Lapor Pajak tetap COMING_SOON dan tidak memiliki workflow aktif.
- [x] ADR-0002 menetapkan MariaDB XAMPP sebagai database authoritative untuk development dan application database lokal.
- [BLOCKED] Exact Figma frame mapping and final business decisions for NIK/nomor KK, conditional business requirements, and production pricing require inputs not present in the repository.

## Phase 1 — Laravel foundation

- [x] Laravel 12 application scaffold dan Composer dependencies tersedia.
- [x] Blade, Livewire, Vite/Tailwind, route separation, queue database, mail boundary, logging, dan exception handling tersedia.
- [x] Laravel `mysql` PDO connection, InnoDB, and utf8mb4 configuration target the authoritative XAMPP MariaDB database.
- [x] Security headers dan session cookie settings tersedia.
- [x] `vendor/bin/pint --test`, PHP lint, route cache, view cache, config cache, frontend build, Composer validate, dan npm audit pernah pass.
- [x] Final foundation checks rerun after implementation changes: Pint, PHP lint, route/view/config cache, frontend build, Composer/npm validation and audits.

## Phase 2 — Database

- [x] Laravel migrations create authentication, catalog, application, document, payment, workflow, communication, audit, result, and queue tables.
- [x] Foreign keys, public ID uniques, ownership/status/reference indexes, delete behavior, and guarded models are implemented.
- [x] Service/requirement snapshots are implemented.
- [x] Factories and synthetic seeder are implemented.
- [x] Clean isolated local MariaDB migration and seeder succeeded on `bantu_daftarin_mvp`.
- [x] Migration status shows all four migrations ran on the isolated local database.
- [x] MariaDB 10.4.32 migration/schema compatibility verified directly on the active XAMPP server.
- [x] Schema/constraint evidence executed through migration status, clean seeding, foreign-key-backed feature setup, and the per-application requirement unique-constraint test.

## Phase 3 — Authentication

- [x] Client registration, email verification, login, password reset, email OTP, session regeneration, logout, and rate limits are implemented.
- [x] Admin login requires password plus email OTP and admin middleware.
- [x] OTP hashing, single-use, expiry, attempts, cooldown, lockout, resend, used-challenge handling after logout, and encrypted queued notification payload are implemented.
- [x] Passwords use Laravel hashing and secrets are environment-backed.
- [x] Client/admin/wrong OTP, registration/email verification, expired/reused OTP, brute-force lockout, resend cooldown, password reset, inactive admin, and session invalidation tests exist and pass.

## Phase 4 — Application workflow

- [x] Active service catalog and backend rejection of COMING_SOON service are implemented.
- [x] Personal and business detail models, representative relationship, consent, draft creation, autosave Livewire form, requirement snapshot, and price snapshot are implemented.
- [x] Application lifecycle transition service and status history/audit are implemented.
- [x] Ownership, COMING_SOON, personal/business creation, representative relationships, consent, snapshot isolation, transition history/audit, and status tampering tests exist and pass.
- [x] Lifecycle transition matrix, payment readiness, revision submission, and invalid representative field combinations are covered by unit/feature tests.
- [x] Client application submit/payment, automatic payment-ready transition after the final required upload, payment action rendering, and Livewire autosave screens are covered through HTTP/Livewire feature tests.

## Phase 5 — Documents

- [x] Private/quarantine disks, metadata, checksum, random storage key, versioning, active flag, review state, retention fields, and access audit are implemented.
- [x] Server-side extension/MIME/signature/size/parser checks and malware scanner abstraction are implemented.
- [x] Pre-payment upload/replace/delete, post-payment lock, admin review, targeted revision, and private audited stream/download are implemented.
- [x] Result document private storage, verification gate, preview/download authorization, and multiple result types are implemented.
- [x] Tests cover dangerous extensions, signature mismatch, parser/active-content rejection, size limits, malware unavailable, path traversal, versioning, lock/revision behavior, access logs, result authorization, and physical purge.
- [x] Infected-malware, per-requirement size limits, and duplicate requirement constraint tests exist and pass.
- [x] Local browser acceptance uses the built-in testing malware-scan abstraction when XAMPP has no ClamAV binary; the production-safe default remains ClamAV.

## Phase 6 — Xendit/payment

- [x] Official Xendit SDK adapter, payment snapshot, checkout boundary, callback-token verification, payload/reference/amount/currency/identity checks, event ledger, transaction, idempotency, and state separation are implemented.
- [x] Fake payment gateway, same-origin local checkout, payment creation/state separation, invalid-token, amount/reference/currency/identity, valid-paid-idempotent, expiry/failure, late callback, and browser-return non-authority tests exist and pass.
- [x] Malformed payload and conflicting/late event-state tests exist and pass.
- [BLOCKED] Live Xendit behavior requires deployment credentials and provider callback access; fake/contract boundary remains testable locally.

## Phase 7 — Admin

- [x] Admin dashboard/application queue, review start/finalize, revision reason/instruction, estimate history/reason, external status, result upload/review/verification, completion gate, and archive actions are implemented.
- [x] Admin policies and admin-sensitive audit calls are implemented.
- [x] Admin review, estimate prerequisites/history, result upload/verification/rejection, completion prerequisites, and private result access tests exist and pass.
- [x] Route-level authorization and archive behavior tests exist and pass.
- [x] Admin review form validation and transition service checks prevent missing reasons and arbitrary status mutation.

## Phase 8 — Communication

- [x] In-app Livewire chat thread, sender/read state/polling, ownership policy, database notifications, and queued email notification classes are implemented.
- [x] Chat ownership, unread/read, message validation, escaped-message storage/render boundary, notification dispatch, and Livewire send tests exist and pass.
- [BLOCKED] SMTP provider credentials and operational queue worker require deployment configuration.

## Phase 9 — Security hardening and operations

- [x] Policies exist for application, document, payment, result, and chat resources.
- [x] CSRF, rate limits, security headers, private storage, mass-assignment guards, encrypted sensitive casts, safe error messages, and audit logging boundaries exist.
- [x] Retention metadata and scheduled physical purge command exist.
- [x] Secret scan found no committed secret pattern; `public/storage` is absent; private/quarantine contain only placeholders.
- [x] Security regression tests cover CSRF middleware wiring, escaped message boundary, SQL-like ID input, path traversal, mass assignment, privilege escalation, private file access, headers, and rate limits. Laravel's unit-test harness bypasses token enforcement; actual 419 behavior remains an environment/manual check.
- [BLOCKED] Final retention duration/legal backup lifecycle requires business/legal confirmation.
- [BLOCKED] Production SMTP, ClamAV, storage, monitoring, backup, and queue deployment configuration requires operations credentials/decisions.

## Phase 10 — Full testing

- [x] Current suite passes: 73 tests, 283 assertions after final-upload/payment-ready, logout/login OTP, and local fake-checkout regression coverage on the isolated test database.
- [x] Unit, smoke, ownership, auth, application, document, payment, admin/result, chat, and security coverage exists.
- [x] Expanded full suite and all static/build/security checks pass after the latest coverage additions.
- [x] Full suite and static/build/security checks were rerun after final formatting and seeder/config fixes.
- [x] Automated evidence is separated from manual/browser acceptance evidence; no manual interactive result is claimed.
- [x] Complete migration and test verification rerun against the active MariaDB 10.4.32 XAMPP server.
- [x] Test procedure documents `php artisan optimize:clear` before PHPUnit so cached application configuration cannot redirect database-reset tests to the application database.
- [BLOCKED] Manual/browser acceptance requires an operator desktop/browser session and is not safely claimable from automated checks alone.
- [x] Manual browser acceptance checklist created at `docs/07-testing/manual-acceptance-checklist.md`, including seeded data, exact route/action coverage, expected state changes, and security negative cases.

## Final repository verification

- [x] Laravel boot and all routes verified after final changes.
- [x] Clean database migration status and idempotent seeder verified after final migration/config changes.
- [x] Authorization/IDOR and sensitive file exposure audit complete.
- [x] State transition, payment webhook, result completion gate, chat, notification, audit, and retention checks complete.
- [x] `git status`/`git diff` equivalent repository cleanliness check complete; current checkout has no `.git` directory.
- [x] No actionable unchecked items remain; only explicitly documented `[BLOCKED]` decisions remain.

## Current known decisions/risks

- The latest architecture decision makes XAMPP's MariaDB 10.4.32 authoritative for local development and the local application database; no alternate database installation or migration is required.
- Existing legacy schema in database `bantu_daftarin` was preserved. Local MVP uses isolated `bantu_daftarin_mvp` and `bantu_daftarin_mvp_test`.
- Figma files are not present in the repository, so exact frame mapping cannot be verified.
- Manual email-dependent acceptance requires a local SMTP mail catcher; the current `MAIL_MAILER=log` setting is not recommended as an OTP inspection method.
