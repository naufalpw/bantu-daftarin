# UI Copy Rewrite — Phase C

Date: 2026-09-07

Scope: Client application workspace, application creation, data forms,
document helpers, face-photo helper copy, and directly-related help terminology

Audit source: `docs/audit/ui-copy-audit.md`

Previous checkpoints: `docs/audit/ui-copy-rewrite-phase-a.md`,
`docs/audit/ui-copy-rewrite-phase-b.md`

Production copy modified: YES

Domain logic modified: NO

Migration: NO expected

Dependencies: NO expected

## Progress

| Checkpoint | Status | Implemented | Kept | Deferred | Tests |
|---|---|---:|---:|---:|---|
| CP0 Baseline + target extraction | COMPLETE | 0 | 18 | 1 | Baseline: 267 tests, 1,648 assertions; Vite build passed |
| CP1 Workspace shell | COMPLETE | 3 | 12 | 0 | `ClientUxPhaseBTest`: PASS (16 tests, 151 assertions) |
| CP2 Creation/forms | COMPLETE | 5 | 4 | 0 | Creation/forms/workspace regressions: PASS (29 tests, 264 assertions) |
| CP3 Documents | COMPLETE | 2 | 2 | 0 | `DocumentWorkflowTest` + workspace document/face tests: PASS (29 tests, 193 assertions) |
| CP4 Regression | COMPLETE | 0 | 18 | 1 | Scoped scan and protected-copy review passed; targeted regressions green |
| CP5 Final | COMPLETE | 10 | 18 | 1 | Full tests, build, Pint, diff, and dependency audits passed; browser QA remains manual |

## CP0 — Baseline + target extraction

Status: COMPLETE

### Baseline

- The worktree contained 111 status entries before Phase C. These are existing
  dirty changes from earlier phases and were preserved.
- `php artisan test --no-coverage`: PASS — 267 tests, 1,648 assertions.
- `npm run build`: PASS — Vite production build completed.
- No reset, checkout, cleanup, migration, or dependency change was performed.

### Target mapping

| Audit ID | Current audit status/decision | Phase C plan | Scope note |
|---|---|---|---|
| P3-001 | ACTIVE / KEEP | KEEP | Workspace title, safe short public ID, and copy action |
| P3-002 | ACTIVE / KEEP | KEEP | Status, estimate, cancellation read-only state, next action |
| P3-003 | ACTIVE / TERMINOLOGY_FIX | IMPLEMENT | Shared workspace privacy note |
| P3-004 | ACTIVE / KEEP | KEEP | Locked sensitive-data state and chat clarification path |
| P3-005 | ACTIVE / KEEP | KEEP | Personal document quality guidance |
| P3-006 | ACTIVE / SHORTEN | IMPLEMENT | Business document section helper |
| P3-007 | ACTIVE / KEEP | KEEP | Business-specific and conditional document descriptions |
| P3-008 | ACTIVE / KEEP | KEEP | Missing-requirement empty state |
| P3-009 | ACTIVE / KEEP | KEEP | Draft document-stage gate |
| P3-010 | ACTIVE / SHORTEN | IMPLEMENT | Revision submission guidance |
| P3-011 | ACTIVE / KEEP | KEEP | Payment section title |
| P3-012 | ACTIVE / KEEP | KEEP | Payment gate explanation |
| P3-013 | ACTIVE / KEEP | KEEP | VERIFIED result boundary and empty state |
| P3-014 | ACTIVE / TERMINOLOGY_FIX | IMPLEMENT | Workspace support card `QnA` terminology |
| P3-015 | ACTIVE / KEEP | KEEP | Cancellation eligibility and support fallback |
| P3-016 | ACTIVE / KEEP | KEEP | Cancellation consequence/confirmation |
| P3-017 | ACTIVE / SHORTEN | IMPLEMENT | Application creation heading/description |
| P3-018 | ACTIVE / TERMINOLOGY_FIX | IMPLEMENT | Application creation privacy note |
| P3-019 | ACTIVE / KEEP | KEEP | Draft-to-workspace transition helper |
| P3-020 | ACTIVE / KEEP | KEEP | Consent and non-government-portal disclosure |
| P3-021 | ACTIVE / SHORTEN | IMPLEMENT | Blur-triggered autosave explanation |
| P3-022 | ACTIVE / KEEP | KEEP | Business data/document consistency helper |
| P3-023 | ACTIVE / SHORTEN | IMPLEMENT | Primary representative helper |
| P3-024 | ACTIVE / KEEP | KEEP | Optional additional representative |
| P3-025 | ACTIVE / REMOVE | IMPLEMENT | Redundant manual-save helper |
| P3-026 | ACTIVE / SHORTEN | IMPLEMENT | Face-photo tips heading |
| P3-027 | ACTIVE / KEEP | KEEP | Face-document privacy and size limit |
| P3-028 | ACTIVE / KEEP | KEEP | Document card actions, states, formats, limits |
| P3-029 | LEGACY/UNUSED / NEEDS_CONTEXT | DEFERRED | Legacy registration component; current routes redirect to workspace |

The implementation set is limited to the ten clear active candidates listed
above. Protected payment, result, cancellation, consent, conditional-document,
privacy, and workflow copy remains unchanged unless a direct terminology fix is
required.

## CP1 — Shared application workspace

Status: COMPLETE

The shared workspace now uses Indonesian privacy terminology, a shorter
business-document helper, and the Phase B help-center name. Personal and
business document requirements, support destinations, and workspace state
conditions remain unchanged.

### P3-003

Status: IMPLEMENTED

Location: Shared Data & Dokumen section privacy note

File: `resources/views/client/applications/show.blade.php`

Original audit line: 126

Current implementation location: `resources/views/client/applications/show.blade.php:76`

Original copy: `"Lengkapi informasi dan persyaratan" / "File disimpan secara private"`

New copy: `"Lengkapi informasi dan persyaratan" / "File disimpan secara privat"`

Decision: TERMINOLOGY_FIX

Reason: Replaced the unnecessary English adjective while retaining the
existing privacy statement and surrounding authorized workspace behavior.

Behavior changed: NO

Risk checked: Private document storage, authorization, locked-data behavior,
and sensitive-value masking are unchanged.

### P3-006

Status: IMPLEMENTED

Location: Business document section helper

File: `resources/views/client/applications/show.blade.php`

Original audit line: 129

Current implementation location: `resources/views/client/applications/show.blade.php:134`

Original copy: `"Siapkan dokumen badan usaha" / "Pastikan setiap dokumen jelas dan sesuai dengan persyaratan pengajuan."`

New copy: `"Siapkan dokumen badan usaha" / "Pastikan dokumen terlihat jelas dan sesuai persyaratan."`

Decision: SHORTEN

Reason: The heading already identifies the business-document context. The
helper keeps both useful requirements—clarity and compliance—without the
formal “setiap ... sesuai dengan ... pengajuan” construction.

Behavior changed: NO

Risk checked: Business requirement grouping, document counts, revision states,
and conditional Surat Kuasa semantics are unchanged.

### P3-014

Status: IMPLEMENTED

Location: Workspace support panel

File: `resources/views/client/applications/show.blade.php`

Original audit line: 137

Current implementation location: `resources/views/client/applications/show.blade.php:268`

Original copy: `"Butuh bantuan?" / "Bantuan umum tersedia di QnA. Pertanyaan khusus pengajuan ini dapat dikirim melalui chat." / "Tanya tentang pengajuan ini" / "Buka Bantuan"`

New copy: `"Butuh bantuan?" / "Bantuan umum tersedia di Pusat Bantuan. Pertanyaan khusus pengajuan ini dapat dikirim melalui chat." / "Tanya tentang pengajuan ini" / "Buka Bantuan"`

Decision: TERMINOLOGY_FIX

Reason: Aligns the workspace with the Phase B client vocabulary while
preserving separate general-help and application-chat destinations.

Behavior changed: NO

Risk checked: The `qna` route, contextual chat route, thread ownership, and
support behavior are unchanged. Chat wording itself remains out of scope for
Phase D.

### Protected workspace copy kept

The following active copy was reviewed and intentionally left unchanged:

- `Ruang pengajuan`, safe short ID, and `Salin ID`.
- Status/estimate/cancellation read-only language and `Langkah berikutnya`.
- `Lengkapi informasi dan persyaratan` and personal document guidance.
- Business-specific KTP, Akta, SK AHU, and conditional Surat Kuasa descriptions.
- Draft and revision gates, including `Lanjut ke dokumen` and `Kirim perbaikan`.
- Payment title/gate, VERIFIED result boundary, and cancellation consequences.

### CP1 verification

- `ClientUxPhaseBTest.php`: PASS — 16 tests, 151 assertions.
- Personal and business workspace rendering, document grouping, support
  destination, privacy masking, and owner authorization remain covered.

## CP2 — Application creation + data forms

Status: COMPLETE

The creation page now separates requirement review from the draft condition,
uses Indonesian privacy terminology, and keeps the consent/data condition
explicit. The shared data form now describes blur-triggered autosave in user
terms, keeps the operation-specific `Menyimpan...` state, removes the redundant
manual-save sentence, and shortens the primary representative helper.

### P3-017

Status: IMPLEMENTED

Location: Application creation page introduction

File: `resources/views/client/applications/create.blade.php`

Original audit line: 140

Current implementation locations: `resources/views/client/applications/create.blade.php:25,27`

Original copy: `"Persyaratan &amp; mulai" / "Tinjau biaya dan dokumen yang perlu disiapkan. Draft dibuat setelah data awal dan persetujuan disimpan."`

New copy: `"Persyaratan pengajuan" / "Lihat biaya dan dokumen yang perlu disiapkan. Setelah data awal dan persetujuan disimpan, draft pengajuan dibuat."`

Decision: SHORTEN

Reason: The heading names the page purpose directly. The description keeps
both cost/document review and the exact initial-data/consent condition for
draft creation without implying that viewing requirements creates a draft.

Behavior changed: NO

Risk checked: Service selection, requirement rendering, initial form fields,
consent validation, and draft creation route are unchanged.

### P3-018

Status: IMPLEMENTED

Location: Application creation privacy note

File: `resources/views/client/applications/create.blade.php`

Original audit line: 141

Current implementation location: `resources/views/client/applications/create.blade.php:52`

Original copy: `"Dokumen akan disimpan secara private dan hanya dapat dibuka oleh pihak yang berwenang dalam pengajuan."`

New copy: `"Dokumen disimpan secara privat dan hanya dapat dibuka oleh pihak yang berwenang dalam pengajuan."`

Decision: TERMINOLOGY_FIX

Reason: Replaced mixed-language privacy wording while preserving the
authorized-access guarantee and the existing private-storage architecture.

Behavior changed: NO

Risk checked: Private storage, policy authorization, and document URL behavior
are unchanged.

### P3-021

Status: IMPLEMENTED

Location: Shared Livewire application data form

File: `resources/views/livewire/application-details-form.blade.php`

Original audit line: 144

Current implementation locations: `resources/views/livewire/application-details-form.blade.php:16,19`

Original copy: `"Data pengajuan" / "Perubahan disimpan saat Anda berpindah dari kolom." / "Menyimpan..."`

New copy: `"Data pengajuan" / "Perubahan disimpan otomatis setelah Anda selesai mengisi kolom." / "Menyimpan..."`

Decision: SHORTEN

Reason: The helper describes the user-visible blur trigger rather than an
implementation detail. The existing operation-specific loading text remains
unchanged because it accurately describes the save request.

Behavior changed: NO

Risk checked: `wire:model.blur`, `wire:blur="save"`, save validation, and
database persistence are unchanged; no stronger per-keystroke claim was added.

### P3-023

Status: IMPLEMENTED

Location: Primary business representative fieldset

File: `resources/views/livewire/application-details-form.blade.php`

Original audit line: 146

Current implementation location: `resources/views/livewire/application-details-form.blade.php:53`

Original copy: `"Penanggung jawab utama" / "Masukkan pihak yang bertanggung jawab atas pengajuan badan usaha ini."`

New copy: `"Penanggung jawab utama" / "Isi data penanggung jawab utama pengajuan."`

Decision: SHORTEN

Reason: The helper gives the next field action and keeps the primary
representative context without repeating the formal “pihak yang bertanggung
jawab” definition.

Behavior changed: NO

Risk checked: Primary representative distinction, required fields, relationship
validation, and optional additional representative behavior are unchanged.

### P3-025

Status: IMPLEMENTED

Location: Shared form footer

File: `resources/views/livewire/application-details-form.blade.php`

Original audit line: 148

Current implementation location: `resources/views/livewire/application-details-form.blade.php:72`

Original copy: `"Anda juga dapat menyimpan secara manual."`

New copy: removed; the existing `"Simpan data"` button remains.

Decision: REMOVE

Reason: The visible button already names the manual-save action. Removing the
sentence reduces redundant helper copy without removing the control or changing
autosave/manual-save behavior.

Behavior changed: NO

Risk checked: The `save` action, button, validation, and persistence remain in
place.

### CP2 verification

- `AuthenticatedClientUiTest.php`: PASS — 2 tests, 35 assertions.
- `ApplicationWorkflowTest.php`: PASS — 11 tests, 112 assertions.
- `ClientUxPhaseBTest.php`: PASS — 16 tests, 117 assertions.
- Combined creation/forms/workspace result: PASS — 29 tests, 264 assertions.
- Personal and business forms, consent, draft creation, blur autosave, manual
  save, and representative validation remain covered.

## CP3 — Documents + face photo

Status: COMPLETE

Document-specific descriptions, conditions, formats, limits, private access,
and revision semantics were reviewed. Only the revision submission helper and
the face-photo tips heading changed; concrete requirement descriptions and
upload constraints remain intact.

### P3-010

Status: IMPLEMENTED

Location: Revision submission panel

File: `resources/views/client/applications/show.blade.php`

Original audit line: 133

Current implementation location: `resources/views/client/applications/show.blade.php:173`

Original copy: `"Semua perbaikan sudah diunggah?" / "Kirim perbaikan setelah seluruh instruksi ditindaklanjuti." / "Kirim perbaikan"`

New copy: `"Semua perbaikan sudah diunggah?" / "Pastikan semua perbaikan sudah selesai sebelum dikirim." / "Kirim perbaikan"`

Decision: SHORTEN

Reason: The question and button already establish the state/action. The helper
now directly states the completion gate without the formal “ditindaklanjuti”
construction.

Behavior changed: NO

Risk checked: Revision requirement gating, targeted document checks, transition
to `REVISION_SUBMITTED`, and button action are unchanged.

### P3-026

Status: IMPLEMENTED

Location: Personal face-photo helper

File: `resources/views/components/personal-face-document.blade.php`

Original audit line: 149

Current implementation location: `resources/views/components/personal-face-document.blade.php:30`

Original copy: `"Pastikan wajah terlihat jelas sebelum mengambil atau mengunggah foto." / "Tips Foto yang Baik"`

New copy: `"Pastikan wajah terlihat jelas sebelum mengambil atau mengunggah foto." / "Tips foto"`

Decision: SHORTEN

Reason: The concrete face-visibility instruction remains. The heading is now
short, sentence-case, and names the content without the generic “yang baik”.

Behavior changed: NO

Risk checked: Camera/upload options, face-photo requirement, privacy note, file
size, image-quality tips, and upload route are unchanged.

### Protected document copy kept

- Personal and business requirement descriptions remain concrete.
- `Surat Kuasa` remains conditional when an application is represented.
- `Ganti dokumen`, file-selection states, accepted formats, and maximum size
  remain unchanged.
- `Dokumen hanya dapat diakses sesuai hak akses pengajuan.` remains unchanged.
- Personal face guidance, camera/upload controls, and privacy/size note remain
  present.

### CP3 verification

- `DocumentWorkflowTest.php`: PASS — 13 tests, 37 assertions.
- `ClientUxPhaseBTest.php`: PASS — 16 tests, 156 assertions.
- Combined document/face-photo result: PASS — 29 tests, 193 assertions.
- Upload, private storage, authorization, versioning, revision, and face-photo
  rendering behavior remain covered.

## CP4 — Terminology + protected-copy regression

Status: COMPLETE

### Scoped terminology scan

The active Phase C production surfaces were scanned without global replacement:

- `private`: fixed in the workspace and creation privacy notes to `privat`.
- `QnA`: fixed in the workspace support note to `Pusat Bantuan`.
- `FAQ`: no active occurrence in the Phase C surfaces.
- `aplikasi`: no visible service-application occurrence in the Phase C surfaces;
  `resources/views/client/applications/index.blade.php` remains an out-of-scope
  Phase B/client-list surface.
- `sesuai kebutuhan`: no active occurrence in changed Phase C surfaces; the
  client application-list occurrence is OUT_OF_SCOPE.
- `Anda juga dapat`: removed from the shared form helper.
- `Pastikan`: retained where it states a real document/photo/revision
  requirement; it is VALID rather than a blanket replacement target.
- `chat`: retained for contextual support wording and classified OUT_OF_SCOPE —
  Phase D; no chat-shell or quick-reply rewrite was made.
- `review`, `workflow`, and `lifecycle`: no active user-facing occurrence in
  the changed Phase C surfaces; technical enum/property usage is out of scope.

Phase B help vocabulary remains intact: `Bantuan`, `Pusat Bantuan`, and
`Bantuan pengajuan`. The technical `/qna` route is unchanged.

### Protected copy confirmed unchanged

- Payment availability gate: `Pembayaran belum dibuka. Lengkapi data dan
  dokumen wajib terlebih dahulu.`
- Result authorization gate: `Hasil belum dapat dilihat atau diunduh sampai
  verifikasi selesai.`
- Cancellation eligibility, payment-confirmation boundary, and terminal
  consequence copy.
- Locked sensitive-data explanation and chat clarification path.
- Consent disclosure that Bantu Daftarin is an administrative service, not an
  official government portal.
- Personal/business separation and business document descriptions.
- Conditional `Surat Kuasa` requirement.
- Private document access note, active document/version behavior, accepted
  formats, and maximum file size.
- Draft creation condition requiring initial data and saved consent.

### Out-of-scope findings

- Chat quick replies, chat empty states, toast/conversation terminology, and
  global chat copy remain for Phase D.
- Accessibility implementation, global dates/currency/numbers, ellipsis
  standardization, and framework translations remain for Phase E.
- Legacy registration copy P3-029 remains DEFERRED because current routes
  redirect to the application workspace and no active call site was found.

### CP4 verification

- Scoped source scan completed; no broad global replacement used.
- Targeted workspace, form, document, and security regression tests remained
  green.

## CP5 — Final verification

Status: COMPLETE

### Executable verification

- `php artisan optimize:clear`: PASS.
- `php artisan view:cache`: PASS.
- `npm run build`: PASS.
- `php artisan test --no-coverage`: PASS — 267 tests, 1,660 assertions.
- `vendor/bin/pint --test`: PASS.
- `git diff --check`: PASS.
- `npm audit --omit=dev`: PASS — 0 vulnerabilities.
- `composer audit --locked`: PASS — no security advisories.

The baseline was 267 tests and 1,648 assertions. The final assertion increase
comes from targeted copy-regression assertions; no domain or security behavior
was changed.

### Manual browser QA

Not run in this environment. Authenticated browser review remains required at
1440, 1024, 768, 430, 393, and 360 px for:

- Personal workspace: privacy note, document cards, face-photo helper, revision
  gate, payment/result sections, cancellation, and support panel.
- Business workspace: company data, primary/additional representatives,
  business document grid, KTP/Akta/SK AHU, conditional Surat Kuasa, and helper
  wrapping.
- Application creation: requirement heading, cost/document explanation,
  consent, privacy note, and draft condition.
- Autosave: blur-triggered persistence matches the new helper wording; manual
  `Simpan data` remains available.
- Responsive behavior: no helper overflow or lost document/revision/payment
  context on narrow screens.

## Final Phase C Status

Implemented audit IDs: P3-003, P3-006, P3-010, P3-014, P3-017, P3-018,
P3-021, P3-023, P3-025, P3-026.

Kept audit IDs: P3-001, P3-002, P3-004, P3-005, P3-007, P3-008, P3-009,
P3-011, P3-012, P3-013, P3-015, P3-016, P3-019, P3-020, P3-022, P3-024,
P3-027, P3-028.

Deferred audit IDs: P3-029 — legacy/unused registration source with no current
routed call site; `[BUSINESS CONFIRMATION REQUIRED]` before any removal or
reconciliation.

Out-of-scope findings: chat quick replies and global chat terminology (Phase D),
accessibility implementation, global date/time/currency/number formatting,
ellipsis standardization, framework translation cleanup (Phase E), and the
client application list surface.

Files changed for Phase C:

- `resources/views/client/applications/show.blade.php`
- `resources/views/client/applications/create.blade.php`
- `resources/views/livewire/application-details-form.blade.php`
- `resources/views/components/personal-face-document.blade.php`
- `tests/Feature/Client/ClientUxPhaseBTest.php`
- `tests/Feature/Client/AuthenticatedClientUiTest.php`
- `tests/Feature/Applications/ApplicationWorkflowTest.php`
- `docs/audit/ui-copy-rewrite-phase-c.md`

Production behavior changed: NO

Domain logic changed: NO

Security/privacy semantics changed: NO

Database/schema changed: NO

Migration: NO

Dependencies: NO

### Copy diff summary

- Descriptions removed: 1 redundant manual-save helper.
- Descriptions shortened: 6 root findings (business helper, revision helper,
  creation intro, autosave helper, representative helper, face-photo heading).
- Terminology fixes: 3 (`private` → `privat` twice, `QnA` → `Pusat Bantuan`).
- Form helpers simplified: 3 root findings, including the removed manual-save
  sentence.
- KEEP items preserved: 18.
- Deferred: 1 legacy/unused finding.

Counts refer to root audit findings; categories can overlap when one finding
is both a helper simplification and a shortening.

## Audit implementation mapping

| Audit ID | Original decision | Phase C status | Current source |
|---|---|---|---|
| P3-001 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:31-35` |
| P3-002 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:42,47-57` |
| P3-003 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/client/applications/show.blade.php:76` |
| P3-004 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:83-84` |
| P3-005 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:92-93` |
| P3-006 | SHORTEN | IMPLEMENTED | `resources/views/client/applications/show.blade.php:133-134` |
| P3-007 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:147-150` |
| P3-008 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:119,158` |
| P3-009 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:167-168` |
| P3-010 | SHORTEN | IMPLEMENTED | `resources/views/client/applications/show.blade.php:173` |
| P3-011 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:181` |
| P3-012 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:195` |
| P3-013 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:234-236` |
| P3-014 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/client/applications/show.blade.php:268` |
| P3-015 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:277-293` |
| P3-016 | KEEP | KEEP | `resources/views/client/applications/show.blade.php:309-328` |
| P3-017 | SHORTEN | IMPLEMENTED | `resources/views/client/applications/create.blade.php:25-27` |
| P3-018 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/client/applications/create.blade.php:52` |
| P3-019 | KEEP | KEEP | `resources/views/client/applications/create.blade.php:142-143` |
| P3-020 | KEEP | KEEP | `resources/views/client/applications/create.blade.php:137` |
| P3-021 | SHORTEN | IMPLEMENTED | `resources/views/livewire/application-details-form.blade.php:15-19` |
| P3-022 | KEEP | KEEP | `resources/views/livewire/application-details-form.blade.php:38-39` |
| P3-023 | SHORTEN | IMPLEMENTED | `resources/views/livewire/application-details-form.blade.php:52-53` |
| P3-024 | KEEP | KEEP | `resources/views/livewire/application-details-form.blade.php:62-63` |
| P3-025 | REMOVE | IMPLEMENTED | `resources/views/livewire/application-details-form.blade.php:72` (button retained; helper removed) |
| P3-026 | SHORTEN | IMPLEMENTED | `resources/views/components/personal-face-document.blade.php:20,30` |
| P3-027 | KEEP | KEEP | `resources/views/components/personal-face-document.blade.php:53-57` |
| P3-028 | KEEP | KEEP | `resources/views/components/personal-document-card.blade.php:52-53,69-72` |
| P3-029 | NEEDS_CONTEXT | DEFERRED | `resources/views/livewire/registration-details-form.blade.php:7-104` |

## Protected copy confirmed unchanged

- Payment gating and payment-status truth.
- Result `VERIFIED` access/download gate.
- Cancellation eligibility, terminal consequence, and support fallback.
- Sensitive-data masking and locked-data explanation.
- Consent disclosure and non-government-portal boundary.
- Personal/business separation and representative optionality.
- Business KTP, Akta, SK AHU, and conditional Surat Kuasa requirements.
- Private document authorization, versioning, accepted formats, and size limits.
- Draft creation condition and application state transitions.

### Final confirmations

- The original audit remains unchanged.
- Phase A and Phase B checkpoints remain unchanged.
- The Phase C checkpoint is current and contains CP0–CP5 records.
- Personal and Business semantics remain distinct.
- Business document requirements remain authoritative; Surat Kuasa remains
  conditional.
- Autosave copy matches actual `wire:model.blur`/`wire:blur="save"` behavior.
- Manual `Simpan data` functionality remains.
- Payment gating, result VERIFIED gating, cancellation rules, privacy semantics,
  and application workflow are unchanged.
- No global find/replace was used.
- No migration or dependency was added.
- Chat quick replies and chat-shell terminology were not rewritten.
- Transactional email Phase 1/2 behavior remains unchanged.

Recommended Phase D scope: chat/support copy only—quick replies, chat empty
states, archive/conversation wording, and toast/support microcopy—using the same
source-traceable checkpoint method.
