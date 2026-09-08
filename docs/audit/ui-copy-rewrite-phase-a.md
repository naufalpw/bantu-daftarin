# UI Copy Rewrite — Phase A

Date: 2026-09-07

Scope: Admin Dashboard, Pengajuan, Dokumen

Audit source: `docs/audit/ui-copy-audit.md`

Production copy modified: YES

Domain logic modified: NO

Migration: NO expected

Dependencies: NO expected

## Progress

| Checkpoint | Status | Findings completed | Findings deferred | Tests |
|---|---|---:|---:|---|
| CP0 Baseline | COMPLETE | 0 | 1 context item | Baseline test/build passed: 267 tests, 1,645 assertions; Vite build passed. |
| CP1 Dashboard | COMPLETE | 6 | 0 | Targeted AdminPhaseB dashboard test: PASS (1 test, 10 assertions). |
| CP2 Pengajuan | COMPLETE | 8 | 1 | `AdminOperationsUiTest`: PASS (10 tests, 80 assertions); `AdminWorkflowTest`: PASS (12 tests, 38 assertions). |
| CP3 Dokumen | COMPLETE | 6 | 0 | Document queue/detail coverage: PASS via `AdminOperationsUiTest` (10 tests, 80 assertions) and workflow coverage via `AdminWorkflowTest` (12 tests, 38 assertions). |
| CP4 Regression | COMPLETE | 0 new root findings | 1 (P5-020) | Reactive filtering: PASS (6 tests, 63 assertions); target admin UI suites remain PASS. |
| CP5 Final | COMPLETE | 20 | 1 | Full suite/build/style/security verification passed; browser QA pending. |

## CP0 — Baseline

### Worktree

The worktree already contained 99 entries before this phase: 72 modified and
27 untracked. Those changes are unrelated in-progress work and were preserved.
No reset, checkout, or cleanup was performed.

### Baseline verification

- `php artisan test --no-coverage`: PASS — 267 tests, 1,645 assertions.
- `npm run build`: PASS — Vite production build completed.
- Source audit read in full; `docs/audit/ui-copy-audit.md` remains unchanged.

### Phase A target list

#### IMPLEMENT

- Dashboard: P5-001, P5-002, P5-003, P5-004, P5-017.
- Shared dashboard/admin presenter: P5-026; only user-facing labels and
  fallback descriptions used by the affected admin surfaces are in scope.
- Pengajuan: P5-005, P5-011, P5-018, P5-019, P5-024, P5-025, P5-027, P6-007.
- Dokumen: P5-006, P5-013, P5-014, P5-022, P5-029, P6-006.
- Additional current-source occurrences directly attached to the document
  queue (`Status review`, `Menunggu review`, `Selesai direview`) will be
  reconciled in CP3/CP4 without changing filter keys.

#### DEFER

- P5-020 (`provider`) remains `DEFERRED — NEEDS_CONTEXT`; the term is visible
  in an internal admin payment source-of-truth context and does not belong to
  the Dashboard/Pengajuan/Dokumen copy rewrite without an operational vocabulary
  decision.
- Any `review`/`workflow` text outside the affected pages or directly-related
  shared presenter remains deferred for a later phase.

#### KEEP

- P5-008, P5-009, P5-012, P5-021, and P5-023 remain unchanged because they are
  already clear or carry authorization/privacy meaning.
- Existing concise state-machine action labels remain unchanged unless a listed
  finding explicitly targets a mixed-language label.

Production source edits begin after this checkpoint. The original audit is an
immutable reference and will not be edited.

## CP1 — Admin Dashboard

Status: COMPLETE

The dashboard copy was reduced to distinct operational jobs: identify the
workspace, name the actionable queue, describe the empty activity state, and
explain the priority queue without repeating its selection rule. Dashboard
queries, ordering, links, chart metrics, and period filtering were not changed.

### P5-001

Status: IMPLEMENTED

Location: Dashboard page intro

File: `resources/views/admin/dashboard.blade.php`

Original audit line: 202

Current implementation location: `resources/views/admin/dashboard.blade.php:8`

Original copy: `"Ruang kerja admin" / "Pantau pekerjaan penting dan perkembangan layanan."`

New copy: `"Ruang kerja admin" / "Lihat pengajuan yang perlu ditindaklanjuti dan aktivitas terbaru."`

Reason: Kept the clear page title and replaced the abstract monitoring slogan
with the two concrete dashboard areas.

Domain behavior changed: NO

Risk checked: Page data, attention links, activity data, and period query are unchanged.

### P5-002

Status: IMPLEMENTED

Location: Dashboard attention panel

File: `resources/views/admin/dashboard.blade.php`

Original audit line: 203

Current implementation location: `resources/views/admin/dashboard.blade.php:23`

Original copy: `"PERLU PERHATIAN" / "Pekerjaan yang memerlukan tindakan"`

New copy: `"Perlu ditindaklanjuti"` (single heading; duplicate subtitle removed)

Reason: One heading now carries the attention state; the existing counts, items,
and action links remain intact.

Domain behavior changed: NO

Risk checked: Only presentation text changed; attention collection and links are unchanged.

### P5-003

Status: IMPLEMENTED

Location: Recent activity empty state

File: `resources/views/admin/dashboard.blade.php`

Original audit line: 204

Current implementation location: `resources/views/admin/dashboard.blade.php:63`

Original copy: `"Belum ada aktivitas terbaru" / "Pembaruan workflow akan muncul di sini."`

New copy: `"Belum ada aktivitas terbaru"` (internal workflow sentence removed)

Reason: The title already states the empty state; removing the vague internal
sentence avoids filler without losing an instruction or domain rule.

Domain behavior changed: NO

Risk checked: Recent-activity query and empty-state condition are unchanged.

### P5-004

Status: IMPLEMENTED

Location: Dashboard priority queue

File: `resources/views/admin/dashboard.blade.php`

Original audit line: 205

Current implementation location: `resources/views/admin/dashboard.blade.php:72-73`

Original copy: `"ANTRIAN PRIORITAS" / "Pengajuan yang perlu dibuka berikutnya" / "Hanya pengajuan yang memerlukan tindakan admin ditampilkan di sini."`

New copy: `"Antrian prioritas" / "Pengajuan yang memerlukan tindakan admin."`

Reason: The title names the queue and the description states its actionable
scope once, without repeating the same rule in a second sentence.

Domain behavior changed: NO

Risk checked: Priority ordering, query scope, item count, and CTA destinations are unchanged.

### P5-017

Status: IMPLEMENTED

Location: Dashboard activity-chart empty state

File: `resources/views/livewire/admin/activity-chart.blade.php`

Original audit line: 218

Current implementation location: `resources/views/livewire/admin/activity-chart.blade.php:74`

Original copy: `"Belum ada aktivitas pada periode ini" / "Aktivitas workflow akan muncul setelah terdapat proses pengajuan."`

New copy: `"Belum ada aktivitas pada periode ini" / "Data aktivitas akan muncul saat pengajuan mulai diproses."`

Reason: Replaced internal English and stiff phrasing with a concise statement
that matches the existing curated operational event definition.

Domain behavior changed: NO

Risk checked: Chart period selection, event aggregation, and empty condition are unchanged.

### P5-026

Status: IMPLEMENTED

Location: Shared admin activity presenter used by Dashboard/activity surfaces

File: `app/Support/AdminActivityPresenter.php`

Original audit line: 227

Current implementation locations: `app/Support/AdminActivityPresenter.php:220,239,272,280`

Original copy: `"Terdapat pembaruan pada workflow pengajuan."` and the mixed
labels `"Dokumen dikirim untuk review"` / `"Review hasil dimulai"`

New copy: `"Terdapat pembaruan pada proses pengajuan."`,
`"Dokumen dikirim untuk pemeriksaan"`, and `"Pemeriksaan hasil dimulai"`

Reason: Aligns shared activity language with established Indonesian operational
terms while keeping each event category and meaning distinct.

Domain behavior changed: NO

Risk checked: Presenter event mapping, activity categories, and timestamps are unchanged; only visible labels/descriptions changed.

### CP1 verification

- Targeted Dashboard test passed: 1 test, 10 assertions.
- `Ruang kerja admin` and the existing chart/attention semantics were retained.
- No route, query, state transition, authorization, or reactive-filter behavior changed.
- Shared presenter wording also appears on the Admin Aktivitas surface; no
  additional Admin Aktivitas scope was opened.

## CP2 — Admin Pengajuan

Status: COMPLETE

Pengajuan copy now names the operational purpose directly, removes abstract
subtitles, and uses Indonesian examination terms where the action is visible.
Filter keys, URL values, action routes, state transitions, payment truth, and
authorization remain unchanged.

### P5-005

Status: IMPLEMENTED

Location: Pengajuan page header

File: `resources/views/admin/applications/index.blade.php`

Original audit line: 206

Current implementation location: `resources/views/admin/applications/index.blade.php:7`

Original copy: `"Tinjau pekerjaan yang memerlukan keputusan, pantau proses aktif, dan buka detail pengajuan secara aman."`

New copy: `"Lihat pengajuan yang perlu ditindaklanjuti dan proses yang sedang berjalan."`

Reason: Replaced the abstract noun, three-verb chain, and redundant safety
qualifier with the page's actual operational scope.

Domain behavior changed: NO

Risk checked: Page route and the Livewire application queue are unchanged.

### P5-011

Status: IMPLEMENTED

Location: Application queue introduction

File: `resources/views/livewire/admin/application-queue.blade.php`

Original audit line: 212

Current implementation location: `resources/views/livewire/admin/application-queue.blade.php:3`

Original copy: `"Urutan memprioritaskan pekerjaan operasional yang perlu ditindaklanjuti."`

New copy: `"Pengajuan yang perlu ditindaklanjuti ditampilkan lebih dulu."`

Reason: Describes the visible ordering rule in plain product language without
exposing implementation terminology.

Domain behavior changed: NO

Risk checked: SQL ordering, filter state, search, pagination, and URL query state are unchanged.

### P5-018

Status: IMPLEMENTED

Location: Application detail summary

File: `resources/views/admin/applications/show.blade.php`

Original audit line: 219

Current implementation location: `resources/views/admin/applications/show.blade.php:25`

Original copy: `"Ringkasan operasional" / "Konteks singkat untuk menentukan tindakan berikutnya."`

New copy: `"Ringkasan operasional"` (subtitle removed)

Reason: The definition fields already show the next action; the abstract
subtitle did not add information.

Domain behavior changed: NO

Risk checked: Summary fields and next-action presenter remain rendered.

### P5-019

Status: IMPLEMENTED

Location: Cancelled application detail

File: `resources/views/admin/applications/show.blade.php`

Original audit line: 220

Current implementation location: `resources/views/admin/applications/show.blade.php:31`

Original copy: `"Pengajuan tetap tersimpan sebagai riwayat dan tidak memiliki tindakan workflow lanjutan."`

New copy: `"Pengajuan tetap tersimpan sebagai riwayat dan tidak dapat dilanjutkan."`

Reason: Preserves cancellation finality and history retention while removing
internal workflow language. The cancellation state is terminal in the current
workflow tests.

Domain behavior changed: NO

Risk checked: No reopen/delete behavior or cancellation transition changed.

### P5-024

Status: IMPLEMENTED

Location: Application history section

File: `resources/views/admin/applications/show.blade.php`

Original audit line: 225

Current implementation location: `resources/views/admin/applications/show.blade.php:106`

Original copy: `"Riwayat status dan estimasi yang relevan untuk operasional."`

New copy: `"Riwayat status dan estimasi"`

Reason: The shorter subtitle names exactly what the timeline contains.

Domain behavior changed: NO

Risk checked: Status and estimate history queries and ordering are unchanged.

### P5-025

Status: IMPLEMENTED

Location: Admin action presenter and application detail actions

Files: `app/Support/AdminApplicationPresenter.php`, `resources/views/admin/applications/show.blade.php`

Original audit line: 226

Current implementation locations: `app/Support/AdminApplicationPresenter.php:69,74` and `resources/views/admin/applications/show.blade.php:117`

Original copy: `"Mulai review hasil"` / `"Pantau pengajuan"` / `"Catatan review (opsional)"` / `"Selesaikan review"`

New copy: `"Tinjau hasil"` / `"Tidak ada tindakan"` / `"Catatan pemeriksaan (opsional)"` / `"Selesaikan peninjauan"`

Reason: Translated only the mixed or misleading visible labels. The default
label now states that no admin transition is available for early client-owned
stages; action availability remains state-gated.

Domain behavior changed: NO

Risk checked: Presenter match arms, form methods, route names, action values,
and transition tests are unchanged.

### P5-027

Status: IMPLEMENTED

Location: Application filter label

File: `app/Support/AdminApplicationPresenter.php`

Original audit line: 228

Current implementation location: `app/Support/AdminApplicationPresenter.php:18`

Original copy: `"Hasil perlu review"`

New copy: `"Hasil perlu ditinjau"`

Reason: Uses the established Indonesian action term while retaining the same
result-review filter meaning.

Domain behavior changed: NO

Risk checked: The `result` filter key, URL query value, and status mapping are unchanged.

### P6-007

Status: IMPLEMENTED

Location: Completion/archive flash feedback

File: `app/Http/Controllers/Admin/ApplicationController.php`

Original audit line: 254

Current implementation locations: `app/Http/Controllers/Admin/ApplicationController.php:119,128`

Original copy: `"Aplikasi ditandai selesai."` / `"Aplikasi diarsipkan."`

New copy: `"Pengajuan ditandai selesai."` / `"Pengajuan diarsipkan."`

Reason: Aligns the visible noun with the established product term without
renaming the internal Application model/controller identifiers.

Domain behavior changed: NO

Risk checked: Only session flash strings changed; completion/archive actions and authorization are unchanged.

### P5-020

Status: DEFERRED

Location: Payment detail source-of-truth note

File: `resources/views/admin/applications/show.blade.php:41`

Original copy: `"Informasi pembayaran bersifat baca-saja dan berasal dari alur provider."`

Reason: `provider` is a context-dependent operational term and changing it
without an agreed admin vocabulary could weaken payment source-of-truth meaning.

Domain behavior changed: NO

Risk checked: Payment read-only and provider-authoritative behavior was preserved.

### CP2 verification

- `AdminOperationsUiTest`: PASS — 10 tests, 80 assertions.
- `AdminWorkflowTest`: PASS — 12 tests, 38 assertions.
- Filter keys and URL/query values were not changed.
- Action routes, transition methods, result gating, payment read-only behavior,
  and authorization were not changed.

## CP3 — Admin Dokumen

Status: COMPLETE

Document queue and detail copy now uses `pemeriksaan`/`ditinjau` consistently,
while retaining the authorized application-detail route, active-version rule,
document counts, review actions, and filter keys.

### P5-006

Status: IMPLEMENTED

Location: Dokumen page header

File: `resources/views/admin/documents/index.blade.php`

Original audit line: 207

Current implementation location: `resources/views/admin/documents/index.blade.php:7`

Original copy: `"Setiap item merangkum dokumen aktif dalam satu pengajuan agar konteks review tetap jelas."`

New copy: `"Periksa dokumen yang perlu ditindaklanjuti pada setiap pengajuan."`

Reason: Names the admin task and grouped pengajuan context without explaining
the card implementation or using mixed-language review terminology.

Domain behavior changed: NO

Risk checked: Document queue route, query, counts, and authorization boundary are unchanged.

### P5-013

Status: IMPLEMENTED

Location: Document queue introduction

File: `resources/views/livewire/admin/document-queue.blade.php`

Original audit line: 214

Current implementation location: `resources/views/livewire/admin/document-queue.blade.php:3`

Original copy: `"Antrian review pengajuan" / "Tinjau dokumen dari detail pengajuan yang sudah terotorisasi."`

New copy: `"Dokumen untuk ditinjau" / "Tinjau dokumen melalui detail pengajuan yang dapat Anda akses."`

Reason: Removes mixed English and keeps the important authorization/context
constraint without implying global document access.

Domain behavior changed: NO

Risk checked: The `review` filter key and application-detail destination remain unchanged.

### P5-014

Status: IMPLEMENTED

Location: Document queue grouping/status copy

File: `resources/views/livewire/admin/document-queue.blade.php`

Original audit line: 215

Current implementation locations: `resources/views/livewire/admin/document-queue.blade.php:22,25,28`

Original copy: `"Dokumen aktif diringkas dalam satu pengajuan."` plus the visible
`"Status review"` and `"Selesai direview"` labels.

New copy: `"Dokumen dikelompokkan per pengajuan."`, `"Status pemeriksaan"`,
and `"Pemeriksaan selesai"`

Reason: Describes the grouping and visible state in product language while
preserving document and revision counts.

Domain behavior changed: NO

Risk checked: Counts, empty state, queue tone, and status conditions are unchanged.

### P5-022

Status: IMPLEMENTED

Location: Application detail document section

File: `resources/views/admin/applications/show.blade.php`

Original audit line: 223

Current implementation location: `resources/views/admin/applications/show.blade.php:70,90`

Original copy: `"Dokumen dan review"` / `"Dokumen aktif yang dikirim pengguna. Keputusan tetap berlaku pada versi aktif dan lifecycle yang tersedia."` / `"Catat keputusan review"`

New copy: `"Dokumen dan pemeriksaan"` / `"Dokumen aktif yang dikirim pengguna. Keputusan hanya berlaku pada versi dokumen yang sedang aktif."` / `"Catat keputusan"`

Reason: Removes internal lifecycle/review terms but explicitly retains the
active-version boundary that prevents decisions being interpreted for old
document versions.

Domain behavior changed: NO

Risk checked: Active-document selection, private routes, form action, and review status semantics are unchanged.

### P5-029

Status: IMPLEMENTED

Location: Document review action

File: `resources/views/admin/applications/show.blade.php`

Original audit line: 230

Current implementation location: `resources/views/admin/applications/show.blade.php:114-119`

Original copy: `"Selesaikan review"`

New copy: `"Selesaikan peninjauan"`

Reason: Aligns the completion action with surrounding Indonesian operational
labels without changing its form route or transition.

Domain behavior changed: NO

Risk checked: Finalize-review route, request field, and state transition are unchanged.

### P6-006

Status: IMPLEMENTED

Location: Saved document-decision feedback

File: `app/Http/Controllers/Admin/ApplicationController.php`

Original audit line: 253

Current implementation location: `app/Http/Controllers/Admin/ApplicationController.php:56`

Original copy: `"Review dokumen tersimpan."`

New copy: `"Keputusan dokumen tersimpan."`

Reason: The controller persists a `DocumentReviewAction`; the feedback now
names the saved decision rather than the internal English stage name.

Domain behavior changed: NO

Risk checked: Review request validation, service call, audit event, and redirect are unchanged.

### Related visible filter labels (no key changes)

The current Livewire document queue labels `Menunggu review` and `Selesai
direview` were changed to `Menunggu pemeriksaan` and `Pemeriksaan selesai` in
`app/Livewire/Admin/DocumentQueue.php:24,27`. The array keys (`review`,
`reviewed`) and every query branch remain unchanged.

### CP3 verification

- Document queue/detail coverage passed in `AdminOperationsUiTest` (10 tests,
  80 assertions).
- Workflow and document decision coverage passed in `AdminWorkflowTest` (12
  tests, 38 assertions).
- Private document authorization, active-version semantics, review actions,
  filter keys, and empty states were preserved.

## CP4 — Cross-module terminology + regression

Status: COMPLETE

### Scoped terminology search

The affected Dashboard, Pengajuan, Dokumen, directly-related presenters, and
Admin document queue were searched for the audit terms `aplikasi`, `review`,
`workflow`, `lifecycle`, `secara aman`, `sesuai kebutuhan`, `Pantau`, and
`Kelola`.

Results:

- Visible target copy in scope was fixed or removed; no old target sentence
  remains in the changed surfaces.
- Remaining `review` occurrences are technical identifiers, route/method names,
  enum/status values, query keys, or authorization names and are OUT_OF_SCOPE;
  they were not mechanically replaced.
- `workflow` remains only in a presenter code comment and technical/domain
  context, OUT_OF_SCOPE.
- `lifecycle` no longer appears in the affected visible document copy.
- P5-020 (`provider`) remains DEFERRED — NEEDS_CONTEXT because payment
  source-of-truth wording needs an operational vocabulary decision.
- `aplikasi` in the scoped visible copy was aligned to `pengajuan`; internal
  Application identifiers and unrelated client/out-of-scope strings were not
  changed.

### Regression verification

- `AdminReactiveFilteringTest`: PASS — 6 tests, 63 assertions. Livewire
  filtering, search, pagination, URL state, dashboard period, and admin
  authorization remain intact.
- `AdminOperationsUiTest`: PASS — 10 tests, 80 assertions.
- `AdminWorkflowTest`: PASS — 12 tests, 38 assertions.
- Dashboard targeted test: PASS — 1 test, 10 assertions.

No email templates, chat behavior, queue, notifier, polling, or reactive
filtering architecture was changed.

## CP5 — Final verification

Status: COMPLETE

### Automated verification

- `php artisan optimize:clear`: PASS.
- `php artisan view:cache`: PASS.
- `php artisan test --no-coverage`: PASS — 267 tests, 1,645 assertions.
- `npm run build`: PASS — Vite production build completed.
- `vendor/bin/pint --test`: PASS.
- `git diff --check`: PASS.
- `npm audit --omit=dev`: PASS — 0 vulnerabilities.
- `composer audit --locked`: PASS — no security vulnerability advisories.
- No migration was added and no dependency was added.

### Manual browser QA

Not run in this environment (`NOT VERIFIED`). The following remain acceptance
items for an authenticated browser at 1440, 1024, 768, 430, 393, and 360px:

- Dashboard copy density, attention panel, priority queue, and chart empty state.
- Pengajuan header, filter label `Hasil perlu ditinjau`, queue/detail actions,
  cancellation text, and flash feedback.
- Dokumen queue/detail headings, active-version note, review controls, and
  saved feedback.
- Responsive wrapping and absence of excess vertical space after subtitle
  removal.
- Livewire filtering/search/pagination/loading and dashboard period behavior
  remain partial updates without document navigation.

## Final Phase A Status

Implemented audit IDs: P5-001, P5-002, P5-003, P5-004, P5-005, P5-006,
P5-011, P5-013, P5-014, P5-017, P5-018, P5-019, P5-022, P5-024, P5-025,
P5-026, P5-027, P5-029, P6-006, P6-007.

Deferred audit IDs: P5-020 (`provider`, NEEDS_CONTEXT).

Files changed for this phase:

- `resources/views/admin/dashboard.blade.php`
- `resources/views/livewire/admin/activity-chart.blade.php`
- `resources/views/admin/applications/index.blade.php`
- `resources/views/livewire/admin/application-queue.blade.php`
- `resources/views/admin/applications/show.blade.php`
- `resources/views/admin/documents/index.blade.php`
- `resources/views/livewire/admin/document-queue.blade.php`
- `app/Support/AdminActivityPresenter.php`
- `app/Support/AdminApplicationPresenter.php`
- `app/Livewire/Admin/DocumentQueue.php`
- `app/Http/Controllers/Admin/ApplicationController.php`
- `tests/Feature/Admin/AdminPhaseBTest.php`
- `tests/Feature/Admin/AdminOperationsUiTest.php`
- `docs/audit/ui-copy-rewrite-phase-a.md`

Production logic changed: NO

Database/schema changed: NO

Dependencies changed: NO

Tests: Full suite 267 passed / 1,645 assertions; targeted Dashboard,
Pengajuan/Dokumen workflow, and reactive-filter suites passed.

Manual browser QA: NOT VERIFIED; acceptance items listed above.

Remaining copy phases: Phase B — application workspace/forms and other areas
listed in the source audit. Admin Dukungan, Aktivitas, Pengguna, client copy,
chat quick replies, auth, accessibility, and format standardization remain out
of scope.

## Audit implementation mapping

| Audit ID | Original decision | Phase A status | Current source |
|---|---|---|---|
| P5-001 | SHORTEN | IMPLEMENTED | `resources/views/admin/dashboard.blade.php:8` |
| P5-002 | MERGE | IMPLEMENTED | `resources/views/admin/dashboard.blade.php:23` |
| P5-003 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/admin/dashboard.blade.php:63` |
| P5-004 | SHORTEN | IMPLEMENTED | `resources/views/admin/dashboard.blade.php:72-73` |
| P5-005 | SHORTEN | IMPLEMENTED | `resources/views/admin/applications/index.blade.php:7` |
| P5-006 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/admin/documents/index.blade.php:7` |
| P5-011 | SHORTEN | IMPLEMENTED | `resources/views/livewire/admin/application-queue.blade.php:3` |
| P5-013 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/livewire/admin/document-queue.blade.php:3` |
| P5-014 | SHORTEN | IMPLEMENTED | `resources/views/livewire/admin/document-queue.blade.php:22,25,28` |
| P5-017 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/livewire/admin/activity-chart.blade.php:74` |
| P5-018 | SHORTEN | IMPLEMENTED | `resources/views/admin/applications/show.blade.php:25` |
| P5-019 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/admin/applications/show.blade.php:31` |
| P5-020 | NEEDS_CONTEXT | DEFERRED | `resources/views/admin/applications/show.blade.php:41` |
| P5-022 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/admin/applications/show.blade.php:70,90` |
| P5-024 | SHORTEN | IMPLEMENTED | `resources/views/admin/applications/show.blade.php:106` |
| P5-025 | TERMINOLOGY_FIX | IMPLEMENTED | `app/Support/AdminApplicationPresenter.php:69,74`; `resources/views/admin/applications/show.blade.php:113,117` |
| P5-026 | TERMINOLOGY_FIX | IMPLEMENTED | `app/Support/AdminActivityPresenter.php:220,239,272,280` |
| P5-027 | TERMINOLOGY_FIX | IMPLEMENTED | `app/Support/AdminApplicationPresenter.php:18` |
| P5-029 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/admin/applications/show.blade.php:113` |
| P6-006 | TERMINOLOGY_FIX | IMPLEMENTED | `app/Http/Controllers/Admin/ApplicationController.php:56` |
| P6-007 | TERMINOLOGY_FIX | IMPLEMENTED | `app/Http/Controllers/Admin/ApplicationController.php:119,128` |

The original `docs/audit/ui-copy-audit.md` remains unchanged.
