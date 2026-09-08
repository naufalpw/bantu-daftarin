# UI Copy Rewrite — Phase D

Date: 2026-09-08

Scope: Client/admin chat, support inbox, quick replies, toast and conversation copy

Audit source: `docs/audit/ui-copy-audit.md`

Previous checkpoints: Phase A, Phase B, Phase C

Production copy modified: YES

Chat behavior modified: NO

Email delivery modified: NO

Domain logic modified: NO

Migration: NO expected

Dependencies: NO expected

## Progress

| Checkpoint | Status | Implemented | Kept | Deferred | Tests |
|---|---|---:|---:|---:|---|
| CP0 Baseline + target extraction | COMPLETE | 0 | 7 | 0 | Baseline: 267 tests, 1,660 assertions; Vite build passed |
| CP1 Chat shell | COMPLETE | 3 | 4 | 0 | ChatTest, AdminOperationsUiTest, GlobalChatNotifierTest: 33 tests, 191 assertions |
| CP2 Quick replies | COMPLETE | 1 | 1 | 0 | ChatTest: 12 tests, 60 assertions |
| CP3 Inbox/toast | COMPLETE | 2 | 3 | 0 | AdminOperationsUiTest + GlobalChatNotifierTest: 21 tests, 131 assertions |
| CP4 Regression | COMPLETE | 0 | 0 | 0 | Chat/support regression set: 67 tests, 375 assertions |
| CP5 Final | COMPLETE | 7 | 7 | 0 | Full suite: 267 tests, 1,664 assertions; build, Pint, diff, and audits passed |

## CP0 — Baseline + target extraction

The pre-Phase-D worktree was already dirty from earlier product phases. No
existing changes were reverted. The baseline test suite passed with 267 tests
and 1,660 assertions, and `npm run build` passed.

### Phase D target mapping

| Audit ID | Area | Original decision | Phase D target |
|---|---|---|---|
| P4-001 | Chat navigation | TERMINOLOGY_FIX | IMPLEMENT |
| P4-002 | Thread context | NEEDS_CONTEXT | IMPLEMENT with context preserved |
| P4-003 | Empty states | REWRITE | IMPLEMENT |
| P4-004 | Presence accessibility label | KEEP | KEEP |
| P4-005 | Message actions/deletion consequence | KEEP | KEEP |
| P4-006 | Admin quick replies | SHORTEN | IMPLEMENT |
| P4-007 | Quick-reply labels | KEEP | KEEP |
| P4-008 | Toast conversation CTA | TERMINOLOGY_FIX | IMPLEMENT |
| P4-009 | Toast preview/privacy | SECURITY_REVIEW | KEEP / SECURITY-PROTECTED |
| P4-010 | Support inbox intro | SHORTEN | IMPLEMENT |
| P4-011 | Admin support description | SHORTEN | IMPLEMENT |
| P4-012 | Archive controls | KEEP | KEEP |
| P4-013 | Support empty states | KEEP | KEEP |
| P4-014 | Presence status labels | KEEP | KEEP |

P4-009 is intentionally protected because its preview is user-generated
message content, not static product copy. P6-013 accessibility semantics,
global translations, and other format cleanup remain out of scope.

## CP1 — Chat shell + empty states

Status: COMPLETE

### P4-001

Status: IMPLEMENTED

File: `resources/views/chat/show.blade.php`

Original audit line: 12, 28; current implementation: lines 4, 6, 12, 28, 31.

Original copy: `"Kembali ke dukungan"` / `"Chat Pengajuan"` / `"Chat pengajuan"`

New copy: `"Kembali ke Dukungan"` / `"Bantuan Pengajuan"` / `"Bantuan pengajuan"`

Reason: Align the admin destination name and client application-support label
with the established `Dukungan` and `Bantuan pengajuan` vocabulary while
preserving distinct back destinations.

Chat behavior changed: NO

Risk checked: Routes, back-link destinations, and general/application thread
branches are unchanged.

### P4-002

Status: IMPLEMENTED

Files: `resources/views/chat/show.blade.php`,
`resources/views/livewire/admin/support-inbox.blade.php`

Original audit line: `resources/views/chat/show.blade.php:15-17,31-33`.

Current implementation: `resources/views/chat/show.blade.php:15-17,31-33`;
`resources/views/livewire/admin/support-inbox.blade.php:31`.

Original copy: `"BANTUAN UMUM"` / `"DUKUNGAN PENGAJUAN"` /
`"Percakapan tanpa konteks pengajuan."` / `"Chat pengajuan"` /
`"Percakapan dengan Admin"` / `"Tanya tentang {{ $thread->application->service->name }}"` /
`'Percakapan ini hanya terkait pengajuan dengan ID …'.strtoupper(substr($thread->application->public_id, -4)).'.'`

New copy: `"BANTUAN UMUM"` / `"DUKUNGAN PENGAJUAN"` /
`"Percakapan umum."` / `"Bantuan pengajuan"` /
`"Percakapan dengan Tim Bantu Daftarin"` / `"Tanya tentang {{ $thread->application->service->name }}"` /
`'Gunakan percakapan ini untuk pertanyaan umum.'` /
`'Percakapan ini terkait pengajuan dengan ID …'.strtoupper(substr($thread->application->public_id, -4)).'.'`

Reason: Replace implementation-style phrasing with concise context labels while
keeping GENERAL_SUPPORT separate from APPLICATION and retaining the safe public
application reference.

Chat behavior changed: NO

Risk checked: No enum, query, authorization, thread route, or application
context mapping changed.

### P4-003

Status: IMPLEMENTED

File: `resources/views/livewire/chat-thread.blade.php`

Original audit line: 84-94; current implementation: lines 84-94.

Original copy: `"Belum ada percakapan"` /
`"Percakapan ini terkait dengan pengajuan berikut."` /
`"Mulai percakapan jika Anda membutuhkan bantuan yang tidak terkait dengan satu pengajuan."` /
`"Gunakan chat ini untuk pertanyaan terkait pengajuan NPWP Anda."`

New copy: `"Belum ada percakapan"` /
`"Gunakan percakapan ini untuk membahas pengajuan ini."` /
`"Mulai percakapan jika Anda membutuhkan bantuan umum."` /
`"Gunakan percakapan ini untuk pertanyaan tentang pengajuan NPWP Anda."`

Reason: Give each empty state one clear purpose and next action without adding
customer-service filler. The existing admin general empty-state sentence was
kept because it already provides an appropriate operational action.

Chat behavior changed: NO

Risk checked: Empty-state rendering branches, composer, polling, and message
actions are unchanged.

### CP1 protected copy

P4-004 presence accessibility text, P4-005 edit/delete actions and deletion
consequence, P4-012 archive controls, P4-013 support empty-state titles and
useful descriptions, and P4-014 presence labels were preserved. P4-009 dynamic
toast previews remain security-protected user content.

## CP2 — Quick replies

Status: COMPLETE

### P4-006

Status: IMPLEMENTED

File: `app/Support/ChatQuickReplyPresenter.php`

Original audit line: 14, 19-24; current implementation: lines 14, 19-24.

Original copy: `"Terima kasih telah menghubungi Tim Bantu Daftarin. Mohon jelaskan kendala yang Anda alami agar kami dapat membantu."`

New copy: `"Terima kasih sudah menghubungi kami. Ceritakan kendala yang Anda alami."`

Reason: Keep the welcome and request for context, but remove call-center
scaffolding and the redundant promise to help.

Chat behavior changed: NO

Risk checked: The `general-information` key and general-support branch are
unchanged.

Original copy: `"Dokumen pada pengajuan Anda belum lengkap. Silakan periksa kembali dokumen yang diperlukan pada halaman pengajuan."`

New copy: `"Dokumen Anda belum lengkap. Periksa kembali dokumen yang masih diperlukan pada pengajuan."`

Reason: State the missing-document condition and one concrete next action.

Original copy: `"Silakan unggah ulang dokumen melalui bagian Dokumen pada halaman pengajuan agar dapat kami periksa kembali."`

New copy: `"Dokumen perlu diunggah ulang. Anda dapat menggantinya dari bagian Dokumen pada pengajuan."`

Reason: Preserve the upload destination while removing the formal “agar dapat”
construction.

Original copy: `"Dokumen Anda sudah kami terima dan sedang dalam proses pemeriksaan."`

New copy: `"Dokumen Anda sudah kami terima dan sedang diperiksa."`

Reason: Shorten the state without changing its meaning.

Original copy: `"Pembayaran untuk pengajuan Anda telah diterima. Silakan pantau perkembangan selanjutnya melalui halaman pengajuan."`

New copy: `"Pembayaran pengajuan Anda sudah diterima. Perkembangan berikutnya dapat dilihat di ruang pengajuan."`

Reason: Preserve provider-authoritative payment truth and point to the existing
workspace without promising timing.

Original copy: `"Pengajuan Anda masih dalam proses. Perkembangan terbaru dapat dipantau melalui halaman pengajuan."`

New copy: `"Pengajuan Anda masih diproses. Perkembangan terbaru dapat dilihat di ruang pengajuan."`

Reason: Use a direct state description and the established client workspace
term instead of an abstract monitoring instruction.

Original copy: `"Hasil pengajuan Anda sudah tersedia. Silakan buka halaman pengajuan untuk melihat hasil yang telah diverifikasi."`

New copy: `"Hasil pengajuan Anda sudah tersedia. Buka ruang pengajuan untuk melihat hasil yang telah diverifikasi."`

Reason: Keep the VERIFIED-result boundary and replace the longer navigation
phrase with the established destination wording.

Risk checked: Template keys, labels, insertion behavior, sending, payment
truth, process status, and VERIFIED-result semantics are unchanged.

### P4-007

Status: KEEP

File: `app/Support/ChatQuickReplyPresenter.php:31-38`.

Original copy: `"Informasi bantuan umum"` / `"Dokumen belum lengkap"` /
`"Unggah ulang dokumen"` / `"Dokumen sedang diperiksa"` /
`"Pembayaran diterima"` / `"Proses masih berlangsung"` / `"Hasil tersedia"`

New copy: unchanged.

Reason: Labels are short and continue to map unambiguously to the same template
keys.

Chat behavior changed: NO

Risk checked: Select values and insertion behavior are unchanged.

## CP3 — Support inbox + toast

Status: COMPLETE

### P4-008

Status: IMPLEMENTED

File: `app/Livewire/GlobalChatNotifier.php:165`.

Original audit line: 155-166; current implementation: line 165.

Original copy: `"Buka chat"`

New copy: `"Buka percakapan"`

Reason: Use one concrete Indonesian destination label for the same client and
admin conversation route.

Chat behavior changed: NO

Risk checked: The role-specific route, toast suppression, unread count, and
notifier lifecycle are unchanged.

### P4-010

Status: IMPLEMENTED

File: `resources/views/livewire/admin/support-inbox.blade.php:3`.

Original audit line: 3.

Original copy: `"Pesan belum dibaca diprioritaskan pada daftar ini."`

New copy: `"Pesan yang belum dibaca tampil lebih dulu."`

Reason: State the existing ordering rule directly and compactly.

Chat behavior changed: NO

Risk checked: The Livewire query and unread ordering are unchanged.

### P4-011

Status: IMPLEMENTED

File: `resources/views/admin/support/index.blade.php:6`.

Original audit line: 6.

Original copy: `"Kelola percakapan Bantuan Umum dan Dukungan Pengajuan."`

New copy: `"Bantuan Umum dan Dukungan Pengajuan dalam satu daftar."`

Reason: Name the two inbox contexts without adding generic management framing.

Chat behavior changed: NO

Risk checked: The page route, filters, component boundary, and general/application
thread distinction are unchanged.

### P4-009

Status: KEEP

File: `resources/views/livewire/global-chat-notifier.blade.php:16-18`.

Original copy: `"{{ $toast['count'] > 1 ? $toast['count'].' pesan baru' : 'Anda menerima pesan baru.' }}"` / `"{{ $toast['preview'] }}"`

New copy: unchanged.

Reason: The summary is already concise. The preview is user-generated content
and remains governed by the existing privacy/security behavior.

Chat behavior changed: NO

Risk checked: No preview filtering, exposure scope, or toast lifecycle changed.

### P4-012 and P4-013

Status: KEEP

File: `resources/views/livewire/admin/support-inbox.blade.php:38-50`.

Original copy: `"Tindakan percakapan"` / `"Keluarkan dari arsip"` /
`"Arsipkan"` / `"Percakapan tidak ditemukan"` /
`"Tidak ada pesan belum dibaca"` / `"Arsip percakapan kosong"` /
`"Belum ada percakapan"`

New copy: unchanged.

Reason: These labels are factual, concise, and explain archive/search/filter
states without decorative language.

Chat behavior changed: NO

Risk checked: Archive/unarchive semantics and empty-state query branches are
unchanged.

## CP4 — Cross-context terminology + behavior regression

Status: COMPLETE

### Scoped terminology scan

- `Buka chat`: FIXED at the notifier root to `Buka percakapan`.
- `Chat Pengajuan` / `Chat pengajuan`: FIXED in the client chat title/context
  to `Bantuan Pengajuan` / `Bantuan pengajuan`.
- `Percakapan tanpa konteks pengajuan.`: FIXED to `Percakapan umum.` in admin
  general-support context.
- `Silakan`, `agar`, and `pantau`: removed from the repetitive quick-reply
  sentences; remaining occurrences outside the changed templates are VALID or
  OUT_OF_SCOPE.
- `Admin`: retained in technical/admin-facing contexts; client general-support
  heading now uses `Tim Bantu Daftarin`.
- `FAQ`, `QnA`, `aplikasi`, `workflow`, and `lifecycle`: no new visible
  occurrences were introduced in the changed chat/support surfaces. Existing
  help-source or backend occurrences are OUT_OF_SCOPE for Phase D.
- `chat` remains in technical CSS/route identifiers and in the protected
  accessibility boundary; no broad replacement was used.

### Protected behavior confirmed unchanged

The chat regression suite passed without changes to unread semantics, per-thread
badges, mark-read behavior, archive/unarchive rules, edit/delete eligibility,
message tombstones, polling, typing, Enter/Shift+Enter handling, IME handling,
auto-scroll, presence heartbeat/thresholds, notifier suppression, toast
lifecycle, assigned-admin rules, recipient rules, email coalescing, or
authenticated chat deep links.

The chat email delivery implementation and Phase 1/2 transactional email
templates remain untouched.

### Out of scope

P6-013 read-icon accessibility semantics remain DEFERRED to Phase E. Dynamic
message previews remain SECURITY-PROTECTED. Framework translations, global
date/number formatting, global punctuation, and broad chat terminology cleanup
remain out of scope.

## CP5 — Final verification

Status: COMPLETE

### Final Phase D Status

Implemented audit IDs: P4-001, P4-002, P4-003, P4-006, P4-008, P4-010,
P4-011.

Kept audit IDs: P4-004, P4-005, P4-007, P4-009, P4-012, P4-013, P4-014.

Deferred audit IDs: none. P6-013 remains deferred to Phase E and is not a
Phase D implementation target.

Out-of-scope findings: chat read-icon accessibility semantics, framework
translations, global date/time/number/punctuation conventions, and broad
terminology cleanup outside the active chat/support surfaces.

Quick replies changed: 7 templates; template IDs and select values are
unchanged.

Files changed for Phase D:

- `resources/views/chat/show.blade.php`
- `resources/views/livewire/chat-thread.blade.php`
- `resources/views/livewire/admin/support-inbox.blade.php`
- `resources/views/admin/support/index.blade.php`
- `app/Support/ChatQuickReplyPresenter.php`
- `app/Livewire/GlobalChatNotifier.php`
- `tests/Feature/Chat/ChatTest.php`
- `tests/Feature/Chat/GlobalChatNotifierTest.php`
- `tests/Feature/Admin/AdminOperationsUiTest.php`
- `tests/Feature/Help/HelpCenterTest.php`
- `docs/audit/ui-copy-rewrite-phase-d.md`

Chat behavior changed: NO

Email delivery changed: NO

Domain logic changed: NO

Migration: NO

Dependencies: NO

Tests: `php artisan test --no-coverage` passed with 267 tests and 1,664
assertions. The focused chat/support regression set passed with 67 tests and
375 assertions.

Manual browser QA: NOT RUN in this execution. The remaining acceptance items
are listed below.

### Important copy changes

- `Terima kasih telah menghubungi Tim Bantu Daftarin. Mohon jelaskan kendala yang Anda alami agar kami dapat membantu.` → `Terima kasih sudah menghubungi kami. Ceritakan kendala yang Anda alami.`
- `Dokumen pada pengajuan Anda belum lengkap. Silakan periksa kembali dokumen yang diperlukan pada halaman pengajuan.` → `Dokumen Anda belum lengkap. Periksa kembali dokumen yang masih diperlukan pada pengajuan.`
- `Pembayaran untuk pengajuan Anda telah diterima. Silakan pantau perkembangan selanjutnya melalui halaman pengajuan.` → `Pembayaran pengajuan Anda sudah diterima. Perkembangan berikutnya dapat dilihat di ruang pengajuan.`
- `Buka chat` → `Buka percakapan`.
- `Kelola percakapan Bantuan Umum dan Dukungan Pengajuan.` → `Bantuan Umum dan Dukungan Pengajuan dalam satu daftar.`

### Audit implementation mapping

| Audit ID | Original decision | Phase D status | Current source |
|---|---|---|---|
| P4-001 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/chat/show.blade.php:4,6,12,28,31` |
| P4-002 | NEEDS_CONTEXT | IMPLEMENTED | `resources/views/chat/show.blade.php:15-17,31-33`; `resources/views/livewire/admin/support-inbox.blade.php:31` |
| P4-003 | REWRITE | IMPLEMENTED | `resources/views/livewire/chat-thread.blade.php:84-94` |
| P4-004 | KEEP | KEEP | `resources/views/livewire/chat-thread.blade.php:15` |
| P4-005 | KEEP | KEEP | `resources/views/livewire/chat-thread.blade.php:44-50,73-78,108-113` |
| P4-006 | SHORTEN | IMPLEMENTED | `app/Support/ChatQuickReplyPresenter.php:14,19-24` |
| P4-007 | KEEP | KEEP | `app/Support/ChatQuickReplyPresenter.php:31-38` |
| P4-008 | TERMINOLOGY_FIX | IMPLEMENTED | `app/Livewire/GlobalChatNotifier.php:165` |
| P4-009 | SECURITY_REVIEW | KEEP / SECURITY-PROTECTED | `resources/views/livewire/global-chat-notifier.blade.php:16-18` |
| P4-010 | SHORTEN | IMPLEMENTED | `resources/views/livewire/admin/support-inbox.blade.php:3` |
| P4-011 | SHORTEN | IMPLEMENTED | `resources/views/admin/support/index.blade.php:6` |
| P4-012 | KEEP | KEEP | `resources/views/livewire/admin/support-inbox.blade.php:38-41` |
| P4-013 | KEEP | KEEP | `resources/views/livewire/admin/support-inbox.blade.php:50` |
| P4-014 | KEEP | KEEP | `app/Support/ChatPresence.php:129-152` |

### Protected chat behavior confirmed unchanged

- Unread message and per-thread/sidebar badge semantics.
- Mark-read behavior and active-thread toast suppression.
- Archive/unarchive behavior, including auto-unarchive rules.
- Sender-only edit/delete eligibility, time window, read locking, and tombstones.
- Five-second active-thread polling, typing, Enter/Shift+Enter, IME handling,
  and auto-scroll.
- Presence heartbeat, thresholds, and labels.
- Global notifier mounting, polling, toast reconciliation, and dismissal.
- Assigned-admin recipient rules and authenticated chat deep links.
- Chat unread-email coalescing, unread-at-execution suppression, and delivery
  queue behavior.

### Protected copy confirmed unchanged

- `Edit pesan`, `Batal`, `Simpan`, `Hapus pesan?`, and the deletion consequence.
- `Tindakan percakapan`, `Keluarkan dari arsip`, and `Arsipkan`.
- `Online`, `Terakhir aktif ...`, and `Sedang tidak aktif`.
- Useful support empty states and message status labels.
- User-generated toast preview content and its privacy boundary.

### Copy diff summary

- Quick replies shortened or rewritten: 7.
- Thread descriptions/context labels rewritten: 3 root findings.
- Inbox descriptions shortened: 2.
- Toast terminology fixes: 1.
- KEEP items preserved: 7.
- Deferred Phase D items: 0.

### Manual browser acceptance items

Check client GENERAL_SUPPORT and APPLICATION threads at 1440, 1024, 768,
430, 393, and 360px. Verify headings, context labels, empty states, composer,
presence, archive controls, message actions, and toast action labels. In the
admin inbox, exercise every quick reply in the composer without sending an
external message; verify filters, archive/unarchive, empty states, and toast
navigation. Confirm no duplicate polling, presence heartbeat, notifier, or
toast behavior occurs.

## Final Phase D confirmation

- The original audit and Phase A, Phase B, and Phase C checkpoints remain unchanged.
- This Phase D checkpoint is current.
- Quick replies are shorter and more human while retaining state and next-action meaning.
- Quick-reply IDs/values remain unchanged.
- GENERAL_SUPPORT and APPLICATION remain distinct.
- Unread, archive, edit/delete, polling, presence, toast, notifier, recipient,
  deep-link, and email-coalescing behavior remain unchanged.
- No chat attachments, WebSocket, migration, or dependency were added.
- Transactional email Phase 1/2 behavior remains unchanged.

Recommended Phase E scope: accessibility semantics, framework/runtime
translations, global date/time/number conventions, remaining terminology
consistency, and punctuation standardization.
