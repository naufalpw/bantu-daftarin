# UI Copy Rewrite — Phase B

Date: 2026-09-07

Scope: Landing, Services, Pusat Bantuan, related client/public navigation

Audit source: `docs/audit/ui-copy-audit.md`

Phase A reference: `docs/audit/ui-copy-rewrite-phase-a.md`

Production copy modified: YES

Domain logic modified: NO

Migration: NO expected

Dependencies: NO expected

## Progress

| Checkpoint | Status | Findings implemented | Deferred | Tests |
|---|---|---:|---:|---|
| CP0 Baseline + target extraction | COMPLETE | 0 | 0 | Baseline: 267 tests, 1,645 assertions; Vite build passed. |
| CP1 Landing | COMPLETE | 5 | 0 | `LandingPagePhaseCTest` + `QnaTest`: PASS (10 tests, 102 assertions). |
| CP2 Services | COMPLETE | 2 | 0 | Client services/UI regression tests: PASS (7 tests, 80 assertions); application cancellation service-flow regression included. |
| CP3 Pusat Bantuan | COMPLETE | 4 | 1 | `HelpCenterTest`, `QnaTest`, and contextual-help regression: PASS (11 tests, 83 assertions) |
| CP4 Terminology | COMPLETE | 1 related fix | 0 | Phase B surface scan + targeted help regressions: PASS |
| CP5 Final | COMPLETE | 11 primary + 1 related | 3 kept/deferred | Full tests, build, Pint, diff, and dependency audits passed; browser QA remains manual |

## CP0 — Baseline + target extraction

Status: COMPLETE

### Baseline

- The worktree contained 103 status entries before Phase B: 76 modified and 27
  untracked. These changes predate this phase and were preserved.
- `php artisan test --no-coverage`: PASS — 267 tests, 1,645 assertions.
- `npm run build`: PASS — Vite production build completed.
- No reset, checkout, cleanup, migration, or dependency change was performed.

The original audit and the Phase A checkpoint were read as immutable references;
neither is being edited by this phase.

### Target list

#### IMPLEMENT

- Landing: P1-001, P1-003, P1-004, P1-005, P1-006.
- Services: P1-009, P1-010.
- Pusat Bantuan: P1-012, P1-013, P1-014, P1-015.
- The landing FAQ kicker is reconciled with P1-006 as a directly-related
  public-help terminology occurrence; its `#qna` anchor remains unchanged.

#### KEEP

- P1-002: keep `Urusan NPWP jadi lebih terarah` after reviewing it with the
  simplified hero description; it remains concise and supports the visual hero
  hierarchy without making an unsupported claim.
- P1-011: keep the factual `Layanan pelaporan pajak sedang dipersiapkan.` /
  `Segera hadir` pair and its `COMING_SOON` behavior.
- P1-016: keep `Belum ada percakapan di arsip.` and `Belum ada percakapan
  tersimpan.` as deliberate factual empty states.
- Existing strong actions (`Daftar untuk mulai`, `Lihat layanan`, `Masuk`,
  `Daftar`) and product-correct process-step labels remain unchanged.

#### DEFER

- No primary Phase B ID is deferred at CP0. Any terminology found outside the
  Landing, Services, Pusat Bantuan, or directly shared public/client navigation
  surfaces will be recorded as OUT_OF_SCOPE rather than expanded into this
  phase.

## CP1 — Landing

Status: COMPLETE

Landing copy is shorter while retaining both NPWP service types, data,
documents, process visibility, real CTAs, and the non-actionable Lapor Pajak
state. The technical `#qna` anchor and all navigation destinations are unchanged.

### P1-001

Status: IMPLEMENTED

Location: Landing hero description

File: `resources/views/home.blade.php`

Original audit line: 45

Current implementation location: `resources/views/home.blade.php:37`

Original copy: `"Layanan online yang membantu Anda menyiapkan pengajuan NPWP Perseorangan dan NPWP Badan Usaha, mulai dari data, dokumen, hingga pemantauan proses."`

New copy: `"Siapkan pengajuan NPWP Perseorangan atau Badan Usaha, mulai dari data dan dokumen hingga melihat perkembangan proses."`

Decision: SHORTEN

Reason: Keeps the two real services and the data/document/process scope while
removing the long helper framing from the first screen.

Behavior changed: NO

Risk checked: No service, registration, route, or workflow capability claim was added.

### P1-003

Status: IMPLEMENTED

Location: Landing capability-list accessible name

File: `resources/views/home.blade.php`

Original audit line: 47

Current implementation location: `resources/views/home.blade.php:46`

Original copy: `"Kemampuan Bantu Daftarin"`

New copy: `"Bantuan pengajuan"`

Decision: SHORTEN

Reason: Uses a concrete product term for the three benefit items without adding
another generic visible heading. The list remains accessible through its aria label.

Behavior changed: NO

Risk checked: The three benefit items and their accessible group semantics remain present.

### P1-004

Status: IMPLEMENTED

Location: Landing service-section heading

File: `resources/views/home.blade.php`

Original audit line: 48

Current implementation location: `resources/views/home.blade.php:66`

Original copy: `"Pilih layanan sesuai kebutuhan pengajuan"`

New copy: `"Pilih layanan pengajuan"`

Decision: SHORTEN

Reason: Names the selection action and the product object; the service cards
already explain the available differences.

Behavior changed: NO

Risk checked: The `#layanan` section, service cards, and service destinations are unchanged.

### P1-005

Status: IMPLEMENTED

Location: Landing closing registration CTA

File: `resources/views/home.blade.php`

Original audit line: 49

Current implementation location: `resources/views/home.blade.php:225-226`

Original copy: `"Mulai pengajuan sesuai kebutuhan Anda"` / `"Buat akun untuk melihat persyaratan dan memulai pengajuan NPWP Perseorangan atau Badan Usaha."`

New copy: `"Siap memulai pengajuan?"` / `"Buat akun untuk melihat persyaratan NPWP Perseorangan atau Badan Usaha."`

Decision: MERGE

Reason: Removed the duplicated kicker and repeated `mulai/pengajuan` wording;
the remaining heading and description have one clear registration purpose.

Behavior changed: NO

Risk checked: Registration/login CTAs and the account requirement remain unchanged.

### P1-006

Status: IMPLEMENTED

Location: Public navigation and directly-related landing help section

File: `resources/views/components/site-header.blade.php`, `resources/views/home.blade.php`

Original audit line: 50

Current implementation locations: `resources/views/components/site-header.blade.php:16,37`; `resources/views/home.blade.php:187`

Original copy: `"FAQ"` in public desktop/mobile navigation; the related landing section kicker also used `"FAQ"`.

New copy: `"Bantuan"` in both public navigation variants and `"Pertanyaan umum"` for the landing help-section kicker.

Decision: TERMINOLOGY_FIX

Reason: Aligns visible public help vocabulary with `Bantuan` navigation and
`Pertanyaan umum` content terminology without renaming the `qna` route or anchor.

Behavior changed: NO

Risk checked: Desktop/mobile hrefs, active behavior, menu behavior, and `#qna` destination are unchanged.

### P1-002

Status: DEFERRED — KEEP

Location: Landing hero headline

File: `resources/views/home.blade.php:36`

Original copy: `"Urusan NPWP jadi lebih terarah"`

Reason: After shortening the supporting description, the headline remains a
concise, non-technical visual lead. No replacement is needed merely for variation.

Behavior changed: NO

Risk checked: The headline remains unchanged.

### CP1 verification

- `LandingPagePhaseCTest`: PASS — 8 tests, 85 assertions.
- `QnaTest`: PASS — 2 tests, 17 assertions.
- Public navigation, service anchors, registration/login destinations, and
  landing QnA behavior remain intact.

## CP2 — Services

Status: COMPLETE

The Services page now uses one orientation sentence and one action per
bookable service. The CTA destination still opens the existing requirements and
draft-start screen; prices, service status, requirements, and active-application
resume behavior are unchanged.

### P1-009

Status: IMPLEMENTED

Location: Services page heading

File: `resources/views/client/services/index.blade.php`

Original audit line: 53

Current implementation location: `resources/views/client/services/index.blade.php:10-11`

Original copy: `"Pilih layanan sesuai kebutuhan Anda" / "Bandingkan layanan, persyaratan utama, dan lanjutkan pengajuan."`

New copy: `"Pilih layanan" / "Bandingkan persyaratan sebelum memulai pengajuan."`

Decision: SHORTEN

Reason: The heading names the choice and the description states the useful
pre-start action without repeating the page purpose in a three-verb chain.

Behavior changed: NO

Risk checked: Service catalog query, price rendering, requirements, active-draft
resume, and availability filtering are unchanged.

### P1-010

Status: IMPLEMENTED

Location: Bookable service card CTA

File: `resources/views/client/services/index.blade.php`

Original audit line: 54

Current implementation location: `resources/views/client/services/index.blade.php:57`

Original copy: `"Lihat persyaratan &amp; mulai"`

New copy: `"Lihat persyaratan"`

Decision: SHORTEN

Reason: The existing destination first presents the service requirements and
then the draft-start form, so the CTA now names that first action instead of
compressing two actions with an ampersand.

Behavior changed: NO

Risk checked: The link still uses `client.applications.create` with the same
service public ID; no commitment or service creation behavior changed.

### P1-011

Status: DEFERRED — KEEP

Location: Lapor Pajak coming-soon card

File: `resources/views/client/services/index.blade.php:72-74`

Original copy: `"Layanan pelaporan pajak sedang dipersiapkan." / "Segera hadir"`

New copy: unchanged

Decision: KEEP

Reason: The pair is factual and restrained. Keeping it preserves the explicit
`COMING_SOON` state and avoids implying an active tax-reporting workflow.

Behavior changed: NO

Risk checked: No CTA, price, requirement, payment, or backend availability was added.

### CP2 verification

- `ClientUxPhaseBTest` targeted service/help cases: PASS — 4 service/help
  cases plus cancellation regression, 48 assertions.
- `AuthenticatedClientUiTest`: PASS — 2 tests, 32 assertions.
- Existing service-card routes, active-application resume, prices, and
  `COMING_SOON` semantics remain unchanged.

## CP3 — Pusat Bantuan

Status: COMPLETE

The public and authenticated help center now uses a shorter introduction,
consistent client-facing help terminology, a compact no-results explanation,
and a single clear application-context prompt. The `/qna` route, client help
search, category filtering, conversation creation, archive state, and owned
application scoping are unchanged.

### P1-012

Status: IMPLEMENTED

Location: Help-center page introduction

File: `resources/views/qna.blade.php`

Original audit line: 56

Current implementation location: `resources/views/qna.blade.php:14`

Original copy: `"Pusat Bantuan" / "Temukan jawaban untuk pertanyaan umum atau dapatkan bantuan terkait pengajuan yang sedang Anda proses."`

New copy: `"Pusat Bantuan" / "Cari jawaban umum atau bantuan untuk pengajuan Anda."`

Decision: SHORTEN

Reason: The page title already names the destination; the description now
states the two available help paths without a generic discovery phrase or a
long relative clause.

Behavior changed: NO

Risk checked: Heading hierarchy and the distinction between general help and
application help remain intact.

### P1-013

Status: IMPLEMENTED

Location: Help-center search placeholder

File: `resources/views/qna.blade.php`

Original audit line: 57

Current implementation location: `resources/views/qna.blade.php:20`

Original copy: `"Cari pertanyaan, mis. dokumen, pembayaran, revisi..."`

New copy: `"Cari pertanyaan, mis. dokumen, pembayaran, revisi…"`

Decision: FORMAT_CONSISTENCY

Reason: The concrete examples are useful and remain unchanged. The trailing
ellipsis now uses the typographic character already used by the active client
UI instead of mixing ASCII dots into this placeholder.

Behavior changed: NO

Risk checked: Search input name, JavaScript selector, autocomplete behavior,
and search semantics are unchanged.

### P1-014

Status: IMPLEMENTED

Location: Help-center no-results state

File: `resources/views/qna.blade.php`

Original audit line: 58

Current implementation location: `resources/views/qna.blade.php:53-54`

Original copy: `"Pertanyaan tidak ditemukan" / "Kami belum menemukan jawaban yang sesuai dengan “<span data-help-empty-query></span>”. Coba gunakan kata kunci lain atau tanyakan langsung kepada admin."`

New copy: `"Pertanyaan tidak ditemukan" / "Tidak ada jawaban untuk “<span data-help-empty-query></span>”. Coba kata kunci lain atau hubungi admin jika masih perlu bantuan."`

Decision: SHORTEN

Reason: The state title remains factual, while the description preserves the
query context, alternative search action, and support path in a shorter form.

Behavior changed: NO

Risk checked: The query placeholder, clear-search action, and general-support
route remain unchanged.

### P1-015

Status: IMPLEMENTED

Location: Application-context help card

File: `resources/views/qna.blade.php`

Original audit line: 59

Current implementation location: `resources/views/qna.blade.php:69-71`

Original copy: `"Bantuan pengajuan" / "Tanyakan sesuai konteks" / "Pilih pengajuan agar admin menerima konteks yang tepat."`

New copy: `"Bantuan pengajuan" / "Pilih pengajuan" / "Tim kami dapat melihat konteksnya."`

Decision: MERGE

Reason: The kicker keeps the support type, the heading gives the required
action, and the description explains why that action matters without
repeating “konteks” three times or exposing an unnecessarily internal role
label.

Behavior changed: NO

Risk checked: Only presentation text changed; application-thread routing,
ownership filtering, and context assignment remain unchanged.

### P1-016

Status: DEFERRED — KEEP

Location: Empty conversation states

File: `resources/views/qna.blade.php:108`

Original audit line: 60

Current implementation location: `resources/views/qna.blade.php:108`

Original copy: `"Belum ada percakapan di arsip." / "Belum ada percakapan tersimpan."`

New copy: unchanged

Decision: KEEP

Reason: Both states are concise and factual. No rewrite is needed merely to
create variation.

Behavior changed: NO

Risk checked: Archive and active-conversation empty-state branching is unchanged.

### Directly-related shared help terminology

Status: IMPLEMENTED

File: `app/Support/HelpFaq.php`

Current implementation location: `app/Support/HelpFaq.php:59`

Original copy fragment: `"... jawabannya belum tersedia di QnA, gunakan Hubungi Admin untuk membuka Bantuan Umum."`

New copy fragment: `"... jawabannya belum tersedia di Pusat Bantuan, gunakan Hubungi Admin untuk membuka Bantuan Umum."`

Decision: TERMINOLOGY_FIX

Reason: This answer is rendered by the public/authenticated Pusat Bantuan and
must use the client-facing page name. The technical `/qna` route and the
admin-facing `Dukungan` terminology remain unchanged.

Behavior changed: NO

Risk checked: FAQ entry IDs, categories, filtering keywords, and support
destination are unchanged.

### CP3 verification

- `HelpCenterTest.php`: PASS — 8 tests, 60 assertions.
- `QnaTest.php`: PASS — 2 tests, 17 assertions.
- `ClientUxPhaseBTest.php` contextual-help regression: PASS — 1 test, 6
  assertions.
- Combined targeted result: PASS — 11 tests, 83 assertions.
- `/qna`, search/filter behavior, support-thread behavior, archive states, and
  owned-application context remain unchanged.

## CP4 — Public/client terminology consistency

Status: COMPLETE

The changed Phase B surfaces were scanned for the requested terminology and
anti-slop patterns. No global replacement was used.

### Scan result

| Term/pattern | Result in Phase B scope | Treatment |
|---|---|---|
| `FAQ` | Fixed in shared public navigation and landing help kicker | Visible labels now use `Bantuan` and `Pertanyaan umum`; route/anchor unchanged |
| `QnA` | Fixed in the shared `HelpFaq` answer | Client-facing answer now says `Pusat Bantuan`; `/qna`, class names, test names, and route identifiers remain technical/out of scope |
| `Support` | No visible Phase B copy | Any remaining identifier/administrative concept is out of scope |
| `aplikasi` | No active visible occurrence in the Phase B surfaces | Client service requests use `pengajuan` |
| `sesuai kebutuhan` | Removed from Phase B headings/CTA copy | No broad replacement performed |
| `secara aman` | No active occurrence in the changed Phase B copy | Security/privacy language outside this scope remains unchanged |
| `mudah` | Existing testimonial/process wording is valid or out of scope for this phase | No unsupported marketing claim added |
| `praktis`, `efisien`, `Temukan`, `Kelola` | No active occurrence requiring a Phase B rewrite | No filler copy added |
| `Pantau` | Retained only in concrete process/status labels and help keywords | It names an actual status/progress action, so it is VALID |

The client-facing terminology now follows `Bantuan` (navigation), `Pusat
Bantuan` (page), `Pertanyaan umum` (general-help content), and `Bantuan
pengajuan` (application-context support). Admin-facing `Dukungan` was not
renamed.

### Regression check

- Help-center and QnA route tests remain green after the terminology fix.
- Service and landing routes, anchors, and client navigation destinations are
  unchanged.
- Application creation, help search/filter logic, support routing, and chat
  context behavior are unchanged.

## CP5 — Final verification

Status: COMPLETE

### Executable verification

- Baseline retained: 267 tests, 1,645 assertions; Vite build passed before
  Phase B edits.
- `php artisan optimize:clear`: PASS.
- `php artisan view:cache`: PASS.
- `npm run build`: PASS.
- `php artisan test --no-coverage`: PASS — 267 tests, 1,648 assertions.
- `vendor/bin/pint --test`: PASS.
- `git diff --check`: PASS.
- `npm audit --omit=dev`: PASS — 0 vulnerabilities.
- `composer audit --locked`: PASS — no security advisories.

The three additional full-suite assertions cover the Phase B copy changes;
there is no production behavior or domain-rule change.

### Manual browser acceptance

Not run in this environment. The following remains for authenticated/local
browser review at 1440, 1024, 768, 430, 393, and 360 px:

- Landing: hero wrapping, service heading, help kicker, closing CTA, and
  public navigation label.
- Services: one-action CTA clarity, service-card height, prices, and the
  unchanged `COMING_SOON` Lapor Pajak card.
- Pusat Bantuan: search placeholder, no-results state, application-context
  help, archived/active empty states, and mobile navigation.
- Confirm route transitions and help search behavior remain unchanged and no
  unsupported claims appear after copy reduction.

## Final Phase B Status

Implemented audit IDs: P1-001, P1-003, P1-004, P1-005, P1-006, P1-009,
P1-010, P1-012, P1-013, P1-014, P1-015.

Kept audit IDs: P1-002, P1-011, P1-016. These are recorded as
`DEFERRED — KEEP` because the audited copy is either context-dependent or
already factual and concise.

Deferred audit IDs: none beyond the explicit KEEP decisions above.

Out-of-scope findings discovered: authentication/security, application
workspace/forms, payment/result, cancellation, chat quick replies, admin
surfaces, accessibility implementation, and global date/number formatting.
They remain for later phases.

Files changed for Phase B:

- `resources/views/home.blade.php`
- `resources/views/components/site-header.blade.php`
- `resources/views/client/services/index.blade.php`
- `resources/views/qna.blade.php`
- `app/Support/HelpFaq.php` (directly-related shared help terminology)
- `tests/Feature/Public/LandingPagePhaseCTest.php`
- `tests/Feature/Client/ClientUxPhaseBTest.php`
- `tests/Feature/Client/AuthenticatedClientUiTest.php`
- `tests/Feature/Applications/ApplicationCancellationTest.php`
- `tests/Feature/Help/HelpCenterTest.php`
- `docs/audit/ui-copy-rewrite-phase-b.md`

Production behavior changed: NO

Domain logic changed: NO

Database/schema changed: NO

Migration: NO

Dependencies: NO

### Copy diff summary

- Descriptions removed: 1 redundant landing closing kicker.
- Descriptions shortened or merged: 5 primary copy findings.
- Strings rewritten: 6 primary copy findings with concrete replacements.
- Terminology/format fixes: 2 (`FAQ`/`QnA` client help terminology and the
  placeholder ellipsis).
- KEEP items untouched: 3 primary findings, plus existing strong actions and
  factual coming-soon/empty states.

Counts above refer to root findings, not rendered occurrences.

## Audit implementation mapping

| Audit ID | Original decision | Phase B status | Current source |
|---|---|---|---|
| P1-001 | SHORTEN | IMPLEMENTED | `resources/views/home.blade.php:37` |
| P1-002 | NEEDS_CONTEXT | DEFERRED — KEEP | `resources/views/home.blade.php:36` |
| P1-003 | SHORTEN | IMPLEMENTED | `resources/views/home.blade.php:46` |
| P1-004 | SHORTEN | IMPLEMENTED | `resources/views/home.blade.php:66` |
| P1-005 | MERGE | IMPLEMENTED | `resources/views/home.blade.php:225-226` |
| P1-006 | TERMINOLOGY_FIX | IMPLEMENTED | `resources/views/components/site-header.blade.php:16,37`; `resources/views/home.blade.php:187` |
| P1-009 | SHORTEN | IMPLEMENTED | `resources/views/client/services/index.blade.php:10-11` |
| P1-010 | SHORTEN | IMPLEMENTED | `resources/views/client/services/index.blade.php:57` |
| P1-011 | KEEP | DEFERRED — KEEP | `resources/views/client/services/index.blade.php:72-74` |
| P1-012 | SHORTEN | IMPLEMENTED | `resources/views/qna.blade.php:14` |
| P1-013 | FORMAT_CONSISTENCY | IMPLEMENTED | `resources/views/qna.blade.php:20` |
| P1-014 | SHORTEN | IMPLEMENTED | `resources/views/qna.blade.php:53-54` |
| P1-015 | MERGE | IMPLEMENTED | `resources/views/qna.blade.php:69-71` |
| P1-016 | KEEP | DEFERRED — KEEP | `resources/views/qna.blade.php:108` |

Directly-related shared terminology fix (no separate primary audit ID):
`app/Support/HelpFaq.php:59`, `QnA` → `Pusat Bantuan`.

### Final confirmations

- The original `docs/audit/ui-copy-audit.md` remains unchanged.
- The Phase A checkpoint remains unchanged.
- This Phase B checkpoint is current and contains CP0–CP5 records.
- Landing copy is shorter without removing the service meaning.
- Services CTAs communicate one primary action and still open the same
  requirements/draft destination.
- `COMING_SOON` semantics remain unchanged.
- Public/client help terminology is `Bantuan`, `Pusat Bantuan`, `Pertanyaan
  umum`, and `Bantuan pengajuan`.
- Visible `FAQ`/`QnA` terminology was removed only where in Phase B scope;
  technical `/qna` routes and identifiers were not renamed.
- Help search and support-thread behavior remain unchanged.
- No security, payment, authorization, application, or domain rule changed.
- No broad global find/replace was used.
- No migration or dependency was added.

Recommended Phase C scope: client application workspace and form/helper copy,
including document, payment, process, result, and cancellation wording, with
the same source-traceable checkpoint method.
