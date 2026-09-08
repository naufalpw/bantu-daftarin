# UI Copy Rewrite — Phase E

Date: 2026-09-08

Scope: Accessibility semantics, runtime locale, format consistency, residual terminology and punctuation

Audit source: `docs/audit/ui-copy-audit.md`

Previous checkpoints: Phase A, Phase B, Phase C, Phase D

Production source modified: YES if required

Domain logic modified: NO

Chat behavior modified: NO

Email behavior modified: NO

Migration: NO expected

Dependencies: NO expected

## Progress

| Checkpoint | Status | Implemented | Kept | Deferred | Tests |
|---|---|---:|---:|---:|---|
| CP0 Baseline | COMPLETE | 0 | 6 | 1 | Baseline: 267 tests, 1,664 assertions; Vite build passed |
| CP1 Accessibility | COMPLETE | 1 | 4 | 0 | ChatTest accessibility regression passed; screen-reader runtime not available |
| CP2 Runtime locale | COMPLETE | 1 | 0 | 0 | PasswordResetLocaleTest passed; runtime config reports `id` |
| CP3 Formats | COMPLETE | 0 | 3 | 0 | Representative format checks documented; no semantic formatting change |
| CP4 Terminology/punctuation | COMPLETE | 7 | 2 | 1 | Scoped residual scan and targeted regression tests passed |
| CP5 Final regression | COMPLETE | 0 | 0 | 0 | 269 tests, 1,672 assertions; build/Pint/audits passed |

## CP0 — Baseline + target extraction

The worktree was already dirty before Phase E. Existing changes were preserved.
The current baseline passed with 267 tests and 1,664 assertions; `npm run build`
also passed.

### Phase E target classification

| Audit ID | Area | Original decision | Phase E classification |
|---|---|---|---|
| P6-013 | Chat read-receipt accessibility | ACCESSIBILITY_GAP | IMPLEMENT |
| P6-016 | Password broker status translation | NEEDS_CONTEXT | IMPLEMENT, with runtime verification |
| P6-018 | Admin/client date and time formats | FORMAT_CONSISTENCY | KEEP / DOCUMENT |
| P6-019 | Currency and number formats | KEEP | KEEP / DOCUMENT |
| P2-014 | Client activity date/time | FORMAT_CONSISTENCY | KEEP / DOCUMENT |
| P1-013 | Help search ellipsis | FORMAT_CONSISTENCY | ALREADY_RESOLVED_BY_PHASE_B |
| P5-020 | Admin provider terminology | NEEDS_CONTEXT | DEFER |
| Residual `Logout` | Shared admin navigation | TERMINOLOGY_FIX | IMPLEMENT |
| Residual `reset` in password recovery UI | Auth copy | TERMINOLOGY_FIX | IMPLEMENT |
| Residual `Trasnsaksi` typo | Client activity | COPY_FIX | IMPLEMENT |

### Protected prior-phase decisions

Phase A–D approved copy, workflow, authorization, payment truth, result
verification, document privacy, chat behavior, notifier/presence/polling, and
transactional email behavior are not reopened in this pass.

## CP1 — Accessibility semantics

Status: COMPLETE

### P6-013

Status: IMPLEMENTED

File: `resources/views/livewire/chat-thread.blade.php`

Original audit line: 64-66.

Current implementation location: `resources/views/livewire/chat-thread.blade.php:64-66`.

Original state: `<img ... alt="" aria-label="Dibaca">` and `<img ... alt="" aria-label="Terkirim">`.

New state: each decorative check icon is `alt="" aria-hidden="true"` inside a
`<span role="img" aria-label="Dibaca">` or `<span role="img" aria-label="Terkirim">`.

Reason: expose exactly one reliable accessible read/send status while keeping
the icon visual and message/read behavior unchanged.

Behavior changed: NO

Risk checked: `read_at`, check-icon rendering, polling, message ownership,
counterpart-read locking, and edit/delete eligibility are unchanged.

### Accessibility items kept

- P6-002 skip link `Lewati ke konten utama`.
- P6-012 presence wording `Sedang tidak aktif`.
- P6-017 logo alt `Bantu Daftarin` and decorative image alts.
- Existing icon-only menu, close, search-clear, archive, and message-action
  controls retain concise accessible names.

Screen-reader runtime verification remains a manual acceptance item; no
screen-reader tool was available in this environment.

## CP2 — Runtime translations / locale

Status: COMPLETE

### P6-016

Status: IMPLEMENTED

Files: `config/app.php`, `.env.example`, local `.env`,
`lang/id/passwords.php`, `tests/Feature/Authentication/PasswordResetLocaleTest.php`.

Original audit line: `app/Http/Controllers/Auth/PasswordResetController.php:26,42`.

Current implementation location: `app/Http/Controllers/Auth/PasswordResetController.php:26,42`
continues to use `__($status)`; the project-owned translation source is now
`lang/id/passwords.php`.

Original state: `__($status)` with no project-owned Indonesian password-broker
translation.

New state: the `passwords.reset`, `sent`, `throttled`, `token`, and `user` keys
resolve to concise Indonesian messages while the controller and broker status
values remain unchanged.

Reason: remove framework-default English leakage without hard-coding status
messages in the controller.

Behavior changed: NO

Risk checked: anti-enumeration wording remains generic, reset token handling,
broker throttling, token expiry, and successful reset behavior are unchanged.

The active local environment now uses `APP_LOCALE=id`; `APP_FALLBACK_LOCALE=en`
remains available for framework keys that are outside this targeted password
translation scope.

## CP3 — Date, time, currency, and number formats

Status: COMPLETE

### P6-018 and P2-014

Status: KEEP / DOCUMENT

The current formats are retained by context:

- date-only metadata: `d M Y`;
- full history/detail timestamps: `d M Y, H:i`;
- recent dashboard/inbox activity: `d M, H:i` when the surrounding heading
  establishes a recent-activity context;
- cancellation and other important history: full year with `d F Y, H:i`;
- chat message times: `H:i`;
- all times remain 24-hour and use the configured application timezone.

This preserves the deliberate distinction between recent operational scanning
and permanent history/detail records. No timestamp or timezone semantics were
changed.

### P6-019

Status: KEEP / DOCUMENT

Active monetary displays consistently use the backend currency code followed by
Indonesian thousands separators, for example `IDR 150.000`. The `Rp` graphic
used on the activity/payment surface is a category marker, not a replacement
for the rendered amount. Ordinary localized numbers use `.` as the thousands
separator; IDs, NIK/KK, phone numbers, tokens, and references remain unchanged.

Risk checked: payment amounts, currency source-of-truth, reference identifiers,
and timestamps are unchanged.

### P1-013

Status: ALREADY_RESOLVED_BY_PHASE_B

The active help placeholder already uses the typographic ellipsis:
`Cari pertanyaan, mis. dokumen, pembayaran, revisi…`. No duplicate punctuation
change was made.

## CP4 — Residual terminology and punctuation

Status: COMPLETE

The residual scan was limited to active Phase E surfaces and directly related
shared presenters. No global search-and-replace was used.

### Implemented residual fixes

| Finding | Current source | Result |
|---|---|---|
| P6-008 | `app/Http/Controllers/Client/ApplicationController.php:132,141` | Flash feedback now says `Data pengajuan tersimpan.` and `Pengajuan masuk ke tahap pengumpulan dokumen.` |
| P2-011 | `resources/views/client/activity/index.blade.php:55,80`; `resources/views/client/activity/show.blade.php:39` | Defensive service fallbacks now say `Layanan pengajuan`. |
| Residual terminology | `app/Services/ApplicationWorkflowService.php:88,172` | Domain exceptions visible through the application flow now use `pengajuan` without changing state rules. |
| Residual terminology | `app/Services/DocumentWorkflowService.php:30,85,92` | User-facing document exceptions now use `pengajuan` and `diperiksa`; review authorization and state checks are unchanged. |
| Residual auth copy | `resources/views/auth/forgot-password.blade.php:6,10` | `reset` wording is now Indonesian and remains a password-reset instruction. |
| Residual shared navigation | `resources/views/components/admin/sidebar.blade.php:43` | `Logout` is now `Keluar`. |
| Residual typo | `resources/views/client/activity/index.blade.php:17` | `Status Trasnsaksi` corrected to `Status Transaksi`. |

### Kept, deferred, or out of scope

- `P5-020` (`provider`) remains **DEFERRED — NEEDS_CONTEXT**. It is an
  operational admin term for provider-sourced payment truth; replacing it by
  preference could weaken meaning.
- `aplikasi` in technical context such as `di luar aplikasi`, internal model
  names, route names, and framework/search keywords remains **VALID** or
  **OUT_OF_SCOPE**. It does not refer to a client service request in those
  locations.
- `private` in storage/service internals and help-search keywords remains
  **OUT_OF_SCOPE**; no backend authorization wording was changed.
- Legacy registration markup and fake-payment test views remain untouched and
  are not active product surfaces for this pass.
- `P1-013` was already resolved in Phase B; no duplicate punctuation edit was
  made.

Behavior regression checks passed for the targeted admin, chat, and password
locale tests. Filter keys, routes, state transitions, document authorization,
chat behavior, and email behavior were not changed.

## CP5 — Final verification

Status: COMPLETE

The final command set is being run after the source and checkpoint review:

- `php artisan optimize:clear`
- `php artisan view:cache`
- `npm run build`
- `php artisan test --no-coverage`
- `vendor/bin/pint --test`
- `git diff --check`
- `npm audit --omit=dev`
- `composer audit --locked`

Browser and screen-reader checks are not available in this environment and are
tracked as manual acceptance items rather than claimed as complete.

### Executed results

- `php artisan optimize:clear` — passed.
- `php artisan view:cache` — passed.
- `npm run build` — passed; Vite transformed 57 modules.
- `php artisan test --no-coverage` — **269 passed, 1,672 assertions**.
- `vendor/bin/pint --test` — passed.
- `git diff --check` — passed.
- `npm audit --omit=dev` — 0 vulnerabilities.
- `composer audit --locked` — no security vulnerability advisories.
- `php artisan config:show app` — active locale `id`, fallback `en`, timezone
  `Asia/Jakarta`.

## Final Phase E Status

Implemented audit IDs: `P6-013`, `P6-016`, `P6-008`, `P2-011`, plus the scoped
residual `Logout`, password-reset wording, and `Trasnsaksi` typo fixes.

Kept/documented audit IDs: `P6-018`, `P6-019`, `P2-014`, `P1-013`, and the
protected P6 accessibility/format items that already matched the product
conventions.

Deferred audit IDs: `P5-020` (`provider`) remains
`DEFERRED — NEEDS_CONTEXT`.

Files changed for Phase E:

- `resources/views/livewire/chat-thread.blade.php`
- `config/app.php`
- `.env.example` (locale setting only for this phase; existing database edits
  in the dirty worktree are unrelated)
- local ignored `.env` (locale setting only)
- `lang/id/passwords.php`
- `resources/views/components/admin/sidebar.blade.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/client/activity/index.blade.php`
- `resources/views/client/activity/show.blade.php`
- `app/Http/Controllers/Client/ApplicationController.php`
- `app/Services/ApplicationWorkflowService.php`
- `app/Services/DocumentWorkflowService.php`
- `tests/Feature/Authentication/PasswordResetLocaleTest.php`
- `tests/Feature/Chat/ChatTest.php` (accessibility regression coverage)
- `tests/Feature/Admin/AdminPhaseBTest.php` (updated intentional visible-copy
  assertion)
- `docs/audit/ui-copy-rewrite-phase-e.md`

Production behavior changed: NO. The changes are wording, locale resources,
and accessibility semantics only.

Domain, payment, document authorization, application state machine, reactive
admin filtering, chat behavior, notifier/presence/polling, and transactional
email behavior changed: NO.

Database/schema changed: NO. Migration: NO. Dependencies: NO.

### Manual acceptance still required

- Run an authenticated browser pass on password reset, client activity, admin
  logout, and the affected service/document errors.
- Use a screen reader or accessibility tree to confirm each outgoing chat
  receipt exposes one status (`Dibaca` or `Terkirim`) without duplicate or
  decorative announcements.
- Check locale-sensitive date/time and currency displays at the supported
  responsive widths; this environment did not provide browser/device tooling.

## Final BantuDaftarin UI copy system

### Voice

- Ramah, bukan cerewet.
- Profesional, bukan birokratis.
- Jelas, bukan terlalu menjelaskan.
- Tenang, bukan dingin.
- Aktif, bukan marketing.
- Natural dalam Bahasa Indonesia.

Client copy may be slightly warmer; admin copy remains compact and operational.
Security, payment, privacy, and state-boundary copy stays precise and does not
promise more than the implementation guarantees.

### Terminology

Use `pengajuan`, `Dokumen`, `Pembayaran`, `Hasil`, `Bantuan`, `Pusat Bantuan`,
and admin-facing `Dukungan` according to context. Use `tinjau`/`diperiksa`
instead of unnecessary English `review` in visible Indonesian copy. Keep
`provider` when it identifies the authoritative external payment source and
the admin context requires that distinction. Do not rename internal classes,
routes, enum values, or database identifiers.

### Accessibility

Decorative images use empty alt text plus `aria-hidden="true"`. Functional or
status icons expose one concise accessible name. Existing skip links, logo alt,
presence wording, icon-only controls, and heading structure remain protected.
Screen-reader runtime verification is still a manual acceptance item.

### Date, time, currency, and numbers

- Recent operational activity: abbreviated date/time such as `d M, H:i` when
  the section heading supplies the year context.
- History/detail records: `d M Y, H:i`; date-only metadata: `d M Y`.
- Important cancellation/history records may use the full month form `d F Y,
  H:i`.
- Chat times remain `H:i`; all times use the configured `Asia/Jakarta`
  timezone.
- Monetary values retain the backend currency code and Indonesian separators,
  for example `IDR 150.000`. The `Rp` activity icon is a category marker, not
  a second amount representation.
- IDs, NIK/KK, phone numbers, tokens, and references are not localized as
  ordinary numbers.

## Audit implementation mapping

| Audit ID | Original decision | Phase E status | Current source |
|---|---|---|---|
| P6-013 | ACCESSIBILITY_GAP | IMPLEMENTED | `resources/views/livewire/chat-thread.blade.php:64-66` |
| P6-016 | NEEDS_CONTEXT | IMPLEMENTED | `lang/id/passwords.php:3-7`; `config/app.php:81` |
| P6-018 | FORMAT_CONSISTENCY | KEEP / DOCUMENTED | `resources/views/admin/dashboard.blade.php:59,96`; related client/admin formatters |
| P6-019 | KEEP | KEEP / DOCUMENTED | `resources/views/client/services/index.blade.php:36`; payment/activity displays |
| P2-014 | FORMAT_CONSISTENCY | KEEP / DOCUMENTED | `resources/views/client/activity/index.blade.php:32,58,85` |
| P1-013 | FORMAT_CONSISTENCY | ALREADY RESOLVED IN PHASE B | `resources/views/qna.blade.php` |
| P5-020 | NEEDS_CONTEXT | DEFERRED | `resources/views/admin/applications/show.blade.php:41` |
| P6-008 | TERMINOLOGY_FIX | IMPLEMENTED | `app/Http/Controllers/Client/ApplicationController.php:132,141` |
| P2-011 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/client/activity/index.blade.php:55,80`; `resources/views/client/activity/show.blade.php:39` |

## Protected behavior and copy confirmed unchanged

- Application transitions, payment gating, cancellation consequences, verified
  result access, document version/authorization rules, and conditional
  requirements remain unchanged.
- Chat unread/read state, archive/unarchive, edit/delete eligibility,
  polling, typing, presence, Enter/Shift+Enter behavior, autoscroll, toast
  suppression, notifier lifecycle, deep links, and recipient rules remain
  unchanged.
- Transactional email Phase 1/2 templates, delivery/coalescing, queues,
  subjects, preheaders, and notification routing remain unchanged.
- Protected copy such as payment gates, private-result wording, cancellation
  boundaries, presence labels, archive actions, status labels, and useful
  empty states remains intact.

## Final classification

**UI COPY SYSTEM PHASE E COMPLETE WITH MANUAL ACCESSIBILITY/BROWSER ACCEPTANCE ITEMS**
