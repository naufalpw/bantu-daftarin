# BantuDaftarin Security & Integrity Audit

## Metadata

Date: 2026-09-08

Repository/worktree: `C:\Users\nopal\Documents\project-bug-squasher\bantu-daftarin`

Audit mode: Source-grounded, non-destructive, audit only

Production source modified: NO

Audit artifact: `docs/audit/security-audit.md`

Evidence coverage: 104 unique repository evidence files are cited in this document (excluding the audit artifact itself), with broader scoped searches across routes, PHP source, Blade, JavaScript, configuration, migrations, tests, and documentation.

## Scope

The audit covers the current Indonesian BantuDaftarin Laravel monolith: public/authentication entry points, authenticated client and admin surfaces, Livewire actions, application and document workflows, payment/Xendit webhook processing, private-file delivery, chat/presence/notifications, sensitive data, browser controls, dependency advisories, and documented production operations.

## Non-goals

- No production system, third-party provider, real account, real email, real payment, or private document was attacked or queried.
- No production code, test, route, configuration, dependency, migration, schema, authentication mechanism, payment gateway, or chat architecture is changed by this audit.
- Recommendations harden the existing design. They do not propose replacement authentication, gateways, WebSockets, external help desks, or a frontend/platform rewrite.
- Runtime-only conclusions are explicitly marked `NEEDS_RUNTIME_VERIFICATION`; source evidence is not presented as proof of an unavailable production environment.

## Progress

| Checkpoint | Status | Findings | Critical/High | Runtime checks |
|---|---|---:|---:|---|
| CP0 Baseline + architecture | COMPLETE | 0 | 0 | Local baseline recorded |
| CP1 Input + injection | COMPLETE | 3 | 0 | Source/tests; no browser exploit run |
| CP2 Auth + authorization | COMPLETE | 5 | 1 | Livewire revocation behavior derived from source |
| CP3 State integrity | COMPLETE | 5 | 2 | Concurrent interleavings still require runtime simulation |
| CP4 Files + sensitive data | COMPLETE | 4 | 0 | Production scanner/storage/backup unavailable |
| CP5 Payment + webhook | COMPLETE | 5 | 2 | Provider retry/dual-write failure injection still required |
| CP6 Livewire + chat + notifications | COMPLETE | 4 | 1 | Snapshot revocation and abuse limits need runtime tests |
| CP7 Production + browser + dependencies | COMPLETE | 6 | 2 | Production host/edge/services unavailable |
| CP8 Final synthesis | COMPLETE | 32 | 7 | Runtime checklist documented; final commands passed |

## Methodology and classification

This audit traces reachable routes through middleware, controllers, Form Requests, policies, services/actions, models, migrations, storage configuration, asynchronous jobs, views, JavaScript, and tests. Source evidence is authoritative. Tests are corroborating evidence, not a substitute for reading the implementation.

Each item is classified as one of:

- `CONFIRMED`: the risky behavior is directly established by reachable source or a reproducible test.
- `POTENTIAL`: source exposes a plausible path, but exploitation depends on runtime timing, infrastructure, or external-provider behavior.
- `NOT_VULNERABLE`: the reviewed path has an effective, source-proven control for the stated threat.
- `NEEDS_RUNTIME_VERIFICATION`: deployment or browser/runtime state is required to reach a conclusion.
- `DEFENSE_IN_DEPTH`: no practical exploit was established, but a bounded hardening opportunity exists.

Severity uses `CRITICAL`, `HIGH`, `MEDIUM`, `LOW`, and `INFO`. Counts represent root findings rather than each route or rendered use.

## CP0 — Baseline and architecture map

### Baseline

- Initial `git status --short`: clean (no output).
- `php artisan test --no-coverage`: PASS — 269 tests, 1,674 assertions.
- `npm run build`: PASS — Vite 6.4.3, 57 modules transformed.
- `composer audit --locked`: no known security vulnerability advisories.
- `npm audit --omit=dev`: 0 vulnerabilities.
- No database migration, dependency, production configuration, or production source was changed by this audit.

### Architecture and trust boundaries

| Boundary | Ingress / source | Existing boundary control | Audit focus |
|---|---|---|---|
| Public/auth | `routes/web.php:14-46` | Guest grouping where appropriate; signed email verification; named rate limiters on login, OTP, resend, and password-reset email | Enumeration, OTP lifecycle, session rotation, CSRF, open redirect |
| Client workspace | `routes/client.php:13-38`, `routes/web.php:53-59` | `auth`, `verified`, `client`; per-resource policy checks expected in controllers/components | IDOR, mass assignment, state tampering, private-file access |
| Admin operations | `routes/admin.php:14-40` | `auth`, `admin`; active user/admin checks in `EnsureAdmin` | Livewire endpoint authorization, state transitions, sensitive-data exposure |
| Payment webhook | `routes/webhooks.php:6` | CSRF exemption at `bootstrap/app.php:29`, `throttle:webhook`; provider-token/idempotency checks to be verified in CP5 | Forgery, replay, duplicate processing, raw payload leakage |
| Local fake payment | `routes/web.php:65-73` | Registered only in `local`/`testing`, fake driver, plus `auth`, `verified`, `client` | Production reachability and ownership checks |
| Presence heartbeat | `routes/web.php:48-51` | Read-only session plus `auth`; avoids normal session write lock | Session authenticity, request scope, side effects |
| Private storage | Document/result controllers, policies, filesystem config | Private/quarantine disks and policy-gated streaming expected | Traversal, active-version rules, MIME/content validation, response headers |
| Async queue/email | Jobs, notifications, database queue | After-commit/coalescing patterns expected | Secret leakage, recipient integrity, duplicate delivery |
| Browser rendering | Blade, Livewire, Alpine, application JavaScript | Blade escaping and security headers expected | Stored/reflected/DOM XSS, unsafe sinks, CSP effectiveness |

### Route and middleware observations

- Client application, payment, document, result, and chat routes are grouped under `auth`, `verified`, and `client` (`routes/client.php:13-38`).
- Admin operational routes are grouped under `auth` and `admin` (`routes/admin.php:14-40`). `EnsureAdmin` additionally rejects inactive users, non-admin roles, and inactive admin profiles (`app/Http/Middleware/EnsureAdmin.php:13-17`).
- Email verification uses a signed route (`routes/web.php:36`). Login, OTP verification/resend, and password-reset email submission are rate limited (`routes/web.php:29-40`).
- Global security headers are appended in `bootstrap/app.php:23`; the implemented header set is reviewed in CP7.
- CSRF is disabled only for `webhooks/*` (`bootstrap/app.php:29`); webhook authenticity must therefore be established independently in CP5.
- The architecture documents define a modular Laravel monolith with separated client/admin/webhook ingress, Form Requests, policies, services/actions, Eloquent, private storage, and database queues (`docs/02-architecture/architecture.md:3-10`). Security documentation requires policy checks for applications, documents, payments, result documents, and chat threads (`docs/03-security/security.md:9`).

### CP0 conclusion

The repository starts from a security-conscious architecture with explicit route boundaries, private-resource policies, PostgreSQL persistence, and documented production controls. CP0 does not treat those architectural intentions as proof that every endpoint is safe; the following checkpoints verify their concrete use and race/error behavior.

## CP1 — Input, injection, and request integrity

### SEC-001 — Stored/reflected and DOM XSS controls on active paths

- **Route/action:** Chat rendering, admin/client free-text rendering, help filtering, toast rendering, testimonial carousel.
- **Source evidence:** `resources/views/livewire/chat-thread.blade.php:54`; `resources/js/app.js:304-311`, `resources/js/app.js:393-394`, `resources/js/app.js:444-445`, `resources/js/app.js:485-487`; `tests/Feature/Chat/ChatTest.php:43-63`.
- **Threat:** An attacker stores HTML/script in chat or another free-text field and causes it to execute for a client/admin; DOM code promotes user-controlled strings into HTML.
- **Existing protection:** Active Blade output uses escaped `{{ ... }}` syntax. The JavaScript audit found no `innerHTML`, `outerHTML`, `insertAdjacentHTML`, `document.write`, `eval`, `new Function`, or Alpine `x-html` sink outside compiled assets. Dynamic help/testimonial text is assigned with `textContent`. The chat regression test persists `<script>alert(1)</script>`, then proves raw script is absent and encoded text is rendered.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High
- **Recommendation:** Preserve escaped Blade output and `textContent`. If future rich-text rendering is introduced, require an explicit sanitizer and a dedicated stored-XSS test.
- **Architecture impact:** None.

### SEC-002 — SQL injection controls for identifiers, filters, and search

- **Route/action:** Client resource lookup; reactive admin application/document/support/user search; dashboard activity aggregation.
- **Source evidence:** `app/Http/Controllers/Client/ApplicationController.php:185-190`; `app/Livewire/Admin/ApplicationQueue.php:49-68`; `app/Livewire/Admin/DocumentQueue.php:84-103`; `app/Livewire/Admin/SupportInbox.php:75-91`; `app/Livewire/Admin/UserDirectory.php:55-61`; `app/Support/AdminActivityPresenter.php:47-54`, `app/Support/AdminActivityPresenter.php:147-190`; `tests/Feature/Security/SecurityRegressionTest.php:73-78`.
- **Threat:** Crafted UUID/search/filter values alter SQL syntax or bypass ownership predicates.
- **Existing protection:** User-controlled values flow through Eloquent/query-builder predicates and parameter binding. The reviewed `selectRaw`/`orderByRaw` fragments are static application constants and interpolate no request data. Public IDs are UUID-checked or matched as bound values together with ownership. A regression test confirms a SQL-like public ID does not bypass ownership.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High
- **Recommendation:** Keep raw SQL fragments static; if dynamic ordering is added, map request values to an allowlist rather than concatenating them.
- **Architecture impact:** None.

### SEC-003 — Legacy payment endpoint silently falls back to BCA

- **Route/action:** `POST /app/applications/{publicId}/payment` (`client.applications.payment`).
- **Source evidence:** Route remains registered at `routes/client.php:24`; `app/Http/Controllers/Client/ApplicationController.php:144-155` reads an unvalidated request and maps any missing/unknown value to `PaymentMethod::BCA`. The current payment screen uses the stricter `client.payments.store` endpoint backed by `StorePaymentRequest` (`resources/views/client/payment/show.blade.php:100-101`, `app/Http/Requests/Client/StorePaymentRequest.php:16-20`).
- **Threat:** A crafted request to the legacy endpoint does not receive a validation failure and can create/select a BCA payment even when the supplied value is invalid.
- **Existing protection:** The route is still behind `auth`, `verified`, and `client`; the application lookup is owner-scoped; `createPayment` locks the application and enforces payment readiness and method availability. The fallback does not enable another user's payment, bypass price/state gates, or select an unsupported provider.
- **Status:** `DEFENSE_IN_DEPTH`
- **Severity:** `LOW`
- **Confidence:** High
- **Recommendation:** In a bounded hardening patch, either remove the redundant legacy endpoint after confirming no supported client uses it, or give it the same `StorePaymentRequest` allowlist and reject invalid values. Do not change payment state rules.
- **Architecture impact:** None; endpoint consolidation or request-validation parity only.

### Input/XSS matrix

| Input class | Entry point | Validation / handling | Rendering / query sink | Conclusion |
|---|---|---|---|---|
| Registration and application data | Form Requests and Livewire form | Type, length, enum, UUID, conditional-field rules; controllers select explicit fields | Escaped Blade; Eloquent parameter binding | No practical XSS/SQLi/mass-assignment path established |
| Chat body/edit body | Livewire `ChatThread` | Required string, max 2,000 characters (`app/Livewire/ChatThread.php:52-55`, `app/Livewire/ChatThread.php:165-172`) | Escaped Blade (`resources/views/livewire/chat-thread.blade.php:54`) | Stored text is safe as plain text |
| Admin review/estimate/result input | Admin Form Requests | Admin authorization, enums/booleans/dates, length constraints, file size | Escaped output; service/state checks reviewed in CP3 | Input shape is constrained |
| Reactive admin search/filter | Livewire URL/public properties | Filter keys mapped to allowlists; search is trimmed text | Bound `whereLike`; static ordering expressions | No SQL injection path established |
| Payment method | Current payment screen | `StorePaymentRequest` allowlist | Enum and workflow gate | Safe on primary path; legacy fallback is SEC-003 |
| Uploaded files | Upload Form Requests plus document service | Request-level file/size checks; deep validation deferred to CP4 | Private/quarantine storage | CP4 determines content-security conclusion |

### CP1 conclusion

No practical XSS, SQL injection, CSRF-bypass, or mass-assignment exploit was established on the reviewed active input paths. Blade escaping, `textContent`, Eloquent binding, explicit field selection, enum/shape validation, and framework CSRF middleware provide layered controls. SEC-003 is a low-severity validation-consistency issue on a redundant endpoint, not a demonstrated authorization or payment-state bypass.

## CP2 — Authentication, session, authorization, and IDOR

### SEC-004 — OTP authentication and session transition controls

- **Route/action:** Login, OTP issue/verify/resend, logout.
- **Source evidence:** `routes/web.php:29-43`; `app/Providers/AppServiceProvider.php:55-57`; `app/Services/AuthOtpService.php:18-104`; `app/Http/Controllers/Auth/AuthController.php:42-130`; `tests/Feature/Authentication/OtpTest.php:135-178`, `tests/Feature/Authentication/OtpTest.php:184-284`, `tests/Feature/Authentication/OtpTest.php:323-341`.
- **Threat:** OTP guessing/reuse, cross-user challenge substitution, privilege confusion between client/admin OTP, fixation of the pre-authentication session, or reuse after logout.
- **Existing protection:** Six-digit codes use `random_int`, only a hash is persisted, prior unused challenges are invalidated, expiry/max-attempt/temporary-lock controls are enforced, challenge verification is serialized with `lockForUpdate`, the challenge is bound to both user and challenge type, and successful login regenerates the session before clearing pending-auth keys. Login/verify/resend have distinct rate limits. Logout invalidates the session and regenerates the CSRF token.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High
- **Recommendation:** Preserve the lock, generic invalid-code response, rate-limit separation, and session regeneration. OTP issuance concurrency is assessed separately in CP3.
- **Architecture impact:** None.

### SEC-005 — Password-reset request discloses account existence

- **Route/action:** `POST /forgot-password` (`password.email`).
- **Source evidence:** `app/Http/Controllers/Auth/PasswordResetController.php:21-27` returns the broker status verbatim; `lang/id/passwords.php:6-8` defines distinct success and unknown-user responses, including `Kami tidak menemukan akun dengan alamat email tersebut.`; `tests/Feature/Authentication/PasswordResetLocaleTest.php:16-20` intentionally asserts that unknown-user text.
- **Threat:** An unauthenticated actor can submit candidate email addresses and distinguish registered accounts from unknown accounts through the visible status message.
- **Existing protection:** The endpoint is rate limited at five requests/minute per normalized email plus IP (`routes/web.php:31`, `app/Providers/AppServiceProvider.php:55`). Reset tokens remain broker-managed, time-limited, throttled, and required to change a password. Enumeration does not itself grant account access.
- **Status:** `CONFIRMED`
- **Severity:** `MEDIUM`
- **Confidence:** High
- **Recommendation:** Return one neutral acknowledgement for both registered and unknown addresses while retaining the existing broker call and rate limits; keep detailed status only in non-user-facing telemetry if needed. Add a focused response-equivalence test.
- **Architecture impact:** None; controller response normalization only.

### SEC-006 — Deactivated admins retain Livewire component access

- **Route/action:** Livewire updates for Admin Dashboard activity, Pengajuan, Dokumen, Dukungan, Aktivitas, and Pengguna components.
- **Source evidence:** Page routes apply `auth` + `admin` (`routes/admin.php:14-40`). `EnsureAdmin` rejects an inactive user or inactive admin profile (`app/Http/Middleware/EnsureAdmin.php:13-17`). However, all reactive admin components inherit `AdminComponent`, whose per-Livewire-request guard checks only authentication and `isAdmin()` (`app/Livewire/Admin/AdminComponent.php:7-22`). A current regression test covers a client role but not inactive admin states (`tests/Feature/Admin/AdminReactiveFilteringTest.php:132-137`). Policies also treat any `isAdmin()` user as admin without an active-state condition (`app/Policies/ApplicationPolicy.php:15-37`, `app/Policies/DocumentPolicy.php:10-38`, `app/Policies/ChatThreadPolicy.php:10-18`).
- **Threat:** An admin whose `users.is_active` or `admins.is_active` flag is revoked while an authenticated admin page/component snapshot is still open can continue issuing Livewire filter/pagination/archive requests and receive operational or client data until the session expires or is otherwise invalidated.
- **Existing protection:** Initial document navigation is blocked by `EnsureAdmin`; Livewire requires an authenticated session and a valid component snapshot/checksum, so this is not an unauthenticated component-mount bypass. A non-admin role is rejected.
- **Status:** `CONFIRMED`
- **Severity:** `HIGH`
- **Confidence:** High
- **Recommendation:** Make the shared `AdminComponent` authorization predicate equivalent to `EnsureAdmin` (active user, admin role, active admin profile), ideally through one reusable authoritative check. Add tests for both inactive flags on initial mount and subsequent Livewire calls. Consider session invalidation when deactivating admins as an additional control, not a substitute for request-time authorization.
- **Architecture impact:** Small shared authorization hardening; no filtering/domain redesign.

### SEC-007 — Resource ownership and IDOR controls

- **Route/action:** Client applications/activity, document upload/view/download/delete, result view/download, payment, chat; admin resources; fake-payment checkout.
- **Source evidence:** Owner-scoped application queries at `app/Http/Controllers/Client/ApplicationController.php:185-191` and payment queries at `app/Http/Controllers/Client/PaymentController.php:107-113`; document upload binds requirement to the owner-scoped application at `app/Http/Controllers/Client/DocumentController.php:22-28`; document/result/chat policies at `app/Policies/DocumentPolicy.php:10-38`, `app/Policies/ResultDocumentPolicy.php:10-22`, `app/Policies/ChatThreadPolicy.php:10-18`; fake payment applies `PaymentPolicy` at `app/Http/Controllers/Testing/FakePaymentController.php:74-81`; cross-owner tests at `tests/Feature/Authorization/OwnershipTest.php:14-34`, `tests/Feature/Documents/DocumentWorkflowTest.php:253-260`, `tests/Feature/Client/ClientUxPhaseBTest.php:383-390`, and `tests/Feature/Chat/ChatTest.php:23-40`.
- **Threat:** Guessing/swapping public IDs to read or mutate another client's application, document, result, payment, or conversation.
- **Existing protection:** Public IDs are not authorization. Controllers either include `user_id`/parent linkage in lookup or invoke a registered policy. Document downloads additionally require passed scanning and active-version state for clients; results require `VERIFIED`; chat requires admin role or thread ownership. Admin routes require admin middleware and controller policy checks where resource-level actions occur.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High
- **Recommendation:** Maintain owner scoping plus policy checks and keep negative cross-owner tests for every new private-resource endpoint.
- **Architecture impact:** None.

### SEC-008 — Session cookie and CSRF posture

- **Route/action:** All web and Livewire state-changing requests except provider webhook.
- **Source evidence:** CSRF exemption is limited to `webhooks/*` (`bootstrap/app.php:29`); web routes include CSRF middleware as asserted at `tests/Feature/Security/SecurityRegressionTest.php:18-23`; session defaults use the database driver, production-dependent secure cookies, `HttpOnly=true`, and `SameSite=lax` (`config/session.php:21`, `config/session.php:172-202`); successful OTP authentication regenerates the session (`app/Services/AuthOtpService.php:95-100`).
- **Threat:** Cross-site state-changing requests, session theft through client-side script, and session fixation.
- **Existing protection:** Laravel CSRF covers web/Livewire requests; forms include `@csrf`; the presence fetch explicitly sends the CSRF token; cookies are HttpOnly and SameSite Lax; secure cookies default on when `APP_ENV=production`; login and logout rotate/invalidate session state.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High for source defaults; production effective values are verified in CP7.
- **Recommendation:** Preserve framework CSRF middleware and confirm effective production environment values for HTTPS, trusted proxies, cookie domain, and `SESSION_SECURE_COOKIE` during deployment review.
- **Architecture impact:** None.

### Authorization / IDOR matrix

| Resource / action | Route protection | Resource check | Result |
|---|---|---|---|
| Client application read/update/submit/cancel | `auth`, `verified`, `client` | Owner-scoped lookup plus `ApplicationPolicy` | Cross-owner access blocked |
| Document upload | Client middleware | Application owner scope + requirement belongs to application | Cross-application requirement substitution blocked |
| Document view/download/delete | Client/admin middleware depending route | `DocumentPolicy`; scan/active/deleted gates | Private access is policy-gated |
| Result view/download | Client/admin middleware depending route | `ResultDocumentPolicy`; client requires owner + `VERIFIED` | Unverified/cross-owner result blocked |
| Payment view/create/fake checkout | Client middleware; fake route is local/testing only | Owner-scoped application or `PaymentPolicy`; workflow state gates | Cross-owner payment access blocked |
| Chat page/actions | Client/admin route middleware | `ChatThreadPolicy` and per-call thread ownership checks | Cross-owner thread access blocked; inactive-state gap is SEC-006 |
| Admin HTML controllers | `auth`, `admin` | Active checks in middleware; action policies | Strong on normal page requests |
| Admin Livewire updates | Authenticated Livewire endpoint | Role-only `AdminComponent` check | Inactive-state bypass confirmed in SEC-006 |

### CP2 conclusion

OTP generation, single-use verification, rate limiting, session regeneration, CSRF, and ordinary IDOR controls are strong and source-tested. Two actionable gaps remain: confirmed account enumeration on password reset (MEDIUM) and a confirmed active-status mismatch on admin Livewire requests (HIGH).

## CP3 — State integrity, duplicate processing, and race conditions

### SEC-009 — Core state transitions, document versioning, cancellation, and chat mutations are serialized

- **Route/action:** Application status transitions; document upload/review; client cancellation; general-support thread creation; archive/edit/delete chat operations; chat unread-email coalescing.
- **Source evidence:** `app/Services/ApplicationTransitionService.php:17-59`; `app/Services/DocumentWorkflowService.php:27-75`, `app/Services/DocumentWorkflowService.php:82-127`; `app/Services/ApplicationCancellationService.php:48-92`; `app/Http/Controllers/Client/GeneralSupportController.php:14-27`; `app/Services/ChatThreadArchiveService.php:14-83`; `app/Services/ChatMessageManagementService.php:14-72`; `app/Services/ChatUnreadEmailService.php:27-99`; uniqueness at `database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:93`, `database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:105`, `database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:140`, `database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:270`, and `database/migrations/2026_09_06_000500_add_chat_message_controls_and_thread_user_states.php:24`.
- **Threat:** Double-click/replay creates conflicting status histories, duplicate document versions, cancellation after payment confirmation, duplicate support threads, or lost archive/message edits.
- **Existing protection:** Application transitions lock the application and re-read current state. Document upload locks both application and requirement before calculating version/deactivating the prior version; review locks document and application and revalidates eligibility. Cancellation locks application and its payments. General-support creation serializes on the client row. Archive and message management lock thread/message. Email coalescing locks recipient/thread state and uses a pending token. Database uniqueness backs one detail/chat row per application and one participant state per thread/user.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High
- **Recommendation:** Preserve the lock-and-revalidate pattern and add concurrency tests when introducing any new transition that writes related rows.
- **Architecture impact:** None.

### SEC-010 — Result verification and application completion can race into an invalid terminal state

- **Route/action:** `POST /admin/results/{resultId}/verify` and `POST /admin/applications/{publicId}/complete`.
- **Source evidence:** `app/Services/AdminWorkflowService.php:174-199` checks and writes result verification without a surrounding transaction or row lock. `app/Services/AdminWorkflowService.php:204-210` checks `hasVerifiedPrimaryResult()` before delegating to the separately locked application transition; the predicate is at `app/Models/Application.php:104-107`. There is no database constraint coupling `applications.status=COMPLETED` to a verified primary result.
- **Threat:** Two admin requests can interleave: one verifies the primary result; completion observes it; a conflicting verification request changes the same result to `REJECTED`; completion then locks/transitions the application to `COMPLETED`. The terminal application can consequently have no currently verified primary result. Conflicting verification writes can also overwrite actor/reason state.
- **Existing protection:** Sequential requests enforce `RESULT_REVIEW`, passed scan, non-deleted result, required rejection reason, and a verified primary result before completion. Normal tests cover sequential gating and idempotent result notification, but not concurrent conflicting verification/completion.
- **Status:** `POTENTIAL`
- **Severity:** `HIGH`
- **Confidence:** High for the unlocked interleaving; runtime concurrency reproduction not executed in this audit.
- **Recommendation:** In one transaction, lock the application and relevant result row(s), re-evaluate result/application eligibility after locks are acquired, persist verification, and lock/recheck the verified-primary invariant during completion. Add a PostgreSQL concurrency regression test for verify-vs-reject-vs-complete.
- **Architecture impact:** Bounded transaction/locking hardening inside the existing service.

### SEC-011 — Review assignment is written before the authoritative transition lock

- **Route/action:** `POST /admin/applications/{publicId}/review/start`.
- **Source evidence:** `app/Services/AdminWorkflowService.php:29-39` validates a caller-supplied/stale application instance, writes `assigned_admin_id` to application/chat, then calls `ApplicationTransitionService`; the authoritative application row is only locked inside `app/Services/ApplicationTransitionService.php:17-24`, where an already-reached target is treated as idempotent.
- **Threat:** Concurrent start-review requests from different admins can produce a status history/audit actor that differs from the final application/chat assignee, depending on write/lock order.
- **Existing protection:** The transition itself is serialized and cannot create an invalid state. The risk is cross-row/actor consistency, not an unauthorized transition.
- **Status:** `POTENTIAL`
- **Severity:** `MEDIUM`
- **Confidence:** High for source-level race; runtime interleaving not reproduced.
- **Recommendation:** Lock and re-read the application before eligibility/assignment, and commit application assignment, chat assignment, and transition/history as one transaction. Add a two-admin concurrency test.
- **Architecture impact:** Bounded service transaction change.

### SEC-012 — Application creation accepts replayed duplicate submissions

- **Route/action:** `POST /app/applications` (`client.applications.store`).
- **Source evidence:** `app/Services/ApplicationWorkflowService.php:29-78` creates a new application, requirements, chat thread, and consent on each call. The applications table has indexes but no duplicate-request/idempotency constraint (`database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:70-88`). The service catalog intentionally locates an existing non-terminal application (`app/Http/Controllers/Client/ServiceCatalogController.php:14-25`), suggesting duplicate open drafts are not the primary UI flow.
- **Threat:** Browser retry, rapid double submit, or replay of a valid CSRF-authenticated request creates multiple active drafts and related records for the same client/service.
- **Existing protection:** Each individual creation is transactional, service availability/kind/consent are validated, and per-application child uniqueness prevents partial duplication within one draft. Multiple historical applications remain legitimate, so a blanket user/service unique constraint would be incorrect.
- **Status:** `POTENTIAL`
- **Severity:** `MEDIUM`
- **Confidence:** High that replay creates multiple rows; business impact and intended allowance for simultaneous active applications require confirmation.
- **Recommendation:** Confirm the one-open-application-per-service rule. If intended, serialize on user/service and return the existing open application; otherwise use a short-lived server idempotency key for the create action. Avoid a permanent uniqueness rule that blocks legitimate later applications.
- **Architecture impact:** Small request/service idempotency control; `[BUSINESS CONFIRMATION REQUIRED]` for simultaneous-open policy.

### SEC-013 — OTP issuance/resend check is not atomic

- **Route/action:** Login-triggered OTP issue and `POST /auth/otp/resend`.
- **Source evidence:** `app/Services/AuthOtpService.php:18-35` reads cooldown state, then `app/Services/AuthOtpService.php:37-58` separately invalidates unused challenges and creates/notifies a new challenge without a transaction or user/challenge row lock.
- **Threat:** Concurrent issue/resend requests can both pass the cooldown read, leading to multiple notifications and timing-dependent invalidation; under a narrow interleaving, more than one unused challenge may exist temporarily. This is primarily authentication reliability/availability risk rather than a demonstrated login bypass because every code still requires mailbox access and verification binds user/type.
- **Existing protection:** Endpoint throttles, 60-second logical cooldown, hashed codes, attempt limits, and locked verification substantially limit abuse. Verification marks one challenge used atomically.
- **Status:** `POTENTIAL`
- **Severity:** `LOW`
- **Confidence:** Medium; exact PostgreSQL interleaving needs a concurrent runtime test.
- **Recommendation:** Serialize issuance per user/type in a transaction (for example by locking the user or latest challenge), re-check cooldown after the lock, invalidate old challenge(s), create the new challenge, and dispatch after commit.
- **Architecture impact:** Bounded service transaction hardening.

### Duplicate/race matrix

| Flow | Duplicate/replay control | Lock/recheck | Residual conclusion |
|---|---|---|---|
| Application status transition | Same-target idempotence; transition graph | Application row locked and current state re-read | Strong |
| Draft creation | Transaction only | No user/service lock or idempotency token | SEC-012 |
| Document upload/version | New random file plus DB transaction | Application + requirement locked, active version replaced | Strong; filesystem/DB boundary reviewed in CP4 |
| Document review | Pending-only rule | Document + application locked and rechecked | Strong |
| Cancellation vs payment | Idempotent cancelled state | Application + payments locked and rechecked | Strong |
| Start review/assignment | Transition idempotence | Assignment occurs before transition lock | SEC-011 |
| Result verify/complete | Sequential business checks | No common result/application lock | SEC-010 |
| Payment creation | Reuses live pending payment | Application and pending payment locked | Strong locally; external dual-write reviewed in CP5 |
| Webhook replay | Provider/event unique key | Event/payment locks and state checks | Reviewed in CP5 |
| General-support thread creation | `firstOrCreate` | Client row lock serializes creation | Strong despite non-unique client/context index |
| Chat message send | One row per accepted send action | Thread lock; no client-generated idempotency key | Ordinary user double-send remains possible, but no state/authorization bypass established |
| Chat edit/delete/archive | Eligibility rules | Thread/message/state locks | Strong |
| Chat unread email | Pending token + unique thread/user state | Thread/state locks and unread recheck | Strong |
| OTP issue/resend | Cooldown/throttle | No issuance lock | SEC-013 |

### CP3 conclusion

The application has a strong transactional baseline and uses PostgreSQL row locks deliberately. The main integrity concern is the unlocked result-verification/completion relationship (HIGH, runtime concurrency verification required), followed by review-assignment consistency and duplicate draft creation. These are narrow hardening targets inside existing services, not reasons to redesign the state machine.

## CP4 — Files, documents, and sensitive data

### SEC-014 — Document/result upload validation and private delivery controls

- **Route/action:** Client document upload/view/download/delete; admin result upload; client/admin private document/result delivery.
- **Source evidence:** `app/Services/DocumentFileService.php:17-123`; `app/Services/AdminWorkflowService.php:106-166`, `app/Services/AdminWorkflowService.php:216-242`; private and quarantine disks at `config/filesystems.php:33-47`; authorization and streamed delivery at `app/Http/Controllers/Client/DocumentController.php:22-108` and `app/Http/Controllers/Client/ResultDocumentController.php:13-70`; document/result policies at `app/Policies/DocumentPolicy.php:10-38` and `app/Policies/ResultDocumentPolicy.php:10-22`; tests at `tests/Feature/Documents/DocumentWorkflowTest.php:30-178`, `tests/Feature/Documents/DocumentWorkflowTest.php:246-281`.
- **Threat:** Extension/MIME spoofing, active PDF content, image parser abuse, malware upload, path traversal, predictable names, direct web-root access, cross-owner download, stale-version access, and abandoned quarantine files.
- **Existing protection:** Requirements provide extension/MIME/size allowlists; server-observed MIME and file signatures must agree; PDFs require an EOF marker and reject JavaScript/launch/embedded/open-action tokens; images must parse; uploaded content is placed in quarantine, scanned, and only then copied to a non-serving private disk. Scanner unavailability fails closed. Paths use server-generated application IDs, sanitized requirement codes, and random UUID filenames. Quarantine is removed on success/failure paths. Policies gate delivery, clients only receive scanned active documents and verified results, filenames are header-sanitized, and access/purge are audited.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High for source controls.
- **Recommendation:** Preserve fail-closed scanning, random server filenames, private streaming, authorization, and negative file tests. Treat signature/regex checks as pre-validation rather than a replacement for maintained malware scanning and hardened PDF/image libraries.
- **Architecture impact:** None.

### SEC-015 — Serve-enabled alias points at the private storage root

- **Route/action:** Framework-generated `GET|PUT /storage/{path}` routes for disk `local`.
- **Source evidence:** Dedicated `private` and `quarantine` disks have `serve=false`, but the separate `local` disk points to the same `storage/app/private` root with `serve=true` (`config/filesystems.php:33-54`). `php artisan route:list` confirms `storage.local` and `storage.local.upload` routes. Framework `ServeFile` requires a valid relative signature for a non-public disk and catches path traversal (`vendor/laravel/framework/src/Illuminate/Filesystem/ServeFile.php:25-67`). A scoped source search found no application call to `Storage::disk('local')`, `temporaryUrl`, or `Storage::url` for private files.
- **Threat:** Future code may accidentally mint framework signed URLs from the serve-enabled alias, bypassing application-specific policy/audit controllers and broadening private-file delivery surface.
- **Existing protection:** Requests without a valid signed URL are rejected; the application currently uses the `private` disk and controller/policy streaming, and `.env.example` sets `FILESYSTEM_DISK=private`. No unsigned/public file disclosure path was established.
- **Status:** `DEFENSE_IN_DEPTH`
- **Severity:** `LOW`
- **Confidence:** High
- **Recommendation:** Give the generic `local` disk a separate non-sensitive root or set `serve=false` if local temporary serving is not required. Retain the explicit `private`/`quarantine` disks as the only document roots.
- **Architecture impact:** Configuration hardening only.

### SEC-016 — Malware-scanner effectiveness is deployment-dependent

- **Route/action:** Every client document and admin result upload.
- **Source evidence:** `AppServiceProvider` selects `TestingMalwareScanner` whenever `files.malware_scan_driver=testing`, otherwise `ClamAvMalwareScanner` (`app/Providers/AppServiceProvider.php:32-42`). ClamAV executes `clamdscan` with a 60-second timeout and treats non-0/1 exit codes as unavailable (`app/Services/ClamAvMalwareScanner.php:10-27`); upload services reject unavailable/non-clean scans. `.env.example` defaults to `clamav` (`.env.example:81`), while test configuration explicitly uses `testing` (`phpunit.xml:34`). On this audit host, `clamdscan --version` failed because the executable is not installed.
- **Threat:** A production environment mistakenly configured with the testing scanner would accept every file as clean; a production environment without working ClamAV would block all uploads (safe failure, but operationally unavailable).
- **Existing protection:** Default configuration is ClamAV and failure is closed. Documentation explicitly confines the testing driver to test/manual acceptance and instructs restoring it (`docs/07-testing/manual-acceptance-checklist.md:48-53`, `docs/07-testing/manual-acceptance-checklist.md:448`).
- **Status:** `NEEDS_RUNTIME_VERIFICATION`
- **Severity:** `MEDIUM`
- **Confidence:** High that local ClamAV is absent; production configuration/runtime was not available.
- **Recommendation:** Add a production deployment/startup check that rejects `MALWARE_SCAN_DRIVER=testing`, verifies the scanner binary/socket and definitions, and exercises one clean plus one EICAR-style synthetic sample in an isolated acceptance environment. Do not weaken fail-closed behavior.
- **Architecture impact:** Deployment guard/health check only.

### SEC-017 — NIK, KK, filenames, and admin display minimization

- **Route/action:** Personal application create/update/autosave; admin application detail; document/result metadata persistence.
- **Source evidence:** NIK and KK are encrypted model casts (`app/Models/PersonalApplicationDetail.php:10-17`); document/result original filenames are encrypted (`app/Models/Document.php:14-27`, `app/Models/ResultDocument.php:15-28`); input requires 16 digits where supplied (`app/Http/Requests/Client/StoreApplicationRequest.php:23-24`, `app/Http/Requests/Client/UpdateApplicationRequest.php:20-21`, `app/Livewire/ApplicationDetailsForm.php:121-122`); admin UI masks all but four trailing digits (`resources/views/admin/applications/show.blade.php:55-59`); encryption/masking tests at `tests/Feature/Applications/RegistrationPhaseTwoTest.php:143-167` and `tests/Feature/Admin/AdminOperationsUiTest.php:136-162`.
- **Threat:** Plaintext identity numbers in database, excessive admin exposure, or metadata leakage through filenames/logs.
- **Existing protection:** Laravel encrypted casts protect database values using `APP_KEY`; admin rendering masks NIK/KK; original names are encrypted; storage paths contain random server filenames rather than the original; audit calls reviewed do not include raw NIK/KK/document contents. Application forms necessarily hydrate the owner's values for editing, but owner scoping and Livewire authorization apply.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High for source behavior; production key custody/backup encryption are CP7 runtime items.
- **Recommendation:** Preserve encryption and masking. Restrict/rotate/back up `APP_KEY` through production secret management, avoid placing decrypted identifiers in logs/notifications, and document key-loss recovery implications.
- **Architecture impact:** None.

### CP4 conclusion

No public or cross-owner document disclosure path was established. The upload pipeline and identity-data protections are among the strongest controls in the repository. Production acceptance must still prove real malware scanning, filesystem permissions, retention scheduling, encrypted backups, and secret custody. The serve-enabled alias is a low-risk hardening opportunity because it broadens framework routing around the same private root, even though signed URLs are required and unused today.

## CP5 — Payment and webhook integrity

### SEC-018 — Payment amount, method, ownership, and browser-return controls

- **Route/action:** Client payment selection/display and fake-payment completion.
- **Source evidence:** `app/Http/Requests/Client/StorePaymentRequest.php:14-20`; `app/Http/Controllers/Client/PaymentController.php:22-58`, `app/Http/Controllers/Client/PaymentController.php:107-113`; `app/Services/ApplicationWorkflowService.php:187-274`; `app/Http/Controllers/Testing/FakePaymentController.php:17-86`; tests at `tests/Feature/Payments/PaymentPhaseTwoBTest.php:34-144`, `tests/Feature/Payments/PaymentPhaseTwoBTest.php:190-207`, `tests/Feature/Payments/PaymentPhaseTwoBTest.php:259-398`.
- **Threat:** Client tampers with price/method/status/QR data, opens another client's payment, replays payment creation, or invokes fake checkout in production.
- **Existing protection:** The amount and currency are copied from the application snapshot, not request fields. Payment method is allowlisted and unsupported PayPal fails without a fake transaction. Creation locks the application, reuses one unexpired pending payment, and rejects a second method. Browser query status/QR values do not mutate or replace server/provider state. Payment/application lookups are owner-scoped. Fake routes are registered and rechecked only in local/testing with the fake driver, then still require authenticated verified client and `PaymentPolicy`.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High
- **Recommendation:** Preserve server-authoritative amount/status, owner scoping, method allowlists, fake-mode double gating, and repeated-submit tests. Resolve the legacy fallback separately under SEC-003.
- **Architecture impact:** None.

### SEC-019 — Webhook authenticity and payment-state validation

- **Route/action:** `POST /webhooks/xendit`.
- **Source evidence:** Constant-time callback-token validation at `app/Http/Controllers/Webhooks/XenditWebhookController.php:27-33`; application/payment locks and amount/currency/external-ID/payer/channel validation at `app/Http/Controllers/Webhooks/XenditWebhookController.php:61-92`; transition allowlist and late-event handling at `app/Http/Controllers/Webhooks/XenditWebhookController.php:94-118`, `app/Http/Controllers/Webhooks/XenditWebhookController.php:149-207`; unique provider/event ledger key at `database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:218-229`; negative/idempotency tests at `tests/Feature/Webhooks/XenditWebhookTest.php:19-183` and `tests/Feature/Payments/PaymentPhaseTwoBTest.php:152-256`.
- **Threat:** Forged webhook marks arbitrary payment paid, amount/currency/reference substitution, replay/double notification, or late failure downgrades a paid payment.
- **Existing protection:** Blank/wrong callback tokens return 401 before payload persistence. Authenticated payloads must map to a known reference and match snapshot amount/currency plus available invoice/payer/channel identity. Application and payment are locked in cancellation-compatible order. Payment status transition rules prevent downgrading paid state. Browser return parameters are not a source of truth. The ledger has a database uniqueness constraint.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High for authenticity/field/state validation; retry semantics are separately vulnerable in SEC-020.
- **Recommendation:** Keep callback secrets outside source control, rotate them through provider/deployment procedures, preserve state/identity validation, and add provider-signature verification if/when Xendit's selected product exposes a stronger signed-webhook mechanism than the callback token.
- **Architecture impact:** None.

### SEC-020 — Existing webhook ledger rows suppress required retries

- **Route/action:** `POST /webhooks/xendit`, duplicate/retry path.
- **Source evidence:** `app/Http/Controllers/Webhooks/XenditWebhookController.php:35-40` returns `Event sudah diproses.` for any existing provider/event key without checking its `status`. The create-race catch repeats the same status-blind conclusion (`app/Http/Controllers/Webhooks/XenditWebhookController.php:42-58`). Processing only marks `PROCESSED` at lines 119-120; all thrown failures become `REJECTED` at lines 122-127. The ledger also permits a durable `RECEIVED` row before processing starts. Existing tests prove duplicate `PROCESSED` idempotence and that malformed input becomes `REJECTED`, but do not exercise retry of `RECEIVED`/transiently `REJECTED` rows (`tests/Feature/Webhooks/XenditWebhookTest.php:27-41`, `tests/Feature/Webhooks/XenditWebhookTest.php:172-183`).
- **Threat:** A process crash after ledger insertion, database deadlock, temporary dependency/notification failure, or other transient exception leaves `RECEIVED`/`REJECTED`. Xendit retries the same event ID; the application responds 200 “already processed” and never attempts payment state reconciliation. A legitimately paid client may remain `AWAITING_PAYMENT` indefinitely.
- **Existing protection:** Duplicate successful events do not double-apply. Operators can inspect ledger status/error class indirectly, and the provider/reference/payment records support manual reconciliation. These controls do not make automatic retry safe.
- **Status:** `CONFIRMED`
- **Severity:** `HIGH`
- **Confidence:** High
- **Recommendation:** Treat only `PROCESSED` as terminal idempotent success. Lock/fetch the ledger row, resume stale `RECEIVED`, and define retryable versus permanently rejected failures. Return a retryable non-2xx response for transient failures. Add tests for existing `RECEIVED`, retryable `REJECTED`, concurrent duplicates, and crash/recovery reconciliation.
- **Architecture impact:** Bounded webhook-ledger state-machine hardening; no provider or application-workflow redesign.

### SEC-021 — Provider call occurs inside a rollback-capable database transaction

- **Route/action:** Payment creation via Xendit.
- **Source evidence:** `ApplicationWorkflowService::createPayment` opens a database transaction and application lock at `app/Services/ApplicationWorkflowService.php:197-201`, creates the local payment/reference at lines 245-255, calls the external gateway before commit at lines 258-265, and may roll back on an exception/crash. Xendit uses the local reference as `idempotency-key` (`app/Services/XenditPaymentProvider.php:59-68`).
- **Threat:** Xendit successfully creates a payment but the PHP process/DB transaction fails before local commit. The local reference can be lost; a retry creates a new UUID reference/idempotency key and potentially a second provider payment. The client may pay an orphaned provider instruction whose later webhook cannot find a local reference.
- **Existing protection:** Normal repeated submits reuse the same locked pending payment; the same surviving reference makes provider calls idempotent; explicit gateway exceptions mark the local payment failed. The residual window is the external-success/local-rollback dual-write boundary.
- **Status:** `POTENTIAL`
- **Severity:** `HIGH`
- **Confidence:** Medium; requires process/DB failure at a narrow point and live-provider reconciliation to reproduce.
- **Recommendation:** Persist a stable pending payment/reference before the external call, call the provider with that durable idempotency key, and resume/reconcile incomplete checkout creation safely. Add a synthetic test for provider success followed by local persistence failure and an operational reconciliation command/report.
- **Architecture impact:** Bounded payment orchestration hardening within the existing gateway/service design.

### SEC-022 — Full provider/webhook payload retention lacks an explicit minimization/retention boundary

- **Route/action:** Payment checkout response persistence and Xendit webhook ledger/payment update.
- **Source evidence:** Full provider responses are stored as `payments.provider_payload` (`app/Services/ApplicationWorkflowService.php:259-265`), while every authenticated webhook payload is stored before semantic validation and may later replace `provider_payload` (`app/Http/Controllers/Webhooks/XenditWebhookController.php:35-49`, `app/Http/Controllers/Webhooks/XenditWebhookController.php:95-105`). Both columns are JSON and no payment/webhook retention command or encrypted cast is defined (`database/migrations/2026_08_26_000100_create_bantu_daftarin_domain_tables.php:202-229`, `app/Models/Payment.php:16-23`, `app/Models/WebhookEvent.php:9-16`). Callback-token headers are not included, and error logs contain only exception class/event ID.
- **Threat:** Provider payloads can contain payer identity or provider metadata beyond what the application needs; a database/backup exposure has a larger privacy footprint and indefinite retention.
- **Existing protection:** Database access is privileged; admin UI tests ensure raw `provider_payload` is not rendered; logs avoid raw payloads/secrets; payload retention provides useful forensic/idempotency evidence.
- **Status:** `DEFENSE_IN_DEPTH`
- **Severity:** `LOW`
- **Confidence:** High for source storage; exact live Xendit payload contents and regulatory retention requirements require production/domain review.
- **Recommendation:** Inventory actual production payload fields, retain a normalized allowlist plus necessary forensic hash/IDs where possible, document retention, and redact/encrypt sensitive fields if full payload retention is legally/operationally required.
- **Architecture impact:** Data-minimization/retention hardening; `[BUSINESS CONFIRMATION REQUIRED]` for retention period.

### CP5 conclusion

Payment amount/status authority, client ownership, fake-mode isolation, webhook authentication, and field/state validation are strong. The critical weakness is status-blind webhook deduplication: retries of incomplete or failed ledger entries are acknowledged without processing. The external-call/database-commit window is a second high-impact runtime risk that should be tested against real provider failure modes.

## CP6 — Livewire, chat, and notifications

### SEC-023 — Client eligibility middleware is not persisted on Livewire updates

- **Route/action:** Livewire updates from the authenticated client workspace, including application form mutations and chat actions.
- **Source evidence:** Initial client pages require `auth`, `verified`, and `client` (`routes/client.php:13-37`, `routes/web.php:53-58`). `EnsureClient` rejects inactive users and non-client roles (`app/Http/Middleware/EnsureClient.php:11-18`). Livewire's default persistent-middleware list contains authentication/authorization middleware but not this project's `EnsureClient`; the application does not call `Livewire::addPersistentMiddleware` (`vendor/livewire/livewire/src/Mechanisms/PersistentMiddleware/PersistentMiddleware.php:16-26`, scoped search of `app/` and `bootstrap/`). Component checks authorize ownership/role but not `is_active` or current email-verification state (`app/Livewire/ApplicationDetailsForm.php:49`, `app/Livewire/ApplicationDetailsForm.php:91`, `app/Livewire/RegistrationDetailsForm.php:28`, `app/Livewire/RegistrationDetailsForm.php:69`, `app/Livewire/ChatThread.php:253-275`; policy predicates at `app/Policies/ApplicationPolicy.php:10-39`).
- **Threat:** A client who loaded a component while eligible can retain the signed Livewire snapshot. If the account is subsequently deactivated (or verification is administratively revoked), authenticated Livewire requests can still read or mutate that client's own application/chat data until the session ends. Snapshot signing prevents forging another component state, and ownership checks prevent cross-client access, but revocation is not immediate.
- **Existing protection:** Livewire persists authentication, component snapshots are integrity-protected, application/thread queries remain owner-scoped, and normal full-page requests re-run `verified`/`client` middleware. Presence heartbeat independently checks active user status.
- **Status:** `CONFIRMED`
- **Severity:** `HIGH`
- **Confidence:** High from Livewire 4 middleware source and application registration.
- **Recommendation:** Register `EnsureClient` and `EnsureAdmin` as Livewire persistent middleware (or enforce the same eligibility predicate in a shared component lifecycle hook) and add tests that deactivate a client/admin after mount then submit the existing snapshot. Preserve per-object policies as a second layer.
- **Architecture impact:** Bounded authorization-lifecycle hardening within existing Livewire architecture. This is the client counterpart to SEC-006.

### SEC-024 — Chat write actions have no server-side abuse-rate boundary

- **Route/action:** `ChatThread::send`, `ChatThread::typing`, Livewire polling, and `POST /presence/heartbeat`.
- **Source evidence:** Message send validates required/string/max 2,000 and serializes thread writes, then creates an audit row, database notification, and delayed-email opportunity (`app/Livewire/ChatThread.php:52-85`). Typing writes a cache key on component updates (`app/Livewire/ChatThread.php:87-105`). The heartbeat route has authentication and a controller-level active-role check but no throttle (`routes/web.php:48-51`, `app/Http/Controllers/PresenceHeartbeatController.php:12-25`). Registered rate limiters cover login, OTP, OTP resend, and webhook only (`app/Providers/AppServiceProvider.php:55-58`).
- **Threat:** An authenticated participant can automate many Livewire send/typing/poll requests or heartbeat writes, growing messages/audit/notification work and consuming database/cache/queue capacity. Email coalescing limits email volume per recipient/thread but does not bound message, audit, database-notification, or request volume.
- **Existing protection:** Authentication and thread ownership/role checks prevent anonymous/cross-thread writes; message length is bounded; thread locks preserve consistency; email notifications coalesce for 60 seconds; the UI itself polls/heartbeats at controlled intervals.
- **Status:** `DEFENSE_IN_DEPTH`
- **Severity:** `MEDIUM`
- **Confidence:** High that no application-level limiter exists; infrastructure/WAF limits were not available.
- **Recommendation:** Apply per-user/per-thread limits inside mutating Livewire methods (especially `send`) and a named route throttle to heartbeat. Return normal validation/rate-limit feedback without altering polling, typing, presence, unread, or delivery semantics. Monitor queue/message growth.
- **Architecture impact:** Targeted abuse-resistance controls only.

### SEC-025 — Queued authentication secrets are encrypted at serialization boundaries

- **Route/action:** Login OTP and password-reset notification dispatch.
- **Source evidence:** `LoginOtpNotification` stores only `encryptedCode` and decrypts inside `toMail` (`app/Notifications/LoginOtpNotification.php:16-42`). `PasswordResetNotification` stores only `encryptedToken` and decrypts when constructing the reset URL (`app/Notifications/PasswordResetNotification.php:17-32`). Both implement `ShouldQueue` and request after-commit dispatch. Tests assert the password-reset token does not appear in the serialized notification and all outbound notifications/jobs use queued after-commit delivery (`tests/Feature/Authentication/OtpTest.php:287-299`, `tests/Feature/Notifications/OutboundNotificationQueuePolicyTest.php:17-36`).
- **Threat:** Plaintext OTP/reset tokens leaking from serialized queue jobs or failed-job payloads.
- **Existing protection:** Application-key encryption before serialization, short-lived/single-use server-side OTP/reset semantics, queued after-commit delivery, and regression tests.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High for application queue payloads. Mail transport/provider/log configuration is a CP7 runtime concern.
- **Recommendation:** Preserve encrypted properties and tests; protect `APP_KEY`, queue storage, failed-job storage, and mail transport in production. Do not log rendered mail bodies.
- **Architecture impact:** None.

### SEC-026 — Chat notifier and unread-email delivery remain recipient/thread scoped

- **Route/action:** Global Livewire chat notifier, unread counts/toasts, and delayed chat unread email.
- **Source evidence:** Client unread queries constrain threads by `client_user_id`; admin queries accept only client-sender messages (`app/Livewire/GlobalChatNotifier.php:92-106`). Toast URLs are role-specific thread routes and do not contain private message bodies beyond the already-visible in-app preview (`app/Livewire/GlobalChatNotifier.php:139-166`). Chat email scheduling locks the unique thread/user state, uses a random pending token, dispatches after commit, re-locks/re-fetches the intended thread/recipient, and rechecks canonical unread incoming messages before sending (`app/Services/ChatUnreadEmailService.php:25-99`). The queued job contains numeric thread/recipient IDs plus the pending token, not message content (`app/Jobs/SendChatUnreadEmail.php:14-27`). The email provides an authenticated role-specific deep link and explicitly omits message content (`app/Notifications/ChatUnreadNotification.php:25-45`). Cross-client notifier isolation and exact authorized deep-link behavior are tested (`tests/Feature/Chat/GlobalChatNotifierTest.php:181-210`, `tests/Feature/Chat/ChatUnreadEmailDeliveryTest.php:175-203`).
- **Threat:** Cross-client toast leakage, message-body leakage through email/queue, stale unread email after reading, or role-confused deep links.
- **Existing protection:** Recipient-scoped queries, thread ownership checks on destination, no message body in email/job, pending-token coalescing, unread-at-execution recheck, and after-commit delivery.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High
- **Recommendation:** Preserve recipient scoping, body omission, unread recheck, token coalescing, route authorization, and cross-client tests. Address eligibility revocation through SEC-023 without changing notifier/polling behavior.
- **Architecture impact:** None.

### CP6 conclusion

No cross-client chat/notifier disclosure or plaintext authentication secret in queued notification state was established. Chat ownership, unread-email coalescing, and role-specific deep links are strong. The important Livewire weakness is eligibility revocation: authentication persists, but this project's active/verified role middleware does not. Authenticated chat writes also need bounded abuse resistance; neither issue requires a chat redesign.

## CP7 — Production posture, browser controls, and dependencies

### SEC-027 — Effective production hardening cannot be proven from repository defaults

- **Route/action:** Entire deployed web application and background workers.
- **Source evidence:** Repository defaults/examples are development-oriented (`.env.example:2-5`, `.env.example:39-59`), while the operations runbook requires production HTTPS, `APP_DEBUG=false`, secure cookies, private storage, least-privilege PostgreSQL, queue worker, scheduler, encrypted backups, monitoring, and non-disclosing error handling (`docs/08-operations/runbook.md:3-23`). Session cookies default secure when `APP_ENV=production`, HttpOnly, and SameSite Lax (`config/session.php:172-202`). The local audit runtime is explicitly `local` with debug enabled; effective local drivers are PostgreSQL, SMTP, database queue/session, and file cache. No production host/configuration was available.
- **Threat:** A deployment that retains local/example settings can expose stack traces, permit HTTP session transport, fail to send security emails, fail to execute delayed notification/purge work, or leave sensitive files/backups with inappropriate permissions.
- **Existing protection:** Safe production expectations are documented; framework defaults switch secure cookies on for production; private/quarantine storage is explicit; application exceptions avoid raw payment details; queue and scheduled purge implementations exist.
- **Status:** `NEEDS_RUNTIME_VERIFICATION`
- **Severity:** `HIGH`
- **Confidence:** High that runtime proof is absent; no claim is made that production is misconfigured.
- **Recommendation:** Make deployment fail closed on `APP_ENV`, `APP_DEBUG`, HTTPS/trusted-proxy handling, secure cookie attributes, production mailer, queue worker health, scheduler freshness, ClamAV driver/health, private-directory permissions, retention configuration, and required Xendit secrets. Capture redacted effective-config and health evidence per release.
- **Architecture impact:** Deployment validation/observability only.

### SEC-028 — Log mailer would write rendered OTP/reset content to application logs

- **Route/action:** Login OTP, password reset, verification, chat unread, and application email notifications.
- **Source evidence:** The mail configuration defaults to `log` if `MAIL_MAILER` is absent (`config/mail.php:17`), and `.env.example` explicitly uses it for local development (`.env.example:59`). The manual checklist and operations runbook warn that rendered OTP/email contents can be written to `storage/logs/laravel.log` and prohibit treating the log mailer as a production substitute (`docs/07-testing/manual-acceptance-checklist.md:37-45`, `docs/08-operations/runbook.md:21-23`). The effective local audit driver was SMTP, not log.
- **Threat:** If a shared/staging/production environment uses the log mailer, anyone with log access could read active OTPs, reset links, verification links, and other email content. Encryption of queued notification properties does not protect the rendered mail body after the job executes.
- **Existing protection:** Current local runtime is SMTP; secrets are encrypted while serialized in queue payloads; documentation explicitly warns against log-based OTP inspection; OTP/reset links expire and are single-use or broker-controlled.
- **Status:** `NEEDS_RUNTIME_VERIFICATION`
- **Severity:** `HIGH`
- **Confidence:** High for behavior; production mailer/log ACLs were unavailable.
- **Recommendation:** Reject `MAIL_MAILER=log` in production startup/deployment checks, use an authenticated TLS mail transport, restrict/rotate logs, and verify logs do not contain synthetic OTP/reset-link fixtures after acceptance tests.
- **Architecture impact:** Configuration guard only.

### SEC-029 — Browser policy is useful but weakened by `unsafe-inline` and has no application HSTS header

- **Route/action:** All web responses through `SecurityHeaders`.
- **Source evidence:** Middleware sets `nosniff`, `DENY`, strict-origin referrer policy, a restrictive permissions policy, and CSP frame/object/base/form restrictions (`app/Http/Middleware/SecurityHeaders.php:11-19`). The same CSP permits both `script-src 'unsafe-inline'` and `style-src 'unsafe-inline'` (`app/Http/Middleware/SecurityHeaders.php:17`). Active inline scripts exist in OTP/application views (`resources/views/auth/otp.blade.php:55`, `resources/views/client/registration/personal.blade.php:137`, `resources/views/client/applications/create.blade.php:150`, `resources/views/client/applications/show.blade.php:336`). No application `Strict-Transport-Security` header is set. Header presence is tested, but exact policy strength/HSTS is not (`tests/Feature/Security/SecurityRegressionTest.php:80-87`).
- **Threat:** If an HTML/script injection is introduced later, `unsafe-inline` materially reduces CSP's ability to block execution. Without HSTS at the application or TLS terminator, a first HTTP visit can be downgrade-prone.
- **Existing protection:** No active unsafe output sink was found in CP1; Blade escapes user content; framing, plugins, base URI, and form destinations are constrained; production HTTPS is an explicit runbook requirement. HSTS may be applied by an unavailable reverse proxy/CDN.
- **Status:** `DEFENSE_IN_DEPTH`
- **Severity:** `MEDIUM`
- **Confidence:** High for application headers; runtime edge headers require verification.
- **Recommendation:** Move inline behavior to bundled JavaScript or introduce per-response nonces compatible with Livewire/Alpine, then remove `unsafe-inline` from scripts (and styles when feasible). Enforce HSTS at the single authoritative TLS layer after HTTPS/subdomain readiness, and add exact security-header tests.
- **Architecture impact:** Incremental browser-policy hardening; no frontend framework change.

### SEC-030 — No tracked production secret/private key was identified

- **Route/action:** Source-control and seed/configuration boundary.
- **Source evidence:** `.gitignore` excludes `.env`, environment variants, storage keys, private/quarantine files, and common credential files. `git ls-files` showed only `.env.example` among environment files. The example leaves application, database, mail, AWS, Xendit, and admin-seed secrets blank/null (`.env.example:3`, `.env.example:32`, `.env.example:64-90`). The seeder refuses a blank or shorter-than-16-character admin password and hashes it (`database/seeders/DatabaseSeeder.php:19-31`). A tracked-source pattern scan found no private key, populated APP key, populated Xendit secret/callback token, or populated mail password; the only token-like hit was an explicitly local fake callback example in documentation.
- **Threat:** Credential disclosure or a predictable built-in administrator password.
- **Existing protection:** Secret files are ignored, examples are unpopulated, README instructs local secret provisioning, and seeding fails closed on weak/missing admin credentials.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High for current tracked files; git history, CI secret stores, host environment, and remote repositories were not inspected.
- **Recommendation:** Preserve blank examples/fail-closed seeding; add CI secret scanning for commits/history; use managed production secrets and least-privilege rotation. Never print effective secrets in diagnostics.
- **Architecture impact:** None.

### SEC-031 — Dependency advisory checks are clean at audit time

- **Route/action:** PHP and production JavaScript dependency supply chain.
- **Source evidence:** Locked manifests exist (`composer.lock`, `package-lock.json`); `composer audit --locked` reported no security vulnerability advisories and `npm audit --omit=dev` reported zero vulnerabilities on 2026-09-08. Runtime dependencies are declared in `composer.json:8-19` and `package.json:9-18`; the frontend uses locally bundled Inter rather than a third-party runtime font request.
- **Threat:** Known vulnerable dependency versions or untracked dependency drift.
- **Existing protection:** Lockfiles, deterministic build inputs, successful production build, and both ecosystem audit commands.
- **Status:** `NOT_VULNERABLE`
- **Severity:** `INFO`
- **Confidence:** High for advisory databases available during this audit; advisories can change after the audit date.
- **Recommendation:** Run both audits in CI/deployment, review lockfile changes, and establish prompt patching for Laravel, Livewire, Xendit SDK, Vite, and Axios advisories. Treat a clean advisory scan as one control, not proof of exploit absence.
- **Architecture impact:** None.

### SEC-032 — Operational security controls require production evidence

- **Route/action:** Queue delivery, scheduled retention purge, malware scanning, PostgreSQL/backup/storage/logging operations.
- **Source evidence:** Database queue and failed-job persistence are configured (`config/queue.php:16-41`, `config/queue.php:101-112`); `files:purge` is scheduled daily (`routes/console.php:3-5`) and physically deletes then audits eligible records (`app/Console/Commands/PurgeExpiredFiles.php:13-40`). Production uploads require numeric retention configuration (`app/Services/DocumentFileService.php:78-94`, `app/Services/AdminWorkflowService.php:133-153`). The runbook requires worker, scheduler, monitoring, least-privilege DB, encrypted backup, and private storage but marks external email/DNS readiness unresolved (`docs/08-operations/runbook.md:3-23`). The repository status document also marks production SMTP, ClamAV, storage, monitoring, backup, and queue deployment as blocked on operational configuration (`docs/IMPLEMENTATION_STATUS.md:98-102`).
- **Threat:** Jobs silently accumulate, unread/security emails are delayed, expired sensitive documents are not purged, backups expose encrypted-at-rest database values together with keys, or filesystem/DB privileges exceed the application need.
- **Existing protection:** Implementations and failure tables exist; notification jobs use after-commit; uploads fail closed if scanner/retention requirements are unavailable; runbook specifies least privilege and encrypted backups.
- **Status:** `NEEDS_RUNTIME_VERIFICATION`
- **Severity:** `MEDIUM`
- **Confidence:** High that repository controls exist and external proof is unavailable.
- **Recommendation:** Verify worker heartbeat/lag and failed-job alerts; scheduler freshness and dry-run purge metrics; ClamAV availability/signatures; storage ownership/no-web-root mapping; PostgreSQL grants/TLS/network exposure; encrypted backup restore; separation of backup ciphertext and `APP_KEY`; log ACL/rotation/redaction; and alerting for repeated webhook/OTP/auth failures.
- **Architecture impact:** Operations acceptance and monitoring only.

### CP7 conclusion

No tracked production credential or known locked dependency advisory was identified. Browser headers and development/operations documentation show good intent, but production posture remains unverified outside this local host. The highest-priority deployment checks are effective debug/HTTPS/cookie settings, a non-log mail transport, real malware scanning, worker/scheduler health, private storage permissions, least-privilege PostgreSQL, and recoverable encrypted backups. CSP tightening and HSTS are defense-in-depth tasks that should follow compatibility testing.

## CP8 — Final synthesis

## Findings index

The detailed evidence for SEC-001 through SEC-032 is recorded under CP1–CP7. The following supplement completes the per-finding operational metadata without repeating sensitive excerpts.

| ID | Category | Attack prerequisite / safe reproduction | Potential impact | Expected fix scope | Behavior change |
|---|---|---|---|---|---|
| SEC-001 | Input/XSS | Store harmless HTML markers in synthetic text fields; render as each role | None established | NONE | NO |
| SEC-002 | Injection | Submit SQL-like synthetic IDs/search against test DB | None established | NONE | NO |
| SEC-003 | Request integrity | Authenticated owner posts invalid method to legacy payment route | Unintended BCA selection | SMALL | MINIMAL: reject invalid input |
| SEC-004 | Auth/OTP | Synthetic expired/replayed/wrong-type OTP tests | None established | NONE | NO |
| SEC-005 | Auth/privacy | Compare known/unknown synthetic reset-email responses | Account enumeration | SMALL | MINIMAL: neutral response |
| SEC-006 | Authorization/Livewire | Mount as active admin, deactivate in test DB, reuse snapshot | Revoked admin retains operational reads/actions | SMALL | MINIMAL: immediate revocation |
| SEC-007 | Authorization/IDOR | Swap synthetic public IDs between two test clients | None established | NONE | NO |
| SEC-008 | Session/CSRF | Submit synthetic state changes without token; inspect cookie flags | None established in source | NONE | NO |
| SEC-009 | State integrity | Repeat synthetic transitions/uploads/chat actions | None established on protected paths | NONE | NO |
| SEC-010 | Race/integrity | Barrier-synchronize conflicting result verify and complete in test DB | Completed application without verified result | MODERATE | MINIMAL: serialize/recheck |
| SEC-011 | Race/integrity | Concurrent synthetic begin-review by two admins | Assignee/history actor mismatch | SMALL | MINIMAL: move write under lock |
| SEC-012 | Duplicate submission | Replay create request concurrently for same client/service | Duplicate active drafts | MODERATE; business invariant confirmation | MINIMAL if invariant approved |
| SEC-013 | Auth/race | Concurrent synthetic OTP issue/resend requests | Multiple valid/sent challenges | SMALL | MINIMAL: serialize issuance |
| SEC-014 | File security | Synthetic spoofed signatures, active PDFs, malware/unavailable scanner, cross-owner IDs | None established | NONE | NO |
| SEC-015 | Private file surface | Request unsigned/path-traversal local storage URLs | No current disclosure; future policy bypass surface | SMALL | NO intended behavior change |
| SEC-016 | Malware operations | Verify production scanner with isolated clean/EICAR fixtures | Testing scanner misuse or upload outage | SMALL deployment guard | NO domain change |
| SEC-017 | Sensitive identity data | Inspect synthetic DB/UI/queue/log representations | None established beyond owner's intended form state | NONE | NO |
| SEC-018 | Payment tampering | Change amount/method/owner/browser-return fields in fake/test flow | None established | NONE | NO |
| SEC-019 | Webhook authenticity | Synthetic wrong token/reference/amount/currency/state payloads | None established for validated events | NONE | NO |
| SEC-020 | Webhook idempotency | Seed same event ID as `RECEIVED`/`REJECTED`, retry locally | Paid payment can remain unreconciled | MODERATE | MINIMAL: retry state semantics |
| SEC-021 | Payment dual write | Fake provider success then inject local commit/process failure | Orphan/duplicate provider payment | MODERATE | MINIMAL orchestration hardening |
| SEC-022 | Data minimization | Inspect synthetic provider payload fields/retention | Enlarged DB/backup privacy footprint | MODERATE; retention confirmation | MINIMAL data retention change |
| SEC-023 | Authorization/Livewire | Mount as active client, deactivate/revoke verification, reuse snapshot | Revoked client retains own workspace mutations | SMALL | MINIMAL: immediate revocation |
| SEC-024 | Abuse resistance | Burst synthetic authenticated send/typing/heartbeat requests | DB/cache/queue/notification resource consumption | SMALL | MINIMAL: rate-limit feedback |
| SEC-025 | Queue secret handling | Serialize synthetic OTP/reset notifications and search for plaintext | None established | NONE | NO |
| SEC-026 | Chat privacy/integrity | Cross-client synthetic notifier and read-before-job tests | None established | NONE | NO |
| SEC-027 | Production configuration | Redacted production effective-config/HTTPS/worker review | Debug/session/storage/service exposure if misconfigured | SMALL deployment checks | NO product change |
| SEC-028 | Mail/log privacy | Send synthetic OTP via production-like worker and inspect authorized logs | OTP/reset-link disclosure to log readers | SMALL | NO UI/domain change |
| SEC-029 | Browser hardening | Inspect response headers and CSP console in authenticated browser | Weaker mitigation for a future injection; HTTP first-visit downgrade | MODERATE | MINIMAL compatibility work |
| SEC-030 | Secret management | Tracked-file/history and redacted secret-store review | None found in current tracked tree | NONE now; SMALL CI guard | NO |
| SEC-031 | Supply chain | Locked Composer/npm advisory scans | None known at audit time | NONE | NO |
| SEC-032 | Operations | Verify production worker/scheduler/scanner/storage/DB/backup health | Delayed jobs, stale files, or operational data exposure | MODERATE operations work | NO domain change |

## Severity Summary

### By severity

| Severity | Count |
|---|---:|
| CRITICAL | 0 |
| HIGH | 7 |
| MEDIUM | 7 |
| LOW | 4 |
| INFO | 14 |
| **Total** | **32** |

### By status

| Status | Count |
|---|---:|
| CONFIRMED | 4 |
| POTENTIAL | 5 |
| NEEDS_RUNTIME_VERIFICATION | 4 |
| NOT_VULNERABLE | 14 |
| DEFENSE_IN_DEPTH | 5 |
| **Total** | **32** |

Confirmed vulnerabilities are SEC-005 (MEDIUM), SEC-006 (HIGH), SEC-020 (HIGH), and SEC-023 (HIGH). HIGH counts also include two plausible concurrency/dual-write failures (SEC-010, SEC-021) and two deployment configurations that require explicit production proof (SEC-027, SEC-028). There are no confirmed CRITICAL findings.

## Authorization / IDOR Matrix

| Resource/action | Client owner | Other client | Active admin | Unauthenticated | Protection / residual risk |
|---|---|---|---|---|---|
| Application view | Allowed | Not found/forbidden | Allowed on admin route | Login required | Owner-scoped query + policy; Livewire revocation gap SEC-023 |
| Application update/submit/cancel | Allowed only in valid states | Not found/forbidden | No client-route mutation; separate admin actions | Login required | Owner scope + policy + state services |
| Document upload/delete | Allowed only for owned application and valid state | Not found/forbidden | Separate admin review access | Login required | Parent/requirement binding + `DocumentPolicy` |
| Document stream | Owned, scanned, active, non-deleted | Forbidden/not found | Policy-authorized | Login required | Private disk + policy controller; signed alias hardening SEC-015 |
| Result download | Owned and `VERIFIED` only | Forbidden/not found | Policy-authorized | Login required | `ResultDocumentPolicy` + private streaming |
| Payment view/create | Owned application/payment only | Not found/forbidden | Operational read through admin application view | Login required | Owner scope + `PaymentPolicy` + server snapshot amount |
| Chat thread/read actions | Thread-owning client | Forbidden | Allowed by admin role | Login required | Per-call ownership/role checks; Livewire revocation gaps SEC-006/023 |
| Admin HTML actions | Forbidden | Forbidden | Allowed only while user/admin profile active | Login required | `EnsureAdmin` + policies/actions |
| Admin Livewire actions | Cannot mount/admin role check fails | Cannot mount/admin role check fails | Allowed; deactivation after mount not rechecked | Login required | Snapshot/auth/role protect; SEC-006 |

Conclusion: changing a public ID did not yield a practical cross-client application, document, result, payment, or chat access path. Public IDs are identifiers, while owner queries/policies remain authoritative. The confirmed Livewire issues concern immediate eligibility revocation, not cross-user IDOR or role forgery.

## Duplicate / Race-condition Matrix

| Operation | UI guard | Server guard | DB guard | Race tested | Risk |
|---|---|---|---|---|---|
| Create application | Normal submit flow | Validation + transaction | Detail/chat uniqueness per new application; no client/service idempotency | Repeated behavior, not parallel invariant | SEC-012 MEDIUM potential duplicate drafts |
| Upload document | Auto/manual upload state | Eligibility + deep file validation | Application/requirement locks; version uniqueness | Sequential negatives; no barrier test | Protected |
| Submit revision | State-aware CTA | Transition graph + completeness checks | Application lock/history | Sequential | Protected |
| Cancel application | Confirmation UI | Cancellation service rechecks state/payment | Application/payment locks | Sequential late-payment cases | Protected |
| Create payment | Disabled/unavailable options | Readiness/method checks; reuse pending payment | Application/pending-payment lock | Repeated submit tested; provider failure window not | SEC-021 HIGH potential dual write |
| Payment webhook | None (provider ingress) | Token, identity, amount/currency, status transition | Event uniqueness + event/payment/application locks | Duplicate processed tested; incomplete retry not | SEC-020 HIGH confirmed retry suppression |
| Admin document decision | Stage-aware action | Pending/current-document checks | Document + application locks | Sequential | Protected |
| Verify result / complete | Stage-aware forms | Sequential eligibility/verified-result checks | No common result/application lock or terminal invariant | No concurrent conflict test | SEC-010 HIGH potential invalid terminal state |
| Create GENERAL_SUPPORT | Existing thread link | `firstOrCreate` after client lock | Client row lock; thread relation | Repeated flow covered | Protected |
| Send chat | Composer prevents blank send | Auth/ownership/body length; thread transaction | Thread lock and message PK; no request idempotency/rate limit | Functional, not burst/concurrent | Ordinary duplicate send possible; SEC-024 abuse risk |

## Input / XSS Matrix

| Input | Stored? | Render surfaces | Escaping | JS context? | Finding |
|---|---|---|---|---|---|
| Client name | Yes | Client/admin headers, dashboard, queues, chat sender/toast title | Escaped Blade; server string later rendered through escaped templates | Initial/avatar text only; no unsafe HTML sink found | SEC-001 protected |
| Business name | Yes | Workspace and admin application detail | Escaped Blade | No unsafe JS interpolation found | SEC-001 protected |
| `business_type_other` | Yes, conditionally | Owner form/workspace and admin detail | Escaped Blade; max 128 and prohibited unless `OTHER` | Livewire owner state, no HTML execution sink | SEC-001 protected |
| Cancellation reason/detail | Yes in history/audit context | Client/admin history | Escaped Blade; enum/conditional max-length validation | No unsafe JS sink; UI script only toggles required/visibility | SEC-001 protected |
| Chat message/edit | Yes | Thread bubble and in-app toast preview | Escaped Blade; max 2,000; toast remains Livewire-rendered text | No body in notification email; no `innerHTML` sink | SEC-001/026 protected |

No user-controlled input was found reaching executable HTML/JavaScript or SQL syntax on the reviewed active paths. This conclusion assumes future changes preserve escaped Blade/text-only DOM assignment.

## Existing Protections Confirmed

- Framework CSRF on browser and Livewire mutations; only the provider webhook is exempt and has independent callback-token authentication plus throttling.
- Password hashing, email verification signatures, OTP random generation/hash/expiry/attempt lock/single use, named authentication throttles, and session regeneration/invalidation.
- Owner-scoped queries and policies for applications, documents, results, payments, and chat; negative cross-owner tests.
- Escaped Blade, `textContent`, no reviewed dangerous DOM sink, bounded text validation, Eloquent parameter binding, enum/conditional request rules, and guarded server-authoritative fields.
- PostgreSQL transactions, row locks, transition graph checks, immutable histories/audits, and targeted uniqueness constraints across core workflows.
- Server-authoritative service price, payment state, provider identity/amount/currency matching, late-cancellation behavior, and no admin route to manually mark provider payment paid.
- Private/quarantine disks, random filenames, MIME/signature/parser/active-PDF validation, fail-closed malware scan, policy-gated streaming, active document versioning, and verified-result gating.
- NIK/KK encrypted database casts, masked admin display, encrypted original filenames, and no raw identity/document content in reviewed logs or notifications. The authenticated owner necessarily receives their own form values in Livewire state; this is intended owner-visible data, not a cross-user disclosure.
- Chat ownership, message-management eligibility locks, per-user archive state, unread recheck, email coalescing, body-free chat email, and role-specific authenticated deep links.
- Secret files ignored, blank example credentials, fail-closed admin seed password, lockfiles, and clean Composer/npm advisory scans.
- Clickjacking, MIME sniffing, referrer, permissions, object, base-URI, and form-action browser protections are present.

## Highest Priority Risks

1. **SEC-020 — webhook retries can be acknowledged but never processed (CONFIRMED HIGH).** Fix first because provider truth and application payment state can diverge indefinitely.
2. **SEC-006 and SEC-023 — Livewire does not immediately honor admin/client deactivation or verification revocation (CONFIRMED HIGH).** Persist the existing eligibility middleware and test post-mount revocation.
3. **SEC-010 — result verification/completion concurrency can violate the verified-result terminal invariant (POTENTIAL HIGH).** Serialize result/application checks and add a deterministic concurrency test.
4. **SEC-021 — provider success before local commit can create an orphan/duplicate payment (POTENTIAL HIGH).** Make the provider idempotency reference durable and resumable.
5. **SEC-005 — password-reset responses enumerate accounts (CONFIRMED MEDIUM).** Normalize the public response.
6. **SEC-011 — review assignment can diverge from the transition actor (POTENTIAL MEDIUM).** Move assignment inside the authoritative transaction/lock.
7. **SEC-012 — replayed creation can produce duplicate active drafts (POTENTIAL MEDIUM).** Confirm the one-active-draft business invariant, then add bounded idempotency/constraint handling.
8. **SEC-024 — chat writes lack an application-side abuse limit (DEFENSE_IN_DEPTH MEDIUM).** Rate-limit mutating actions without changing chat semantics.
9. **SEC-013 — concurrent OTP issue/resend is not atomic (POTENTIAL LOW).** Serialize issuance after higher-impact items.

Production blockers SEC-027/028 and scanner/operations checks SEC-016/032 are not ranked as confirmed code vulnerabilities, but must be closed before declaring a production deployment secure.

## Defense-in-depth Findings

- SEC-003: validate or retire the redundant payment endpoint's silent BCA fallback.
- SEC-015: stop the generic serve-enabled disk from sharing the private document root.
- SEC-022: minimize and define retention for provider/webhook payloads.
- SEC-024: add server-side chat/presence abuse limits.
- SEC-029: remove CSP `unsafe-inline` through nonce/bundling work and establish HSTS at the TLS authority.

These items are not presented as proven XSS, private-file disclosure, payment tampering, or authentication bypasses.

## Runtime Verification Still Required

| Runtime check | Why source is insufficient | Expected evidence |
|---|---|---|
| Livewire revocation after mount | Requires real/synthetic snapshot request after DB eligibility change | Inactive admin/client and unverified client receive 403 without data/action |
| Result verification vs completion | Requires controlled PostgreSQL concurrency | No completed application can lack a verified primary result |
| Begin-review assignment race | Requires two concurrent admin transactions | Assignee and transition actor remain consistent |
| Draft replay | Business invariant plus concurrent requests required | Approved invariant enforced without accidental duplicate active draft |
| OTP issue/resend race | Requires concurrent session/user requests | At most one current challenge and intended notification count |
| Webhook `RECEIVED`/`REJECTED` retry | Requires synthetic ledger states/failure injection | Retry resumes safely; only processed event is terminal success |
| Provider success/local failure | Requires fake-provider success plus injected process/commit failure | Durable reference is reconciled; no second external invoice |
| ClamAV | Binary/daemon/signatures absent locally | Production health check plus isolated clean/malicious fixtures |
| HTTPS/cookies/headers | Edge proxy/CDN not available | HTTPS redirect, Secure/HttpOnly/SameSite cookies, HSTS at one layer, exact CSP |
| Mail/queue/scheduler | Production services not available | Non-log TLS mailer, worker lag/failed-job alerts, scheduled purge freshness |
| PostgreSQL/storage/backups/logs | Host/ACL/backup systems unavailable | Least-privilege grants, network/TLS policy, private paths, encrypted restore test, key separation, redacted logs |
| Xendit production boundary | External provider must not be attacked from audit | Authorized provider configuration review and controlled sandbox webhook/reconciliation evidence |

No browser-authenticated two-session or production Network/Console test was claimed in this audit. Automated feature tests and local configuration inspection are evidence for source behavior, not a substitute for the runtime checklist above.

## Explicit Security Questions Answered

- **Can user-controlled form input execute JavaScript anywhere?** No practical path was established. Active rendering uses escaped Blade or `textContent`; keep rich HTML unsupported unless separately sanitized.
- **Is there stored or reflected XSS?** None established. Synthetic chat script content is covered by an existing escaping regression test. CSP weakening in SEC-029 is defense-in-depth, not evidence of XSS.
- **Can chat messages or toast previews execute HTML/JS?** No; chat is escaped text and toast preview is rendered through Livewire/escaped Blade. Email intentionally omits the body.
- **Is SQL injection practically possible?** None found. Request values use Eloquent/query binding; reviewed raw expressions are static.
- **Can hidden/request fields manipulate server-authoritative data?** No price, owner, role, payment status, result status, or application transition bypass was established. SEC-003 is a limited invalid-method fallback on a legacy owner-only route.
- **Are all browser state changes CSRF-protected?** Yes for reviewed web/Livewire mutations. Email verification is an intentional expiring signed GET; the Xendit webhook is CSRF-exempt but independently authenticated and throttled.
- **Can Client A access Client B application, document, result, payment, or chat?** No practical IDOR path was established through ID substitution; owner scoping and policies reject it.
- **Can a non-admin invoke admin actions directly?** A client/unauthenticated actor cannot mount or call reviewed admin actions. An already authenticated admin whose account is later deactivated is the SEC-006 exception.
- **Can Livewire public properties/methods bypass authorization?** Snapshot integrity, authentication, owner/role checks prevent arbitrary cross-role/object access. However, project-specific active/verified middleware is not persisted, causing SEC-006/023.
- **Can OTP be brute-forced, replayed, or reused?** Attempt locks, route throttles, expiry, hash storage, row locking, type/user binding, and single-use state materially control this. Concurrent issuance/resend remains SEC-013.
- **Is OTP resend abuse reasonably controlled?** Yes under normal sequential use (cooldown plus 3/min named throttle); atomicity under concurrency is not proven.
- **Is session fixation mitigated?** Yes; successful OTP login regenerates the session and logout invalidates it/regenerates CSRF state.
- **Can duplicate submission or concurrent requests corrupt business state?** Most transitions/uploads/cancellation/chat management lock and revalidate. Duplicate drafts (SEC-012), result verify/complete (SEC-010), review assignment (SEC-011), provider/local dual write (SEC-021), and OTP issuance (SEC-013) remain.
- **Can duplicate webhooks double-process payment?** A duplicate already-processed event does not double-apply due uniqueness/locks/transitions. The inverse failure is confirmed: an incomplete/rejected event can be incorrectly acknowledged and never retried (SEC-020).
- **Can payment amount, service, user, or browser return state be tampered with?** No practical bypass was established; amount/currency/reference/payer/channel are server/provider-authoritative and ownership is scoped.
- **Can a cancelled application be reopened by late payment?** No. Provider payment truth may be recorded, but transition rules keep the application cancelled and make no refund assumption.
- **Can malicious files be uploaded?** Content can reach quarantine, but promotion requires extension/MIME/signature/parser checks and a clean scanner result; scanner failure rejects the upload. No detector guarantees every malicious file, so production ClamAV effectiveness remains SEC-016.
- **Can uploaded content execute in a browser?** No active path was established: files are private, policy streamed, images parsed, and active PDF markers rejected. Preserve restrictive response headers and scanning.
- **Can private files be accessed through a direct/public URL?** No unsigned or cross-owner path was established. Application delivery uses policies; the unused serve-enabled alias sharing the root is SEC-015 defense-in-depth.
- **Are NIK/KK exposed in logs, queue payloads, JavaScript, or UI?** No unintended cross-user/log/queue/admin exposure was found. They are encrypted in the database and masked for admin. The authenticated owner receives their own values in public Livewire form state so they can edit them; treat any future third-party script/XSS as sensitive because owner-visible browser state cannot be secret from that owner session.
- **Are secrets tracked or exposed?** No populated secret/private key was found in current tracked files. Production environment, history, CI, backups, and secret-store custody still require review. A log mailer would expose rendered security-email content (SEC-028).
- **Are production debug/test routes safely controlled?** Fake payment routes require local/testing plus fake driver, authenticated verified client, and policy checks. No other unsafe state-changing test/debug route was identified. Effective production debug/config remains SEC-027.
- **Which controls are strongest?** Private document pipeline, owner/policy IDOR controls, OTP verification/session rotation, transactional state transitions, server-authoritative payment validation, verified-result access gate, chat recipient scoping, and queued-secret encryption.
- **Which findings need no architecture change?** All proposed fixes are bounded within current middleware/services/configuration/operations. No authentication, payment, chat, database, or frontend architecture replacement is required.
- **Which items are defense-in-depth only?** SEC-003, SEC-015, SEC-022, SEC-024, and SEC-029. SEC-016/027/028/032 are deployment/runtime verification items, not confirmed application vulnerabilities.

## Proposed Security Hardening Phases

### Security Hardening A — Confirmed high-impact integrity and revocation

1. SEC-020: correct webhook ledger retry state semantics and add recovery tests.
2. SEC-006/023: persist existing admin/client eligibility middleware for Livewire and test post-mount deactivation/revocation.
3. SEC-005: normalize password-reset request responses.

These are SMALL to MODERATE changes inside existing controllers/middleware/Livewire registration and do not redesign authentication or payments.

### Security Hardening B — Concurrency and external dual-write

1. SEC-010: serialize result verification/completion and enforce/recheck the terminal invariant.
2. SEC-021: make the external payment reference durable/resumable across local failure.
3. SEC-011/012/013: serialize assignment/OTP issuance and, after business confirmation, make active-draft creation idempotent.

Use deterministic PostgreSQL concurrency/failure-injection tests. Do not change the application state graph, gateway, or OTP mechanism.

### Security Hardening C — Abuse resistance and data minimization

Apply targeted chat/presence rate limits (SEC-024), legacy payment validation parity (SEC-003), provider-payload retention/minimization after legal/business confirmation (SEC-022), and the storage-alias boundary (SEC-015).

### Security Hardening D — Production and browser acceptance

Close SEC-016/027/028/032 with deployment gates and evidence; tighten CSP/HSTS under SEC-029 after Livewire/Alpine compatibility testing; automate dependency and secret scans. This is configuration/operations hardening, not product redesign.

## Final verification

Executed on 2026-09-08 after completing the audit artifact:

- `php artisan optimize:clear`: PASS.
- `php artisan view:cache`: PASS.
- `npm run build`: PASS — Vite 6.4.3, 57 modules transformed.
- `php artisan test --no-coverage`: PASS — 269 tests, 1,674 assertions, 82.11 seconds.
- `vendor/bin/pint --test`: PASS.
- `git diff --check`: PASS (no output).
- `composer audit --locked`: no security vulnerability advisories found.
- `npm audit --omit=dev`: 0 vulnerabilities.
- Final `git status --short`: only `?? docs/audit/security-audit.md` before this final documentation update.

No production source, configuration, route, test, dependency, migration, schema, database data, or temporary exploit file was intentionally changed. Browser/two-session concurrency, live ClamAV, live Xendit, and production host/edge checks were not executed and are not claimed; they remain in the runtime-verification table.

## Final Classification

**BANTUDAFTARIN SECURITY AUDIT COMPLETE WITH CONFIRMED HIGH-RISK FINDINGS**

Reason: the audit is complete and found confirmed HIGH-risk integrity/authorization issues (SEC-006, SEC-020, SEC-023), alongside runtime verification requirements. No fixes were implemented in accordance with the audit-only stop condition.
