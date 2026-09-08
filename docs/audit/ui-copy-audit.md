# UI Copy Audit

## Metadata

- Audit date: 2026-09-07
- Repository/worktree context: `C:\Users\nopal\Documents\project-bug-squasher\bantu-daftarin`; worktree contains unrelated in-progress implementation changes that predate this audit.
- Locale audited: Bahasa Indonesia (`id`), including Indonesian strings embedded in active PHP/Blade source.
- Production source modified: NO
- Audit artifact: `docs/audit/ui-copy-audit.md`
- Email scope: transactional email body copy is excluded. Email terminology is used only as a consistency reference where the same product concept appears in the UI.

## Audit methodology

This is a read-only, incremental audit. Active routes, controllers, Livewire components, presenters, enums, shared Blade components, and rendered Blade views are treated as the source of truth. Each flagged root item records a source path, stable source line, verbatim source text, function, decision, priority, rewrite direction, and change risk. Shared copy is deduplicated by source root; repeated render locations are listed as usages rather than counted as new findings. `KEEP` notes are included where they calibrate the boundary between useful product language and cosmetic rewriting.

The required pass order is used:

1. Client public/auth
2. Client authenticated/general
3. Application workspace
4. Chat/support
5. Admin operational UI
6. Shared/system copy

Only this Markdown artifact is being created or changed. Production source line numbers remain stable during the audit.

## Established terminology reference

- Use **pengajuan** for a client's service application. Do not reopen this as a terminology debate when the UI clearly means a service application.
- Preserve **NPWP Perseorangan**, **NPWP Badan Usaha**, **Dokumen**, **Pembayaran**, and **Hasil**.
- Use **Bantuan** for client-facing help where it is the established navigation label; **Dukungan** is appropriate for the admin support/inbox context when the distinction is intentional.
- Use the Phase 2 transactional-email voice as the cross-surface reference: calm, concise, natural Indonesian, factual, minimal, and action-oriented without hype or artificial reassurance.
- Security, payment, privacy, document, and cancellation wording stays precise even when other UI copy becomes warmer.

## Pass 1 — Client public/auth

### Coverage

Inspected active public/auth sources: `resources/views/home.blade.php`, `resources/views/components/site-header.blade.php`, `resources/views/layouts/guest.blade.php`, `resources/views/qna.blade.php`, `resources/views/client/services/index.blade.php`, `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`, `resources/views/auth/otp.blade.php`, `resources/views/auth/forgot-password.blade.php`, `resources/views/auth/reset-password.blade.php`, and auth controller flash/error strings in `app/Http/Controllers/Auth/*`.

### Detailed findings

| ID | Status | Audience | Location | Copy type | File | Line | Additional source locations / usages | Current copy — VERBATIM | Issue | Decision | Priority | Naturalness / Clarity / Necessity / Consistency | Recommended direction | Risk if changed |
|---|---|---|---|---|---|---:|---|---|---|---|---|---|---|---|
| P1-001 | ACTIVE | CLIENT | Landing hero | PAGE_DESCRIPTION | `resources/views/home.blade.php` | 37 | Rendered on public landing only | `Layanan online yang membantu Anda menyiapkan pengajuan NPWP Perseorangan dan NPWP Badan Usaha, mulai dari data, dokumen, hingga pemantauan proses.` | Clear product scope, but a long “mulai dari … hingga …” chain makes the hero carry too many jobs at once. | SHORTEN | P1 | 3 / 4 / 3 / 5 | Keep the service scope and the three concrete areas, but reduce the chained framing so the first screen states one clear promise. | Removing data/document/process context could make the service vague; preserve those nouns. |
| P1-002 | ACTIVE | CLIENT | Landing hero | PAGE_DESCRIPTION | `resources/views/home.blade.php` | 36 | Public landing hero | `Urusan NPWP jadi lebih terarah` | Natural enough, but “lebih terarah” is abstract and reads like a generic service slogan without naming the user's next action. | NEEDS_CONTEXT | P2 | 3 / 3 / 3 / 4 | During rewrite, test whether a concrete preparation/action statement communicates more than this slogan; do not replace merely for variation. | A more literal line could lose the concise hero tone; validate against the visual hierarchy. |
| P1-003 | ACTIVE | CLIENT | Landing capability list | SECTION_DESCRIPTION | `resources/views/home.blade.php` | 46-57 | Three benefit items in the same shared list | `Kemampuan Bantu Daftarin` | The label names an abstract capability rather than introducing the three concrete benefits below it. | SHORTEN | P3 | 3 / 3 / 2 / 4 | Prefer a compact heading that introduces what the user gets, or remove it if the three labels are self-explanatory. | Removing the label must not make the list lose its accessible group name. |
| P1-004 | ACTIVE | CLIENT | Landing service section | PAGE_DESCRIPTION | `resources/views/home.blade.php` | 66 | Public service catalogue heading | `Pilih layanan sesuai kebutuhan pengajuan` | Useful intent, but “sesuai kebutuhan” is a recurring generic construction and the heading combines selection with a broad qualifier. | SHORTEN | P2 | 3 / 4 / 3 / 4 | Keep the selection action and product noun; remove only the non-specific qualifier if the surrounding cards already explain the service difference. | Do not imply the service catalogue supports needs it does not support. |
| P1-005 | ACTIVE | CLIENT | Landing closing CTA | PAGE_DESCRIPTION | `resources/views/home.blade.php` | 226-227 | Closing section before registration CTA | `Mulai pengajuan sesuai kebutuhan Anda` / `Buat akun untuk melihat persyaratan dan memulai pengajuan NPWP Perseorangan atau Badan Usaha.` | Two adjacent lines repeat “mulai/pengajuan” and “sesuai kebutuhan” without adding a distinct decision. | MERGE | P2 | 3 / 4 / 2 / 4 | Collapse the closing message into one concrete reason to register plus the two service names. | Keep the service choices and account requirement; do not make registration sound optional if it is required. |
| P1-006 | ACTIVE | CLIENT | Public navigation | NAVIGATION | `resources/views/components/site-header.blade.php` | 16, 37 | Desktop and mobile public navigation | `FAQ` | The public nav uses English acronym `FAQ` while the active help page and client nav use `Bantuan` / `Pusat Bantuan`. This is a visible terminology split for the same destination. | TERMINOLOGY_FIX | P1 | 3 / 4 / 4 / 2 | Align the label with the established Indonesian help vocabulary while preserving the route and information architecture. | Navigation changes affect recognition/bookmarks; keep the destination and only reconcile the label. |
| P1-007 | ACTIVE | SHARED | Guest auth shell | PAGE_DESCRIPTION | `resources/views/layouts/guest.blade.php` | 14 | Admin auth layout branch | `Layanan administrasi NPWP yang mudah dipantau.` | “Mudah dipantau” is generic reassurance and does not describe the admin's operational role. | SHORTEN | P2 | 3 / 3 / 2 / 4 | Use a compact operational description or omit the subtitle if the admin login heading already supplies context. | Security/auth page should retain a clear admin identity; do not remove the admin distinction. |
| P1-008 | ACTIVE | CLIENT | Guest auth shell | PAGE_DESCRIPTION | `resources/views/layouts/guest.blade.php` | 17 | Client login/register/reset shell | `Layanan bantuan administrasi NPWP yang dapat dipantau dari akun Anda.` | “Dapat dipantau dari akun Anda” repeats the product promise used elsewhere and is slightly stiff for a shared auth intro. | SHORTEN | P2 | 3 / 4 / 2 / 4 | State the service plainly and let the page-specific heading explain the task. | The intro is shared across multiple auth screens; keep enough context for direct-entry pages. |
| P1-009 | ACTIVE | CLIENT | Services page heading | PAGE_DESCRIPTION | `resources/views/client/services/index.blade.php` | 10-11 | `/app/services` | `Pilih layanan sesuai kebutuhan Anda` / `Bandingkan layanan, persyaratan utama, dan lanjutkan pengajuan.` | The subtitle is a three-verb chain (“bandingkan… dan lanjutkan…”) and duplicates the selection heading. | SHORTEN | P1 | 3 / 4 / 2 / 4 | Keep one useful orientation line: compare service requirements before starting an application. | Do not remove the existence of requirements or imply that a draft already exists. |
| P1-010 | ACTIVE | CLIENT | Services CTA | BUTTON | `resources/views/client/services/index.blade.php` | 57 | One CTA per bookable service card | `Lihat persyaratan &amp; mulai` | Two actions are compressed into one button, and the ampersand feels like a compact marketing label rather than a direct action. | SHORTEN | P2 | 3 / 4 / 3 / 4 | Choose one primary action that matches the destination, with requirements shown in the surrounding card. | If the destination still serves both preview and start, preserve the user's ability to review requirements before submission. |
| P1-011 | ACTIVE | CLIENT | Services empty/coming-soon card | SECTION_DESCRIPTION | `resources/views/client/services/index.blade.php` | 72-74 | `Lapor Pajak` is intentionally `COMING_SOON` | `Layanan pelaporan pajak sedang dipersiapkan.` / `Segera hadir` | This is clear and appropriately restrained. It should not be “humanized” into hype; only check that the two lines are not redundant in the final layout. | KEEP | P3 | — | Preserve the factual availability statement; remove neither the status nor the service name. | Changing it could imply the service is available or alter the explicit MVP scope. |
| P1-012 | ACTIVE | CLIENT | Help page intro | PAGE_DESCRIPTION | `resources/views/qna.blade.php` | 13-14 | `/qna` public and authenticated help page | `Pusat Bantuan` / `Temukan jawaban untuk pertanyaan umum atau dapatkan bantuan terkait pengajuan yang sedang Anda proses.` | Two help paths are joined in a generic “temukan… atau dapatkan…” construction; the sentence is longer than the page's immediate search task. | SHORTEN | P1 | 3 / 4 / 3 / 5 | State that users can search common questions and get help for an active pengajuan. | Preserve the distinction between general answers and contextual support. |
| P1-013 | ACTIVE | CLIENT | Help search | FORM_PLACEHOLDER | `resources/views/qna.blade.php` | 20 | Public/authenticated help search | `Cari pertanyaan, mis. dokumen, pembayaran, revisi...` | Useful searchable examples, but the ASCII three-dot ending is inconsistent with the product's otherwise typographic punctuation and is slightly crowded. | FORMAT_CONSISTENCY | P3 | 4 / 4 / 4 / 3 | Keep the concrete examples; standardize the ellipsis/punctuation convention with the rest of the UI. | Do not remove examples; they reduce search ambiguity. |
| P1-014 | ACTIVE | CLIENT | Help no-results state | EMPTY_STATE_DESCRIPTION | `resources/views/qna.blade.php` | 53-54 | Appears after filtered FAQ search | `Pertanyaan tidak ditemukan` / `Kami belum menemukan jawaban yang sesuai dengan “<span data-help-empty-query></span>”. Coba gunakan kata kunci lain atau tanyakan langsung kepada admin.` | Helpful next action, but the sentence is long and the “belum menemukan… sesuai…” construction is generic. | SHORTEN | P2 | 3 / 4 / 4 / 4 | Keep the searched term and two available next steps, but split or tighten the copy so the state is scannable. | Removing the admin path could hide a legitimate support route; preserve it. |
| P1-015 | ACTIVE | CLIENT | Help contextual support | SECTION_DESCRIPTION | `resources/views/qna.blade.php` | 69-71 | Application-specific help card | `Bantuan pengajuan` / `Tanyakan sesuai konteks` / `Pilih pengajuan agar admin menerima konteks yang tepat.` | “Sesuai konteks” is abstract and the three lines repeat the same idea (contextual application support). | MERGE | P2 | 3 / 4 / 2 / 5 | Keep one short explanation that selecting a pengajuan sends the relevant context to admin. | Must preserve the operational reason for selecting an application. |
| P1-016 | ACTIVE | CLIENT | Help conversation empty state | EMPTY_STATE_DESCRIPTION | `resources/views/qna.blade.php` | 108 | Active/archived conversation lists | `Belum ada percakapan di arsip.` / `Belum ada percakapan tersimpan.` | The copy is clear, but the two state variants should be treated as a deliberate pair; do not add motivational filler. | KEEP | P3 | — | Retain as factual empty-state copy; only standardize punctuation/spacing if the shared empty-state system requires it. | Changing “tersimpan” or “arsip” could blur the distinction between active and archived conversations. |
| P1-017 | ACTIVE | CLIENT | Auth login verification notice | SECURITY_NOTICE | `resources/views/auth/login.blade.php` | 48-50 | Shown when login is attempted before email verification | `Verifikasi email Anda` / `Kami mengirim link verifikasi ke <b>{{ session('verification_email') }}</b>. Buka link tersebut sebelum login.` / `Kirim ulang link verifikasi` | English “link” conflicts with the established Indonesian transactional copy (“tautan”); the action is precise but repeated. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Replace only the user-facing English term with the established Indonesian term and keep the verification-before-login instruction. | This is security flow copy; do not soften or remove the prerequisite. |
| P1-018 | ACTIVE | CLIENT | Registration intro | PAGE_DESCRIPTION | `resources/views/auth/register.blade.php` | 10-11 | `/register` | `Buat Akun Baru` / `Daftar untuk membuat akun dan memulai pengajuan NPWP. Setelah mendaftar, verifikasi email Anda sebelum masuk.` | Clear but repeats “daftar/mendaftar” and places three sequential actions in one line. | SHORTEN | P2 | 3 / 5 / 4 / 5 | Keep account creation, pengajuan, and email verification; reduce repeated verbs and preserve ordering. | Removing verification wording would weaken an important auth expectation. |
| P1-019 | ACTIVE | CLIENT | Registration submit button | BUTTON | `resources/views/auth/register.blade.php` | 48 | `/register` | `Daftar dan kirim verifikasi` | Understandable, but it exposes an implementation sequence (“kirim verifikasi”) rather than the user's primary action. | SHORTEN | P2 | 3 / 4 / 3 / 4 | Use a concise registration action and explain verification in the nearby helper/flash copy. | Must not imply the account is immediately usable before verification. |
| P1-020 | ACTIVE | CLIENT | Password recovery request | PAGE_DESCRIPTION / BUTTON | `resources/views/auth/forgot-password.blade.php` | 5-10 | `/forgot-password` | `Masukkan email akun Anda. Jika cocok, kami akan mengirim tautan reset.` / `Kirim tautan reset` | “Reset” is mixed English while the page title already uses “Atur ulang kata sandi”; the helper is also intentionally vague for account enumeration protection. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Align the user-facing noun with “atur ulang kata sandi” without changing the non-disclosing response behavior. | Do not make the response confirm whether an email exists. |
| P1-021 | ACTIVE | CLIENT | OTP screen | SECURITY_NOTICE | `resources/views/auth/otp.blade.php` | 8-9, 34-35 | Admin and client branches duplicate the same explanation | `Masukkan kode OTP` / `Masukkan 6 digit OTP yang dikirim ke email terdaftar. Satu OTP hanya dapat dipakai sekali.` | Security wording is precise and should stay formal; the same root copy is independently duplicated in two branches. | DUPLICATE_COPY | P1 | 5 / 5 / 5 / 3 | Consolidate the shared presentation later while preserving “6 digit”, single-use, and registered-email meaning. | Any rewrite must retain OTP length and one-time-use semantics. |
| P1-022 | ACTIVE | CLIENT / ADMIN | OTP resend | SECURITY_NOTICE | `resources/views/auth/otp.blade.php` | 20-27, 43-50 | Admin and client branches | `Kirim ulang OTP tersedia dalam <span data-otp-countdown>{{ $resendCooldownSeconds }}</span> detik.` / `Belum menerima OTP? Anda dapat mengirim ulang sekarang.` / `Kirim ulang OTP` | The cooldown and resend instruction are clear; the first line is slightly mechanical but this is a security/control state, not AI-slop. | KEEP | P3 | — | Preserve the factual cooldown state and explicit resend action. | Changing it could confuse cooldown timing or encourage unsafe repeated attempts. |
| P1-023 | ACTIVE | CLIENT | Auth error flash | ERROR | `app/Http/Controllers/Auth/AuthController.php` | 54 | Login failure flash | `Email atau password tidak sesuai.` | “Password” is English while the active form label is “Kata sandi”; terminology is inconsistent at the exact point of failure. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Align the user-facing term with “kata sandi” while retaining non-specific credential failure wording. | Do not reveal which credential was incorrect. |
| P1-024 | ACTIVE | CLIENT | Registration success flash | SUCCESS | `app/Http/Controllers/Auth/AuthController.php` | 39 | Redirect after registration | `Pendaftaran berhasil. Periksa email Anda untuk verifikasi sebelum login.` | Clear, calm, and operational. It contains the next action without hype. | KEEP | P3 | — | Preserve as a strong example of product language. | Changing it risks weakening the verification instruction. |
| P1-025 | ACTIVE | CLIENT | Verification success flash | SUCCESS | `app/Http/Controllers/Auth/EmailVerificationController.php` | 24 | Redirect after signed verification | `Email berhasil diverifikasi. Silakan login.` | Short, factual, and actionable. | KEEP | P3 | — | Preserve. | No meaningful copy risk. |

### Strong copy to preserve in Pass 1

- `resources/views/home.blade.php:39-40` — `Daftar untuk mulai` and `Lihat layanan`: short, concrete CTAs.
- `resources/views/home.blade.php:119-136` — the five-step labels (`Pilih layanan`, `Lengkapi data`, `Unggah dokumen`, `Pembayaran & pemeriksaan`, `Pantau proses & hasil`) are operationally legible; review only for consistency with the actual workflow, not for cosmetic variation.
- `resources/views/components/site-header.blade.php:20-21` — `Masuk` and `Daftar` are appropriately concise.
- `resources/views/auth/forgot-password.blade.php:5` and `resources/views/auth/reset-password.blade.php:5` — `Atur ulang kata sandi` / `Buat kata sandi baru` establish the preferred Indonesian recovery vocabulary.
- `resources/views/qna.blade.php:80` — `Tanya tentang pengajuan ini` is a concrete, context-specific CTA.

## Pass 2 — Client authenticated

### Coverage

Inspected active authenticated client sources: `resources/views/components/client-header.blade.php`, `resources/views/components/mobile-bottom-nav.blade.php`, `resources/views/layouts/client.blade.php`, `resources/views/client/dashboard.blade.php`, `resources/views/client/applications/index.blade.php`, `resources/views/components/application-list-item.blade.php`, `resources/views/client/activity/index.blade.php`, `resources/views/client/activity/show.blade.php`, `app/Support/ApplicationStatusPresenter.php`, `app/Support/PaymentStatusPresenter.php`, and the active client routes in `routes/client.php`.

### Detailed findings

| ID | Status | Audience | Location | Copy type | File | Line | Additional source locations / usages | Current copy — VERBATIM | Issue | Decision | Priority | Naturalness / Clarity / Necessity / Consistency | Recommended direction | Risk if changed |
|---|---|---|---|---|---|---:|---|---|---|---|---|---|---|---|
| P2-001 | ACTIVE | CLIENT | Dashboard intro | PAGE_DESCRIPTION | `resources/views/client/dashboard.blade.php` | 10-11 | `/app/dashboard` | `Selamat datang, {{ auth()->user()->name }}` / `Lanjutkan hal yang perlu Anda tindaklanjuti atau lihat perkembangan pengajuan Anda.` | Greeting is fine; the second line combines “next action” and “progress” into an abstract two-purpose sentence. | SHORTEN | P2 | 3 / 4 / 3 / 5 | Keep the warm greeting and state one concrete dashboard purpose; let the priority card carry the next action. | Do not remove the personalized greeting or make it overly casual. |
| P2-002 | ACTIVE | CLIENT | Dashboard empty state | EMPTY_STATE_DESCRIPTION | `resources/views/client/dashboard.blade.php` | 17-20 | New client with no applications | `Mulai pengajuan` / `Belum ada pengajuan` / `Pilih layanan untuk memulai pengajuan pertama Anda.` | Clear and actionable; this is a strong empty-state pattern rather than slop. | KEEP | P3 | — | Preserve title, one-sentence next action, and `Lihat layanan` CTA. | Rewriting could make the first-use path less clear. |
| P2-003 | ACTIVE | CLIENT | Dashboard application summary | EMPTY_STATE_DESCRIPTION | `resources/views/client/dashboard.blade.php` | 68-70 | Empty active-application list | `Tidak ada pengajuan yang sedang berjalan.` / `Pengajuan yang dibatalkan tetap tersedia pada riwayat Pengajuan.` | Useful consequence and destination, but “riwayat Pengajuan” is not visibly named as a separate navigation item and may be confusing. | NEEDS_CONTEXT | P2 | 4 / 4 / 4 / 4 | Verify the destination label in the current navigation; if it is the same Pengajuan list, say that cancelled items can be found there without inventing a “history” section. | Do not hide the availability of cancelled records. |
| P2-004 | ACTIVE | CLIENT | Dashboard service shortcut | PAGE_DESCRIPTION | `resources/views/client/dashboard.blade.php` | 95-96 | Repeated on services/landing | `Butuh layanan lain?` / `Pilih layanan sesuai kebutuhan Anda` | The question plus repeated “sesuai kebutuhan” construction adds a generic promotional layer to a simple link group. | SHORTEN | P2 | 3 / 4 / 2 / 4 | Keep the alternate-service intent; remove the repeated qualifier if card labels already identify services. | Must retain a path to available services and not advertise `COMING_SOON` as available. |
| P2-005 | ACTIVE | CLIENT | Application list intro | PAGE_DESCRIPTION | `resources/views/client/applications/index.blade.php` | 10-11 | `/app/applications` | `Semua pengajuan Anda` / `Lihat yang perlu ditindaklanjuti, sedang diproses, atau sudah selesai.` | Factual and understandable, but the description is a status list that duplicates the filters immediately below. | SHORTEN | P2 | 4 / 4 / 2 / 5 | Keep the title; shorten or remove the description if the filter labels provide the same orientation. | Keep the filters and status distinctions; only remove duplicate framing. |
| P2-006 | ACTIVE | CLIENT | Application-list shared fallback | OTHER | `resources/views/components/application-list-item.blade.php` | 16 | Used by dashboard and application list when service relation is absent | `Layanan NPWP` | Safe fallback and not user-facing in normal valid data. | KEEP | P3 | — | Preserve as a defensive fallback; do not spend rewrite effort unless it appears in active data. | Changing fallback could mask data-integrity context. |
| P2-007 | ACTIVE | CLIENT | Application-list CTA fallback | BUTTON | `resources/views/components/application-list-item.blade.php` | 8 | Shared across application cards | `Lihat pengajuan` | Concrete and consistent with the product term. | KEEP | P3 | — | Preserve. | No meaningful risk. |
| P2-008 | ACTIVE | CLIENT | Activity page section label | SECTION_TITLE | `resources/views/client/activity/index.blade.php` | 17 | Payment activity accordion | `Status Trasnsaksi` | Typographical error (“Trasnsaksi”) is visible in a section heading. | REWRITE | P1 | 1 / 5 / 5 / 1 | Correct the spelling in a source-controlled copy pass; keep the intended transaction-status meaning. | Low semantic risk; validate the final label against payment terminology. |
| P2-009 | ACTIVE | CLIENT | Activity page section label | SECTION_TITLE | `resources/views/client/activity/index.blade.php` | 45 | Process activity accordion | `Proses Pengerjaan` | “Pengerjaan” is less consistent with the established workflow vocabulary (`Proses`, `pemrosesan`, `pengajuan`) and can sound like a generic task board. | TERMINOLOGY_FIX | P2 | 3 / 4 / 3 / 3 | Align with the established process/history vocabulary after checking the intended product concept. | This may be a domain label rather than a style issue; confirm against the activity information architecture. |
| P2-010 | ACTIVE | CLIENT | Activity page section label | SECTION_TITLE | `resources/views/client/activity/index.blade.php` | 72 | Third activity accordion | `Pesanan` | The product primarily uses `pengajuan`; “Pesanan” may imply an e-commerce order and is not self-explanatory in this context. | DOMAIN_REVIEW | P2 | 2 / 3 / 3 / 2 | Confirm whether this section is intended as payment/order history; then use the established domain term. | Renaming without confirming the data meaning could misrepresent the activity records. |
| P2-011 | ACTIVE | CLIENT | Activity fallback label | OTHER | `resources/views/client/activity/index.blade.php` | 55, 80 | Process/order rows when service relation is missing | `Layanan aplikasi` | User-facing fallback uses `aplikasi`, conflicting with the established `pengajuan` vocabulary. | TERMINOLOGY_FIX | P1 | 3 / 4 / 3 / 2 | Use a safe service/pengajuan fallback that matches the current product language, only for missing relation data. | Keep fallback behavior defensive; do not alter the underlying relation/query. |
| P2-012 | ACTIVE | CLIENT | Activity empty states | EMPTY_STATE_TITLE | `resources/views/client/activity/index.blade.php` | 38, 65, 91 | Payment/process/order accordions | `Belum ada transaksi pembayaran.` / `Belum ada riwayat proses pengerjaan.` / `Belum ada pesanan.` | Payment empty state is clear. The latter two inherit the “pengerjaan”/“pesanan” terminology uncertainty. | DOMAIN_REVIEW | P2 | 3 / 4 / 4 / 3 | Keep factual empty-state structure; resolve section terminology first, then align the titles. | Avoid changing empty states independently of their section meaning. |
| P2-013 | ACTIVE | CLIENT | Activity detail labels | SECTION_TITLE / STATUS | `resources/views/client/activity/show.blade.php` | 38, 66-67 | Activity detail page | `Detail Pembayaran` / `Status Pengerjaan` | `Detail Pembayaran` is clear; `Status Pengerjaan` repeats the same ambiguous wording from the activity index. | DOMAIN_REVIEW | P2 | 3 / 4 / 4 / 3 | Preserve payment label; resolve whether the second card means workflow status, processing status, or service progress before rewriting. | Wrong replacement could obscure workflow state. |
| P2-014 | ACTIVE | CLIENT | Activity date/time | FORMAT_LABEL | `resources/views/client/activity/index.blade.php` | 32, 58, 85 | Activity detail uses the same family with different ordering | `d M Y, H:i` | Date format is internally consistent within this older activity surface but should be compared to the dominant client convention (`d M Y` and `d M Y, H:i`) and translated-format usage elsewhere. | FORMAT_CONSISTENCY | P2 | 4 / 4 / 4 / 3 | Choose one documented Indonesian date/time convention for client activity and apply it consistently in a later copy/format pass. | Date formatting is functional; do not alter timezone or timestamp meaning. |
| P2-015 | ACTIVE | CLIENT | Application status presenter | STATUS / OPERATIONAL_COPY | `app/Support/ApplicationStatusPresenter.php` | 61-78 | Drives dashboard, application cards, and workspace status copy | `Data pengajuan masih dapat dilengkapi.`; `Dokumen wajib belum lengkap.`; `Dokumen minimum telah lengkap.`; `Pembayaran belum diterima.`; `Tunggu hasil pemeriksaan.`; `Satu atau beberapa dokumen memerlukan perbaikan.`; `Pengajuan sedang diproses.`; `Proses dilanjutkan secara manual di luar aplikasi.`; `Hasil terverifikasi tersedia di ruang pengajuan.` | Most labels are concise and domain-accurate. The “Pantau proses dan estimasi yang tersedia.” next-action line (line 72) is abstract and the “di luar aplikasi” phrase (line 73) leaks an internal/system framing. | SHORTEN | P2 | 3 / 4 / 4 / 4 | Preserve the state machine meaning; make next actions concrete and use product-facing wording for external processing. | Status/CTA copy is coupled to workflow; do not imply a client can act when no action is available. |
| P2-016 | ACTIVE | CLIENT | Application status presenter | STATUS | `app/Support/ApplicationStatusPresenter.php` | 72 | `IN_PROGRESS` status | `Pantau proses dan estimasi yang tersedia.` | Generic “pantau” plus an abstract “yang tersedia” is a recurring AI/corporate pattern; it does not tell the user what is currently available. | SHORTEN | P2 | 2 / 4 / 3 / 4 | State only the available status/estimate information, or omit the line when there is no actionable next step. | Do not promise an estimate if the backend has not supplied one. |
| P2-017 | ACTIVE | CLIENT | Payment status presenter | STATUS | `app/Support/PaymentStatusPresenter.php` | 13-24 | Payment workspace and activity surfaces | `Pembayaran kedaluwarsa`; `Menunggu pembayaran`; `Pembayaran berhasil`; `Pembayaran gagal`; `Pembayaran dibatalkan`; `Pengembalian diajukan`; `Pengembalian diproses`; `Dana telah dikembalikan`; `Belum ada pembayaran` | Stable, factual status vocabulary. | KEEP | P3 | — | Preserve; statuses should not be made conversational. | Changing stable labels could break operational interpretation and tests. |

### Strong copy to preserve in Pass 2

- `resources/views/components/client-header.blade.php:22-25` and `resources/views/components/mobile-bottom-nav.blade.php:4-7` use a compact, consistent navigation vocabulary (`Beranda`, `Layanan`, `Pengajuan`, `Bantuan`).
- `resources/views/client/dashboard.blade.php:17-20` is a clear first-use empty state with one action.
- `resources/views/components/application-list-item.blade.php:28` (`Diperbarui` / `Dibatalkan` plus a localized date) is concise metadata; only the global date convention needs documenting.
- `app/Support/PaymentStatusPresenter.php:17-24` uses factual payment labels appropriate for a high-stakes flow.

## Pass 3 — Application workspace

### Coverage

Inspected the active shared workspace path (`resources/views/client/applications/create.blade.php`, `resources/views/client/applications/show.blade.php`, `resources/views/livewire/application-details-form.blade.php`, `resources/views/components/application-progress.blade.php`, `resources/views/components/personal-document-card.blade.php`, `resources/views/components/personal-face-document.blade.php`, and `resources/views/client/payment/show.blade.php`). The legacy `resources/views/client/registration/*.blade.php` files were also checked against `app/Http/Controllers/Client/RegistrationController.php`; the current `/npwp-*` routes redirect into the application workspace, so those standalone registration views are classified as `LEGACY/UNUSED` unless another call site is found.

### Detailed findings

| ID | Status | Audience | Location | Copy type | File | Line | Additional source locations / usages | Current copy — VERBATIM | Issue | Decision | Priority | Naturalness / Clarity / Necessity / Consistency | Recommended direction | Risk if changed |
|---|---|---|---|---|---|---:|---|---|---|---|---|---|---|---|
| P3-001 | ACTIVE | CLIENT | Workspace header | PAGE_TITLE / NAVIGATION | `resources/views/client/applications/show.blade.php` | 31-35 | All application workspaces | `Ruang pengajuan` / `ID Pengajuan: …{{ $shortId }}` / `Salin ID` | Clear, product-aligned, and safely uses a shortened public identifier. | KEEP | P3 | — | Preserve. | Do not expose the internal ID or alter the authorization model behind copy/share. |
| P3-002 | ACTIVE | CLIENT | Workspace status/progress | SECTION_DESCRIPTION | `resources/views/client/applications/show.blade.php` | 42, 47-48, 55-57 | Dynamic status presenter supplies the next action | `Estimasi yang tercatat: {{ $application->estimated_completion_at->translatedFormat('d M Y') }}` / `Pengajuan telah dihentikan` / `Riwayat tetap tersimpan sebagai konteks baca-saja.` / `Langkah berikutnya` | These lines are factual and appropriately serious. “Sebagai konteks baca-saja” is technical but accurately communicates the cancelled read-only state. | KEEP | P3 | — | Preserve; only revisit if user testing shows “baca-saja” is misunderstood. | Softening cancellation language could misstate workflow finality. |
| P3-003 | ACTIVE | CLIENT | Data/document section | SECTION_DESCRIPTION / FORMAT_LABEL | `resources/views/client/applications/show.blade.php` | 75-76 | Shared personal/business workspace | `Lengkapi informasi dan persyaratan` / `File disimpan secara private` | “Private” is unnecessary English in an otherwise Indonesian security note; the sentence is important but can be expressed in product language. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Keep the privacy guarantee and align the noun/adjective with Indonesian terminology without weakening access restrictions. | Must preserve the claim that only authorized parties can access files. |
| P3-004 | ACTIVE | CLIENT | Locked data state | SECURITY_NOTICE | `resources/views/client/applications/show.blade.php` | 83-84 | Non-editable workspace stages | `Data pengajuan sudah dikunci pada tahap ini.` / `Informasi sensitif tidak ditampilkan ulang. Hubungi tim melalui chat pengajuan jika ada data yang perlu diklarifikasi.` | Precise and calm security/workflow copy. | KEEP | P3 | — | Preserve as a strong example; it explains state, privacy, and next action without filler. | Removing the chat path would make the locked state less actionable. |
| P3-005 | ACTIVE | CLIENT | Personal document section | SECTION_DESCRIPTION | `resources/views/client/applications/show.blade.php` | 92-93 | Personal document card group | `Siapkan dokumen pendukung` / `Pastikan dokumen terlihat jelas dan sesuai persyaratan.` | Useful upload guidance, though “Pastikan” is a generic imperative repeated elsewhere. | KEEP | P3 | — | Keep while auditing globally for repetition; it earns space because clarity of uploads matters. | Do not shorten away the visibility/requirement criteria. |
| P3-006 | ACTIVE | CLIENT | Business document section | SECTION_DESCRIPTION | `resources/views/client/applications/show.blade.php` | 133-134 | Business document card group | `Siapkan dokumen badan usaha` / `Pastikan setiap dokumen jelas dan sesuai dengan persyaratan pengajuan.` | Same repeated “Pastikan … sesuai persyaratan” construction as the personal section; the business noun is useful. | SHORTEN | P2 | 3 / 4 / 4 / 4 | Keep the business-specific heading and one concise requirement reminder; avoid duplicating the personal helper verbatim. | Preserve the requirement reminder and business-specific context. |
| P3-007 | ACTIVE | CLIENT | Business document semantics | HELPER_TEXT | `resources/views/client/applications/show.blade.php` | 147-150 | Business requirement-specific descriptions | `Unggah foto atau salinan KTP penanggung jawab utama.` / `Unggah salinan akta notaris badan usaha.` / `Unggah salinan SK AHU badan usaha.` / `Unggah surat kuasa bila pengajuan diwakilkan.` | These are concrete, business-specific, and preserve conditional Surat Kuasa semantics. | KEEP | P3 | — | Preserve; do not generalize into personal document names or make Surat Kuasa always required. | Any simplification must keep requirement semantics and representative context. |
| P3-008 | ACTIVE | CLIENT | Document card empty state | EMPTY_STATE_DESCRIPTION | `resources/views/client/applications/show.blade.php` | 119, 158 | Personal and business branches | `Belum ada persyaratan dokumen untuk pengajuan ini.` | Clear defensive empty state. | KEEP | P3 | — | Preserve. | Changing it could incorrectly imply a missing backend requirement is a user action. |
| P3-009 | ACTIVE | CLIENT | Draft submit CTA | SECTION_DESCRIPTION / BUTTON | `resources/views/client/applications/show.blade.php` | 167-168 | Draft application | `Data awal sudah lengkap?` / `Lanjutkan untuk membuka tahap pengumpulan dokumen.` / `Lanjut ke dokumen` | Clear state gate and concrete action. | KEEP | P3 | — | Preserve. | This copy communicates the workflow transition; avoid making it sound like document upload is already complete. |
| P3-010 | ACTIVE | CLIENT | Revision submit CTA | SECTION_DESCRIPTION | `resources/views/client/applications/show.blade.php` | 173-174 | Revision-required application | `Semua perbaikan sudah diunggah?` / `Kirim perbaikan setelah seluruh instruksi ditindaklanjuti.` / `Kirim perbaikan` | The question/CTA are clear; “seluruh instruksi ditindaklanjuti” is formal and indirect. | SHORTEN | P2 | 3 / 5 / 4 / 4 | Use a direct statement that all requested changes must be completed before submission. | Keep the gating condition; do not allow copy to imply partial revisions can be submitted as complete. |
| P3-011 | ACTIVE | CLIENT | Payment section | SECTION_DESCRIPTION | `resources/views/client/applications/show.blade.php` | 181 | Workspace payment section | `Status dan rincian pembayaran` | Clear and appropriately factual. | KEEP | P3 | — | Preserve. | Payment labels should remain stable. |
| P3-012 | ACTIVE | CLIENT | Payment gate | HELPER_TEXT | `resources/views/client/applications/show.blade.php` | 195 | Payment unavailable before required data/documents | `Pembayaran belum dibuka. Lengkapi data dan dokumen wajib terlebih dahulu.` | Precise gating explanation and next action. | KEEP | P3 | — | Preserve. | Changing it could misstate payment eligibility. |
| P3-013 | ACTIVE | CLIENT | Result locked state | SECURITY_NOTICE | `resources/views/client/applications/show.blade.php` | 234-236 | Result uploaded/review and empty result states | `Hasil belum dapat dilihat atau diunduh sampai verifikasi selesai.` / `Hasil belum tersedia.` / `Dokumen hasil akan muncul di sini setelah proses selesai dan hasil diverifikasi.` | Clear authorization/workflow boundary and useful empty state. | KEEP | P3 | — | Preserve. | Do not imply result access before VERIFIED. |
| P3-014 | ACTIVE | CLIENT | Support sidebar | SECTION_DESCRIPTION | `resources/views/client/applications/show.blade.php` | 267-272 | Application workspace support card | `Butuh bantuan?` / `Bantuan umum tersedia di QnA. Pertanyaan khusus pengajuan ini dapat dikirim melalui chat.` / `Tanya tentang pengajuan ini` / `Buka Bantuan` | “QnA” conflicts with the active `Pusat Bantuan`/`Bantuan` product vocabulary; the rest is useful contextual support copy. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Use the established help label while preserving the distinction between general help and application chat. | Do not collapse the two support destinations; they have different contexts. |
| P3-015 | ACTIVE | CLIENT | Cancellation settings | WARNING / BUTTON | `resources/views/client/applications/show.blade.php` | 277-293 | Cancellation eligibility branches | `Tidak ingin melanjutkan?` / `Anda dapat membatalkan selama pembayaran belum dikonfirmasi. Catatan tetap tersimpan.` / `Pembatalan mandiri tidak tersedia setelah pembayaran dikonfirmasi. Jika Anda mengalami kendala, hubungi Tim Bantu Daftarin.` / `Batalkan pengajuan` / `Buka bantuan` | Serious, accurate, and appropriately non-playful. | KEEP | P3 | — | Preserve; this is domain/legal-risk copy rather than a tone-polish target. | Any rewrite must preserve the payment-confirmation boundary and support fallback. |
| P3-016 | ACTIVE | CLIENT | Cancellation confirmation | MODAL_TITLE / MODAL_BODY | `resources/views/client/applications/show.blade.php` | 309-328 | Cancellation dialog | `Batalkan pengajuan?` / `Pengajuan akan dihentikan dan tidak akan diproses lebih lanjut. Catatan pengajuan tetap tersimpan.` / `Alasan pembatalan` / `Ya, batalkan pengajuan` | Clear consequence and confirmation; formal tone is justified. | KEEP | P3 | — | Preserve. | Changing consequence wording could create a legal/workflow ambiguity. |
| P3-017 | ACTIVE | CLIENT | Application creation intro | PAGE_DESCRIPTION | `resources/views/client/applications/create.blade.php` | 25-27 | Service-specific creation page | `Persyaratan &amp; mulai` / `Tinjau biaya dan dokumen yang perlu disiapkan. Draft dibuat setelah data awal dan persetujuan disimpan.` | “Persyaratan & mulai” is compressed, while the description is useful but carries two steps. | SHORTEN | P2 | 3 / 4 / 4 / 4 | Keep cost/document review and the draft creation condition, but use one clear sentence or a more direct heading. | Must preserve that the draft is created only after required initial data/consent. |
| P3-018 | ACTIVE | CLIENT | Application creation privacy | SECURITY_NOTICE | `resources/views/client/applications/create.blade.php` | 52 | Before initial data form | `Dokumen akan disimpan secara private dan hanya dapat dibuka oleh pihak yang berwenang dalam pengajuan.` | Important security statement, but “private” is mixed language and repeats the shared workspace issue. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Retain the authorized-access guarantee and standardize the Indonesian privacy term across workspace surfaces. | Do not weaken the security promise or imply public file access. |
| P3-019 | ACTIVE | CLIENT | Application form helper | HELPER_TEXT | `resources/views/client/applications/create.blade.php` | 142-143 | Initial creation form | `Setelah draft dibuat, data lengkap dan dokumen dikelola dari ruang pengajuan.` / `Buat draft pengajuan` | Clear workflow explanation and concrete action. | KEEP | P3 | — | Preserve. | It anchors the transition into the workspace. |
| P3-020 | ACTIVE | CLIENT | Consent notice | SECURITY_NOTICE | `resources/views/client/applications/create.blade.php` | 137 | Initial application form | `Saya menyetujui pemrosesan data untuk layanan ini. Saya memahami Bantu Daftarin adalah layanan bantuan administrasi, bukan portal resmi pemerintah.` | Necessary legal/product boundary; formal wording is appropriate. | KEEP | P1 | — | Preserve; only review punctuation/accessibility layout in implementation, not meaning. | Any shortening risks losing the non-government-portal disclosure. |
| P3-021 | ACTIVE | CLIENT | Autosave status | HELPER_TEXT | `resources/views/livewire/application-details-form.blade.php` | 15-20 | Shared personal/business data form | `Data pengajuan` / `Perubahan disimpan saat Anda berpindah dari kolom.` / `Menyimpan...` | The autosave explanation is useful but “berpindah dari kolom” sounds like implementation language; loading punctuation also differs from the reactive admin convention. | SHORTEN | P2 | 3 / 4 / 4 / 4 | Explain autosave in user terms and standardize the short saving/loading pattern across forms. | Must not imply save happens before the blur-triggered persistence. |
| P3-022 | ACTIVE | CLIENT | Business form group | SECTION_DESCRIPTION | `resources/views/livewire/application-details-form.blade.php` | 38-39 | Business application data | `Data badan usaha` / `Isi informasi badan usaha sesuai dokumen pendukung yang akan diunggah.` | Specific and helpful; it explains why the fields should match documents. | KEEP | P3 | — | Preserve. | Changing it could reduce data consistency guidance. |
| P3-023 | ACTIVE | CLIENT | Representative fieldset | SECTION_DESCRIPTION | `resources/views/livewire/application-details-form.blade.php` | 52-53 | Primary business representative | `Penanggung jawab utama` / `Masukkan pihak yang bertanggung jawab atas pengajuan badan usaha ini.` | Accurate, but the second line is formal and repeats the heading's meaning. | SHORTEN | P2 | 3 / 5 / 3 / 5 | Keep the representative requirement; reduce the sentence to the role/context not already obvious from the legend. | Preserve the distinction between primary and additional representatives. |
| P3-024 | ACTIVE | CLIENT | Additional representative | HELPER_TEXT | `resources/views/livewire/application-details-form.blade.php` | 62-63 | Optional business representative | `Penanggung jawab tambahan <small>Opsional</small>` / `Isi data penanggung jawab tambahan jika diperlukan.` | Clear, properly marks optionality, and earns its space. | KEEP | P3 | — | Preserve. | Do not make the optional representative appear required. |
| P3-025 | ACTIVE | CLIENT | Manual save helper | HELPER_TEXT | `resources/views/livewire/application-details-form.blade.php` | 72 | Shared form footer | `Anda juga dapat menyimpan secara manual.` | Redundant beside the visible `Simpan data` button and uses a generic “Anda juga dapat” construction. | REMOVE | P2 | 2 / 5 / 1 / 3 | Remove the sentence or retain only if autosave/manual-save distinction is not otherwise visible; the button label already explains the action. | Do not remove the manual-save control itself. |
| P3-026 | ACTIVE | CLIENT | Personal face upload | HELPER_TEXT | `resources/views/components/personal-face-document.blade.php` | 20, 30 | Personal-only requirement | `Pastikan wajah terlihat jelas sebelum mengambil atau mengunggah foto.` / `Tips Foto yang Baik` | The instruction is useful. The title uses unnecessary title case and the generic “baik” wording. | SHORTEN | P2 | 3 / 4 / 4 / 4 | Keep concrete face/photo requirements; use a compact, sentence-case heading. | Do not remove image-quality guidance; it supports successful upload. |
| P3-027 | ACTIVE | CLIENT | Personal face privacy | SECURITY_NOTICE | `resources/views/components/personal-face-document.blade.php` | 53-57 | Personal face document card | `Dokumen hanya dapat diakses sesuai hak akses pengajuan.` / `Maks. {{ $maxMegabytes }} MB.` | Precise privacy and upload-limit copy; should remain formal. | KEEP | P1 | — | Preserve. | Do not make privacy language casual or less specific. |
| P3-028 | ACTIVE | CLIENT | Document upload card | HELPER_TEXT | `resources/views/components/personal-document-card.blade.php` | 52-53, 69-72 | Personal and business cards reuse this component | `Ganti dokumen` / `Pilih file untuk mengganti dokumen.` / `Belum ada file dipilih.` / `Format: {{ $formats ?: 'sesuai ketentuan' }} &middot; Maks. {{ $maxMegabytes }} MB` | Concrete state/action copy. The fallback `sesuai ketentuan` is vague only when backend format data is absent; this is a defensive case. | KEEP | P3 | — | Preserve; consider a dedicated fallback only if the missing configuration is an actual user-facing condition. | Do not hide accepted formats or size limits. |
| P3-029 | LEGACY/UNUSED | CLIENT | Legacy registration forms | FORM_LABEL / PLACEHOLDER | `resources/views/livewire/registration-details-form.blade.php` | 7-104 | No current route call site found; `/npwp-*` redirects to application workspace | `Masukan Data Diri` / `Masukan  nama Lengkap` / `Keperluan pwp` / `Masukan Nomor Kartu Keluarga` / `Data Kuasa` / `Masukan Akta notaris` / `Masukan SK_AHU` | Contains spelling, capitalization, and terminology problems, but source is not part of the current routed workspace. | NEEDS_CONTEXT | P3 | 1 / 2 / 2 / 1 | Do not rewrite in this audit; remove or reconcile only after proving whether a legacy route/template is still needed. | Editing dead/unknown source could create scope churn or revive an obsolete flow. |

### Strong copy to preserve in Pass 3

- `resources/views/client/applications/show.blade.php:167-174` has explicit draft/revision gates and concrete actions.
- `resources/views/client/applications/show.blade.php:195`, `234-236`, and `288-294` preserve payment/result/cancellation consequences clearly; these are not candidates for casualization.
- Business document descriptions at `resources/views/client/applications/show.blade.php:147-150` correctly distinguish KTP, Akta, SK AHU, and conditional Surat Kuasa.
- `resources/views/client/applications/create.blade.php:137` is a necessary service/legal disclosure and should remain formal.

## Pass 4 — Chat/support

### Coverage

Inspected active chat/support sources: `resources/views/chat/show.blade.php`, `resources/views/livewire/chat-thread.blade.php`, `resources/views/livewire/global-chat-notifier.blade.php`, `resources/views/livewire/admin/support-inbox.blade.php`, `resources/views/admin/support/index.blade.php`, `app/Support/ChatQuickReplyPresenter.php`, `app/Support/ChatPresentation.php`, `app/Support/ChatPresence.php`, and `app/Livewire/GlobalChatNotifier.php`. Message body text itself is user-generated and is not treated as product copy; only labels, states, canned replies, and explanatory UI are audited.

### Detailed findings

| ID | Status | Audience | Location | Copy type | File | Line | Additional source locations / usages | Current copy — VERBATIM | Issue | Decision | Priority | Naturalness / Clarity / Necessity / Consistency | Recommended direction | Risk if changed |
|---|---|---|---|---|---|---:|---|---|---|---|---|---|---|---|
| P4-001 | ACTIVE | CLIENT / ADMIN | Chat page back link | NAVIGATION | `resources/views/chat/show.blade.php` | 12, 28 | Admin and client chat shells | `Kembali ke dukungan` / `Kembali ke Pusat Bantuan` / `Kembali ke ruang pengajuan` | Destination-specific labels are clear; the client uses `Pusat Bantuan` while other UI sometimes uses `Bantuan`/`QnA`. | TERMINOLOGY_FIX | P2 | 4 / 5 / 5 / 3 | Standardize the help destination label while preserving the application-context distinction. | Do not collapse general support and application support routes. |
| P4-002 | ACTIVE | CLIENT / ADMIN | Chat context | CHAT_COPY | `resources/views/chat/show.blade.php` | 15-17, 31-33 | Admin and client headers | `BANTUAN UMUM` / `DUKUNGAN PENGAJUAN` / `Percakapan tanpa konteks pengajuan.` / `Chat pengajuan` / `Percakapan dengan Admin` / `Tanya tentang {{ $thread->application->service->name }}` | Context labels are useful. `Chat pengajuan` mixes a product-common English loanword with the otherwise Indonesian label set, while “tanpa konteks pengajuan” is precise but technical for clients. | NEEDS_CONTEXT | P2 | 3 / 4 / 4 / 4 | Validate against the approved UI term for the feature; simplify the client explanation only if the context remains explicit. | Changing support-context wording could make a general thread look application-specific. |
| P4-003 | ACTIVE | CLIENT / ADMIN | Conversation empty state | EMPTY_STATE_DESCRIPTION | `resources/views/livewire/chat-thread.blade.php` | 84-94 | Admin/general/application variants | `Belum ada percakapan` / `Mulai percakapan dengan klien jika diperlukan.` / `Percakapan ini terkait dengan pengajuan berikut.` / `Mulai percakapan jika Anda membutuhkan bantuan yang tidak terkait dengan satu pengajuan.` / `Gunakan chat ini untuk pertanyaan terkait pengajuan NPWP Anda.` | Client variants give a clear purpose. The admin application variant (“terkait dengan pengajuan berikut”) does not tell the admin what to do next and reads like a placeholder. | REWRITE | P2 | 2 / 3 / 3 / 3 | Give the admin an operational next action or context-specific explanation; keep the client general/application variants concise. | Do not invent a new admin workflow or imply a message is required. |
| P4-004 | ACTIVE | CLIENT / ADMIN | Presence label | ACCESSIBILITY_LABEL | `resources/views/livewire/chat-thread.blade.php` | 15 | Uses dynamic `ChatPresence` labels | `Status kehadiran: {{ $presenceLabel }}` | Clear accessible label; status values include a standard `Online` and Indonesian inactive phrases. | KEEP | P3 | — | Preserve; `Online` is a recognizable status term and should not be changed for stylistic reasons alone. | Presence semantics must remain accurate. |
| P4-005 | ACTIVE | CLIENT / ADMIN | Message actions | MODAL_TITLE / MODAL_BODY | `resources/views/livewire/chat-thread.blade.php` | 44-50, 73-78, 108-113 | Edit/delete menus and confirmation | `Edit pesan` / `Batal` / `Simpan` / `Tindakan untuk pesan Anda` / `Hapus pesan?` / `Pesan ini akan ditarik dari percakapan dan tidak lagi ditampilkan kepada Anda maupun penerima.` | The actions are short and the deletion consequence is explicit. | KEEP | P1 | — | Preserve; this is a good example of concise, serious operational copy. | Altering deletion language could misstate whether recipients can still see the message. |
| P4-006 | ACTIVE | ADMIN | Quick reply template | CHAT_COPY | `app/Support/ChatQuickReplyPresenter.php` | 14, 19-24 | Canned replies inserted into admin composer | `Terima kasih telah menghubungi Tim Bantu Daftarin. Mohon jelaskan kendala yang Anda alami agar kami dapat membantu.` / `Dokumen pada pengajuan Anda belum lengkap. Silakan periksa kembali dokumen yang diperlukan pada halaman pengajuan.` / `Silakan unggah ulang dokumen melalui bagian Dokumen pada halaman pengajuan agar dapat kami periksa kembali.` / `Dokumen Anda sudah kami terima dan sedang dalam proses pemeriksaan.` / `Pembayaran untuk pengajuan Anda telah diterima. Silakan pantau perkembangan selanjutnya melalui halaman pengajuan.` / `Pengajuan Anda masih dalam proses. Perkembangan terbaru dapat dipantau melalui halaman pengajuan.` / `Hasil pengajuan Anda sudah tersedia. Silakan buka halaman pengajuan untuk melihat hasil yang telah diverifikasi.` | Canned replies are functional but several repeat “Silakan”, “halaman pengajuan”, and abstract “agar/perkembangan dapat dipantau” wording. This is the highest-density AI/corporate pattern in the chat area. | SHORTEN | P1 | 3 / 4 / 3 / 4 | Rewrite as concise human admin replies with one action per template; preserve document/payment/result facts and `pengajuan` terminology. | Canned replies are sent to clients, so over-shortening could remove the next action or status consequence. |
| P4-007 | ACTIVE | ADMIN | Quick reply labels | BUTTON / FORM_LABEL | `app/Support/ChatQuickReplyPresenter.php` | 31-38 | Admin quick-reply select | `Informasi bantuan umum` / `Dokumen belum lengkap` / `Unggah ulang dokumen` / `Dokumen sedang diperiksa` / `Pembayaran diterima` / `Proses masih berlangsung` / `Hasil tersedia` | Mostly concise and useful. `Informasi bantuan umum` is slightly abstract but accurately distinguishes the template. | KEEP | P3 | — | Preserve unless template copy is redesigned with a shorter label in the same pass. | Labels must continue to map unambiguously to the canned response. |
| P4-008 | ACTIVE | CLIENT / ADMIN | Toast notification | CHAT_COPY | `app/Livewire/GlobalChatNotifier.php` | 155-166; `resources/views/livewire/global-chat-notifier.blade.php:12-18` | Client/admin toast variants | `Pesan baru dari` / `Tim Bantu Daftarin` / `Bantuan Umum` / `Anda menerima pesan baru.` / `Buka percakapan` / `Buka chat` | Toast is concise, but the action label differs by audience for the same destination (`Buka percakapan` vs `Buka chat`), and `Buka chat` conflicts with the more formal product vocabulary. | TERMINOLOGY_FIX | P2 | 3 / 5 / 4 / 3 | Use one consistent, concrete conversation action label while retaining audience-specific title/context. | Do not change thread URLs or notifier behavior. |
| P4-009 | ACTIVE | CLIENT / ADMIN | Toast preview | CHAT_COPY / PRIVACY_REVIEW | `resources/views/livewire/global-chat-notifier.blade.php` | 16-18 | Dynamic preview is generated from latest message | `{{ $toast['count'] > 1 ? $toast['count'].' pesan baru' : 'Anda menerima pesan baru.' }}` / `{{ $toast['preview'] }}` | Summary text is fine. Preview is user-generated rather than product copy and should remain governed by chat privacy/security rules, not rewritten as UI prose. | SECURITY_REVIEW | P1 | 4 / 4 / 4 / 3 | Keep the summary; audit preview exposure separately from copy rewrite and preserve the existing privacy decision. | Changing preview behavior here could alter chat notification/security scope. |
| P4-010 | ACTIVE | ADMIN | Support inbox intro | PAGE_DESCRIPTION | `resources/views/livewire/admin/support-inbox.blade.php` | 3 | Admin support list | `Percakapan klien` / `Pesan belum dibaca diprioritaskan pada daftar ini.` | “Diprioritaskan” is operationally meaningful but passive and formal; the second line can be more compact. | SHORTEN | P2 | 3 / 4 / 3 / 4 | State the ordering rule directly in a short admin sentence. | Preserve that unread messages are prioritized; do not change query ordering. |
| P4-011 | ACTIVE | ADMIN | Support context | SECTION_DESCRIPTION | `resources/views/admin/support/index.blade.php` | 6 | Admin support page header | `Kelola percakapan Bantuan Umum dan Dukungan Pengajuan.` | Direct enough for admin, but “Kelola” is a generic admin verb and the two support terms need a deliberate vocabulary distinction. | SHORTEN | P2 | 3 / 4 / 3 / 4 | Name the two inbox contexts and omit generic management framing if the page title already does the work. | Keep the distinction between general and application support. |
| P4-012 | ACTIVE | ADMIN | Archive controls | BUTTON / ACCESSIBILITY_LABEL | `resources/views/livewire/admin/support-inbox.blade.php` | 38-41 | Support list item menu | `Tindakan percakapan` / `Keluarkan dari arsip` / `Arsipkan` | Clear, concise, and action-specific. | KEEP | P3 | — | Preserve. | Archive semantics are domain behavior; copy is already adequate. |
| P4-013 | ACTIVE | ADMIN | Support empty states | EMPTY_STATE_TITLE / DESCRIPTION | `resources/views/livewire/admin/support-inbox.blade.php` | 50 | Search/unread/archive/all variants | `Percakapan tidak ditemukan` / `Tidak ada pesan belum dibaca` / `Arsip percakapan kosong` / `Belum ada percakapan` / `Coba gunakan nama klien, layanan, atau ID pengajuan lain.` / `Percakapan yang Anda arsipkan akan muncul di sini.` / `Percakapan baru akan muncul saat klien menghubungi dukungan.` | Titles are factual; descriptions give a useful next step or explain when content will appear. | KEEP | P3 | — | Preserve. | These states are more useful than decorative “nothing here” copy. |
| P4-014 | ACTIVE | CLIENT / ADMIN | Presence dynamic labels | STATUS | `app/Support/ChatPresence.php` | 128-152 | Chat header | `Online` / `Terakhir aktif beberapa menit lalu` / `Terakhir aktif hari ini` / `Terakhir aktif kemarin` / `Sedang tidak aktif` | Natural, compact, and time-sensitive. | KEEP | P3 | — | Preserve. | Do not alter time thresholds or imply a stronger presence guarantee. |

### Strong copy to preserve in Pass 4

- `resources/views/livewire/chat-thread.blade.php:108-113` clearly explains the consequence of deleting a message.
- `resources/views/livewire/admin/support-inbox.blade.php:50` provides factual empty states and useful search/filter guidance.
- `app/Support/ChatPresence.php:128-152` uses short status labels appropriate for a live chat header.

## Pass 5 — Admin operational UI

### Coverage

Inspected active admin sources: `resources/views/admin/dashboard.blade.php`, `resources/views/admin/applications/index.blade.php`, `resources/views/admin/applications/show.blade.php`, `resources/views/admin/documents/index.blade.php`, `resources/views/admin/support/index.blade.php`, `resources/views/admin/activity/index.blade.php`, `resources/views/admin/users/index.blade.php`, `resources/views/admin/users/show.blade.php`, `resources/views/components/admin/{sidebar,header,page-header,metric,status-badge}.blade.php`, all current `resources/views/livewire/admin/*.blade.php`, `app/Livewire/Admin/*`, `app/Support/AdminApplicationPresenter.php`, and `app/Support/AdminActivityPresenter.php`.

### Detailed findings

| ID | Status | Audience | Location | Copy type | File | Line | Additional source locations / usages | Current copy — VERBATIM | Issue | Decision | Priority | Naturalness / Clarity / Necessity / Consistency | Recommended direction | Risk if changed |
|---|---|---|---|---|---|---:|---|---|---|---|---|---|---|---|
| P5-001 | ACTIVE | ADMIN | Dashboard page intro | PAGE_DESCRIPTION | `resources/views/admin/dashboard.blade.php` | 8 | Admin dashboard | `Ruang kerja admin` / `Pantau pekerjaan penting dan perkembangan layanan.` | “Pantau” and “pekerjaan penting” are abstract; the page actually shows attention queues, activity, and service/application work. | SHORTEN | P1 | 2 / 4 / 3 / 4 | State the operational scope directly, without a generic monitoring slogan. | Keep the admin role and avoid promising analytics beyond the current dashboard. |
| P5-002 | ACTIVE | ADMIN | Attention panel | SECTION_TITLE | `resources/views/admin/dashboard.blade.php` | 24-25 | Dashboard attention queue | `PERLU PERHATIAN` / `Pekerjaan yang memerlukan tindakan` | Repeats the same urgency idea twice; “perlu perhatian” is a generic dashboard trope. | MERGE | P2 | 3 / 4 / 2 / 4 | Use one compact heading and let item labels carry the action context. | Do not remove the fact that these items require admin action. |
| P5-003 | ACTIVE | ADMIN | Dashboard recent activity empty state | EMPTY_STATE_DESCRIPTION | `resources/views/admin/dashboard.blade.php` | 66-67 | Recent activity card | `Belum ada aktivitas terbaru` / `Pembaruan workflow akan muncul di sini.` | “Workflow” is internal English and the sentence is vague; it does not name what updates are shown. | TERMINOLOGY_FIX | P1 | 2 / 4 / 3 / 2 | Use the established `pengajuan/proses` vocabulary or omit the description if the title is sufficient. | Keep the empty state factual and do not expose raw audit-log terminology. |
| P5-004 | ACTIVE | ADMIN | Dashboard priority queue | SECTION_TITLE / DESCRIPTION | `resources/views/admin/dashboard.blade.php` | 76-80 | Priority queue card | `ANTRIAN PRIORITAS` / `Pengajuan yang perlu dibuka berikutnya` / `Hanya pengajuan yang memerlukan tindakan admin ditampilkan di sini.` | The title “perlu dibuka berikutnya” is awkward and the description repeats the same rule in longer form. | SHORTEN | P1 | 2 / 4 / 2 / 4 | Name the queue by its operational purpose; retain the rule that only actionable pengajuan appear. | Do not alter queue priority semantics. |
| P5-005 | ACTIVE | ADMIN | Applications page header | PAGE_DESCRIPTION | `resources/views/admin/applications/index.blade.php` | 7 | Calibration example in task | `Tinjau pekerjaan yang memerlukan keputusan, pantau proses aktif, dan buka detail pengajuan secara aman.` | Three verb chain, abstract “pekerjaan”, and redundant “secara aman” make this the clearest admin AI/corporate-copy example. | SHORTEN | P1 | 1 / 4 / 2 / 4 | State the actual list purpose in one direct sentence; remove the generic safety qualifier. | Keep the scope of decisions, active process, and detail access; do not imply unauthorized access. |
| P5-006 | ACTIVE | ADMIN | Documents page header | PAGE_DESCRIPTION | `resources/views/admin/documents/index.blade.php` | 7 | Document queue page | `Setiap item merangkum dokumen aktif dalam satu pengajuan agar konteks review tetap jelas.` | “Review” is mixed language; “agar konteks … tetap jelas” is a generic rationale that describes the UI rather than the admin task. | TERMINOLOGY_FIX | P1 | 2 / 4 / 3 / 2 | Name the document-review queue and its grouped-by-pengajuan behavior directly. | Preserve that grouping is intentional for review context. |
| P5-007 | ACTIVE | ADMIN | Activity page header | PAGE_DESCRIPTION | `resources/views/admin/activity/index.blade.php` | 5 | Curated operational feed | `Pembaruan terkurasi dari pengajuan, dokumen, pembayaran, proses, dan hasil. Data audit mentah tidak ditampilkan.` | The privacy/scope boundary is important, but “terkurasi” and “data audit mentah” sound internal and formal. | SHORTEN | P2 | 3 / 5 / 5 / 3 | Keep the listed operational categories and the explicit exclusion of raw audit data, in a shorter admin sentence. | Do not imply the page is a complete audit log. |
| P5-008 | ACTIVE | ADMIN | User directory page header | PAGE_DESCRIPTION | `resources/views/admin/users/index.blade.php` | 5 | Read-only user directory | `Lihat data akun dan pengajuan milik pelanggan tanpa mengubah identitas atau akses mereka.` | Clear and appropriately explicit about read-only boundaries. | KEEP | P1 | — | Preserve. | This is authorization/role clarity, not unnecessary verbosity. |
| P5-009 | ACTIVE | ADMIN | Admin header identity | NAVIGATION / ROLE_LABEL | `resources/views/components/admin/header.blade.php` | 8-14 | Shared admin shell | `ADMINISTRASI` / `Ruang kerja admin` / `Super Admin` | `Ruang kerja admin` is fine; the all-caps kicker is visual convention. No issue except consistency with the sidebar's `Logout`. | KEEP | P3 | — | Preserve role context. | Do not obscure admin-role boundaries. |
| P5-010 | ACTIVE | ADMIN | Admin sidebar logout | BUTTON | `resources/views/components/admin/sidebar.blade.php` | 43 | Shared admin shell | `Logout` | Unnecessary English when the client shell uses `Keluar`; visible terminology inconsistency. | TERMINOLOGY_FIX | P1 | 2 / 5 / 5 / 2 | Align with the established Indonesian action vocabulary. | Keep logout route/POST behavior unchanged. |
| P5-011 | ACTIVE | ADMIN | Application queue intro | SECTION_DESCRIPTION | `resources/views/livewire/admin/application-queue.blade.php` | 3 | Reactive Pengajuan list | `Semua pengajuan` / `Urutan memprioritaskan pekerjaan operasional yang perlu ditindaklanjuti.` | Passive, corporate construction; “pekerjaan operasional” hides the actual pengajuan ordering rule. | SHORTEN | P1 | 2 / 4 / 3 / 4 | State that the list is ordered by operational priority, compactly. | Preserve priority ordering and filter semantics. |
| P5-012 | ACTIVE | ADMIN | Application search | FORM_PLACEHOLDER | `resources/views/livewire/admin/application-queue.blade.php` | 5-6 | Reactive search | `Cari pengajuan` / `Cari ID, klien, email, atau layanan` | Concrete and useful; searchable fields are explicit. | KEEP | P3 | — | Preserve. | Do not remove searchable field guidance. |
| P5-013 | ACTIVE | ADMIN | Document queue intro | SECTION_DESCRIPTION | `resources/views/livewire/admin/document-queue.blade.php` | 3 | Reactive document list | `Antrian review pengajuan` / `Tinjau dokumen dari detail pengajuan yang sudah terotorisasi.` | “Review” is mixed language, but authorized detail access is meaningful. “Sudah terotorisasi” is formal yet accurate. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Replace only the user-facing English term and retain the authorization condition. | Do not imply admins can review documents outside authorized application context. |
| P5-014 | ACTIVE | ADMIN | Document queue row helper | OPERATIONAL_COPY | `resources/views/livewire/admin/document-queue.blade.php` | 28, 37 | Queue counts/empty state | `{{ $application->revision_required_count }} dokumen perlu ditindaklanjuti` / `Dokumen aktif diringkas dalam satu pengajuan.` / `Pengajuan dengan dokumen yang relevan akan muncul di sini.` | First line is clear. “Diringkas dalam satu pengajuan” describes implementation rather than admin outcome; the empty-state copy is useful. | SHORTEN | P2 | 3 / 4 / 3 / 4 | Keep counts and “relevan” empty-state guidance; remove or clarify the implementation-focused sentence. | Preserve document grouping/count semantics. |
| P5-015 | ACTIVE | ADMIN | Activity feed intro | SECTION_DESCRIPTION | `resources/views/livewire/admin/activity-feed.blade.php` | 2 | Reactive activity list | `Pembaruan terbaru` / `Gunakan detail pengajuan untuk meninjau konteks dan tindakan yang tersedia.` | “Gunakan detail … untuk meninjau konteks” is a generic instruction and repeats the Detail link. | SHORTEN | P2 | 2 / 4 / 2 / 4 | Let the table and `Detail` action carry the instruction; retain only needed scope context. | Do not remove the detail route or action. |
| P5-016 | ACTIVE | ADMIN | Chart description | SECTION_DESCRIPTION | `resources/views/livewire/admin/activity-chart.blade.php` | 28-30 | Dashboard activity chart | `AKTIVITAS OPERASIONAL` / `Aktivitas layanan` / `Jumlah aktivitas penting pada pengajuan selama periode yang dipilih.` | Clear, specific chart description; “aktivitas penting” should be checked against the curated event definition but is not inherently slop. | NEEDS_CONTEXT | P3 | 4 / 4 / 4 / 4 | Preserve unless the analytics definition uses a more precise term than “penting”. | Do not redefine the metric in a copy pass. |
| P5-017 | ACTIVE | ADMIN | Chart empty state | EMPTY_STATE_DESCRIPTION | `resources/views/livewire/admin/activity-chart.blade.php` | 73-74 | Dashboard period filter | `Belum ada aktivitas pada periode ini` / `Aktivitas workflow akan muncul setelah terdapat proses pengajuan.` | “Workflow” is internal English and “setelah terdapat proses” is stiff. | TERMINOLOGY_FIX | P2 | 2 / 4 / 3 / 2 | Use the activity/pengajuan vocabulary already used by the chart and state when records will appear. | Preserve the empty period condition. |
| P5-018 | ACTIVE | ADMIN | Application detail summary | SECTION_DESCRIPTION | `resources/views/admin/applications/show.blade.php` | 25 | Application detail | `Ringkasan operasional` / `Konteks singkat untuk menentukan tindakan berikutnya.` | Corporate/abstract phrasing; the fields below already identify the next action. | SHORTEN | P2 | 2 / 4 / 2 / 4 | Use a compact summary label or remove the explanatory subtitle. | Do not remove the next-action field. |
| P5-019 | ACTIVE | ADMIN | Cancellation detail | SECTION_DESCRIPTION | `resources/views/admin/applications/show.blade.php` | 31 | Cancelled application detail | `Pengajuan tetap tersimpan sebagai riwayat dan tidak memiliki tindakan workflow lanjutan.` | Mixed English `workflow` and an implementation-oriented phrase. | TERMINOLOGY_FIX | P1 | 2 / 5 / 4 / 2 | Keep the final/read-only meaning while using product-facing lifecycle language. | Must preserve cancellation finality. |
| P5-020 | ACTIVE | ADMIN | Payment detail | SECTION_DESCRIPTION | `resources/views/admin/applications/show.blade.php` | 41 | Provider-authoritative payment section | `Informasi pembayaran bersifat baca-saja dan berasal dari alur provider.` | `provider` is operationally understandable to admins, but the sentence is stiff and mixes English. | NEEDS_CONTEXT | P2 | 3 / 5 / 5 / 3 | Confirm the internal admin vocabulary; if a user-facing Indonesian term exists, use it without weakening provider-source truth. | Do not imply admin can edit provider payment state. |
| P5-021 | ACTIVE | ADMIN | Data privacy summary | SECURITY_NOTICE | `resources/views/admin/applications/show.blade.php` | 52 | Personal/business application data | `Informasi pemohon yang digunakan dalam pengajuan. Nomor identitas ditampilkan dalam bentuk tersamarkan.` | Clear, precise, and appropriate for sensitive data. | KEEP | P1 | — | Preserve. | Do not remove masking explanation. |
| P5-022 | ACTIVE | ADMIN | Document review detail | SECTION_DESCRIPTION / BUTTON | `resources/views/admin/applications/show.blade.php` | 70, 90 | Application detail document section | `Dokumen aktif yang dikirim pengguna. Keputusan tetap berlaku pada versi aktif dan lifecycle yang tersedia.` / `Catat keputusan review` / `Simpan keputusan` | `review` and `lifecycle` leak internal English; the version-active rule is important. | TERMINOLOGY_FIX | P1 | 2 / 5 / 5 / 2 | Standardize the review/action terms while preserving the version and lifecycle semantics. | Incorrect simplification could make an old document version appear actionable. |
| P5-023 | ACTIVE | ADMIN | Result detail | SECURITY_NOTICE | `resources/views/admin/applications/show.blade.php` | 102-103 | Result verification | `Hanya hasil terverifikasi yang dapat tersedia untuk klien.` | Precise result authorization boundary. | KEEP | P1 | — | Preserve. | Do not weaken VERIFIED gating. |
| P5-024 | ACTIVE | ADMIN | History detail | SECTION_DESCRIPTION | `resources/views/admin/applications/show.blade.php` | 106 | Status/estimate history | `Riwayat status dan estimasi yang relevan untuk operasional.` | “Relevan untuk operasional” is abstract but the section itself is operational. | SHORTEN | P2 | 3 / 4 / 3 / 4 | State what records are shown, or omit the subtitle. | Preserve status/estimate history scope. |
| P5-025 | ACTIVE | ADMIN | Admin action presenter | BUTTON / OPERATIONAL_COPY | `app/Support/AdminApplicationPresenter.php` | 63-74 | Drives detail header/action card | `Mulai pemeriksaan` / `Tinjau dokumen` / `Tetapkan estimasi` / `Perbarui proses` / `Unggah hasil` / `Mulai review hasil` / `Verifikasi hasil` / `Arsipkan bila perlu` / `Riwayat baca-saja` / `Tidak ada tindakan` / `Pantau pengajuan` | Most actions are short and concrete. `Mulai review hasil` mixes English; `Pantau pengajuan` is vague when no action is available. | TERMINOLOGY_FIX | P1 | 3 / 5 / 4 / 3 | Replace only the mixed term and reconsider the no-action label in context; preserve state-gated action availability. | Action labels are coupled to the state machine; do not imply an available transition where none exists. |
| P5-026 | ACTIVE | ADMIN | Activity presenter | OPERATIONAL_COPY | `app/Support/AdminActivityPresenter.php` | 207-220, 239 | Dashboard/activity feed event descriptions | `Estimasi penyelesaian pengajuan diperbarui.` / `Dokumen aktif telah diterima saat pemeriksaan.` / `Dokumen aktif ditolak saat pemeriksaan.` / `Dokumen aktif memerlukan perbaikan dari klien.` / `Hasil pengajuan diunggah untuk pemeriksaan.` / `Hasil telah diverifikasi oleh admin.` / `Terdapat pembaruan pada workflow pengajuan.` | Most event descriptions are concise and factual. The fallback `workflow` sentence is vague/internal; `review` in labels at line 280 is mixed language. | TERMINOLOGY_FIX | P2 | 3 / 4 / 4 / 3 | Preserve concrete event descriptions; replace internal/mixed fallback vocabulary with established terms. | Do not collapse distinct document/result/payment events. |
| P5-027 | ACTIVE | ADMIN | Status/filter labels | STATUS | `app/Support/AdminApplicationPresenter.php` | 12-20 | Application filter tabs | `Semua` / `Perlu ditinjau` / `Revisi masuk` / `Dokumen diterima` / `Sedang diproses` / `Hasil perlu review` / `Selesai` / `Dibatalkan` | Mostly concise; `Hasil perlu review` is mixed English and inconsistent with Indonesian action labels. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Use a stable Indonesian review term while keeping the filter meaning unchanged. | Changing filter labels must not alter query keys or bookmarked URLs. |
| P5-028 | ACTIVE | ADMIN | Admin user detail | PAGE_DESCRIPTION / SECURITY_NOTICE | `resources/views/admin/users/show.blade.php` | 6, 9 | Read-only user detail | `Detail pelanggan bersifat baca-saja. Data pengajuan dapat dibuka melalui konteks masing-masing.` / `Daftar ini tidak menampilkan dokumen atau identitas sensitif.` | Precise read-only/privacy boundaries; “melalui konteks masing-masing” is slightly abstract but functional. | KEEP | P1 | — | Preserve; only revisit the second sentence if user testing finds the navigation unclear. | Do not weaken sensitive-data omission language. |
| P5-029 | ACTIVE | ADMIN | Operational terminology | OPERATIONAL_COPY | `resources/views/admin/applications/show.blade.php` | 90, 103, 114-119 | Review/result/action forms | `Keputusan` / `Terima dokumen` / `Minta perbaikan` / `Tolak dokumen` / `Verifikasi hasil` / `Tolak hasil` / `Selesaikan review` / `Tandai menunggu proses instansi` | Action labels are generally good; `Selesaikan review` is the remaining mixed-language operational label. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 3 | Align the one mixed label with the surrounding Indonesian actions; preserve decision meanings. | Do not alter form action values or review semantics. |

### Strong copy to preserve in Pass 5

- `resources/views/admin/users/index.blade.php:5` explicitly communicates the read-only directory boundary.
- `resources/views/admin/applications/show.blade.php:52`, `102-103`, and `resources/views/admin/users/show.blade.php:9` explain privacy/result authorization without marketing language.
- Admin filter labels in `app/Support/AdminApplicationPresenter.php:12-20`, except the single mixed-language review label, are concise operational labels.
- `resources/views/livewire/admin/application-queue.blade.php:5-6` and `document-queue.blade.php:5-6` use concrete search-field placeholders.

## Pass 6 — Shared/system copy

Pass 6 inspects the shared shell, flash/error messages, active validation copy,
loading text, accessibility names/alt text, and user-visible date/currency
formatting. Identical shared loading text is recorded once with all source
locations rather than once per rendered page.

| ID | Status | Audience | Location | Copy type | File | Line | Additional source locations / usages | Current copy — VERBATIM | Issue | Decision | Priority | Naturalness / Clarity / Necessity / Consistency | Recommended direction | Risk if changed |
|---|---|---|---|---|---|---:|---|---|---|---|---|---|---|---|
| P6-001 | ACTIVE | SHARED | Client feedback summary | ERROR | `resources/views/layouts/client.blade.php` | 22 | Session validation feedback | `Periksa kembali informasi berikut:` | Clear, calm, and useful as a heading for field-level errors. | KEEP | P1 | — | Preserve. | Do not remove the relation between the heading and the listed field errors. |
| P6-002 | ACTIVE | SHARED | Accessibility skip link | ACCESSIBILITY_LABEL | `resources/views/layouts/client.blade.php` | 12 | Client authenticated shell | `Lewati ke konten utama` | Concrete skip-navigation wording; not decorative filler. | KEEP | P1 | — | Preserve. | Changing it to a less specific label could reduce keyboard-navigation clarity. |
| P6-003 | ACTIVE | SHARED | Admin authentication error | ERROR | `app/Http/Controllers/Auth/AuthController.php` | 60 | Admin login only | `Akun admin tidak aktif.` | Short and operationally precise. | KEEP | P1 | — | Preserve. | Do not disclose more account state than this existing authorization-safe message. |
| P6-004 | ACTIVE | SHARED | OTP resend feedback | SUCCESS | `app/Http/Controllers/Auth/AuthController.php` | 122 | Login OTP resend | `Kode OTP baru telah dikirim.` | Direct confirmation with no unnecessary promise. | KEEP | P1 | — | Preserve. | Keep security wording separate from the OTP value itself. |
| P6-005 | ACTIVE | SHARED | Verification fallback feedback | SUCCESS | `app/Http/Controllers/Auth/EmailVerificationController.php` | 35 | Verification resend request | `Jika email terdaftar dan belum diverifikasi, tautan verifikasi akan dikirim.` | Privacy-preserving conditional language; slightly formal but purposeful. | KEEP | P1 | — | Preserve unless product deliberately changes anti-enumeration policy. | Do not replace with a message that confirms whether an email exists. |
| P6-006 | ACTIVE | ADMIN | Document-review status feedback | SUCCESS | `app/Http/Controllers/Admin/ApplicationController.php` | 56 | Admin document decision | `Review dokumen tersimpan.` | English `Review` conflicts with the established Indonesian operational vocabulary; otherwise concise. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Use the existing Indonesian term for the document decision while keeping the saved-state meaning. | Do not change the underlying review action or audit event. |
| P6-007 | ACTIVE | ADMIN | Completed/archive status feedback | SUCCESS | `app/Http/Controllers/Admin/ApplicationController.php` | 119, 128 | Admin lifecycle actions | `Aplikasi ditandai selesai.` / `Aplikasi diarsipkan.` | User-facing `Aplikasi` conflicts with the established product term `pengajuan`. The sentences themselves are compact. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Align the noun with `pengajuan`; preserve the final-state and archive meanings. | A careless change must not alter state-machine terminology or route behavior. |
| P6-008 | ACTIVE | CLIENT | Application data status feedback | SUCCESS | `app/Http/Controllers/Client/ApplicationController.php` | 132, 141 | Client save and document-stage transitions | `Data aplikasi tersimpan.` / `Aplikasi masuk ke tahap pengumpulan dokumen.` | Both use `aplikasi` for the service request and therefore conflict with `pengajuan`. | TERMINOLOGY_FIX | P1 | 3 / 5 / 5 / 2 | Replace only the user-facing noun with the established product term. | Keep the distinction between saving data and entering document collection. |
| P6-009 | ACTIVE | CLIENT | Document upload feedback | SUCCESS | `app/Http/Controllers/Client/DocumentController.php` | 29-33 | Upload result branches | `Dokumen lengkap. Silakan lanjut ke pembayaran.` / `Dokumen berhasil diunggah dan menunggu pemeriksaan.` | Clear branch-specific next actions; the second message accurately communicates the review wait. | KEEP | P1 | — | Preserve. | Do not merge the branches; one is payment-ready and the other is review-pending. |
| P6-010 | ACTIVE | CLIENT | Payment instruction feedback | SUCCESS | `app/Http/Controllers/Client/PaymentController.php` | 59-61 | Checkout URL vs on-page payment instructions | `Instruksi pembayaran dibuat. Lanjutkan melalui halaman pembayaran yang tersedia.` / `Instruksi pembayaran sudah tersedia di halaman ini.` | Specific and non-promotional; wording correctly differs by provider flow. | KEEP | P1 | — | Preserve unless payment UX provides a more concrete destination label. | Must not imply a payment has succeeded before provider confirmation. |
| P6-011 | ACTIVE | CLIENT | Cancellation validation | ERROR / FORM_HELPER | `app/Http/Requests/Client/CancelApplicationRequest.php` | 32-34 | Cancellation modal/form | `Pilih alasan pembatalan.` / `Jelaskan alasan pembatalan lainnya.` / `Penjelasan alasan maksimal 500 karakter.` | Concise, actionable, and appropriately formal for an irreversible action. | KEEP | P1 | — | Preserve. | Do not remove the character limit or the required-reason distinction. |
| P6-012 | ACTIVE | SHARED | Chat presence wording | STATUS / ACCESSIBILITY_LABEL | `app/Livewire/ChatThread.php` | 42 | Rendered in `resources/views/livewire/chat-thread.blade.php:15` | `Sedang tidak aktif` | Factual presence state; no artificial friendliness. | KEEP | P2 | — | Preserve. | Presence must continue to reflect the actual heartbeat state. |
| P6-013 | ACTIVE | SHARED | Chat message read icons | ACCESSIBILITY_LABEL | `resources/views/livewire/chat-thread.blade.php` | 64, 66 | Image icons have `alt=""` and `aria-label="Dibaca"` / `aria-label="Terkirim"` | `Dibaca` / `Terkirim` | The status terms are concise, but an empty-alt image with an ARIA label may be inconsistently exposed by assistive technology. | ACCESSIBILITY_GAP | P2 | 4 / 3 / 5 / 4 | Verify the semantic pattern with an actual screen reader; expose the status through a reliably named element without duplicating visible content. | Do not remove read receipts or expose message content. |
| P6-014 | ACTIVE | SHARED | Admin reactive result loading | LOADING | `resources/views/livewire/admin/application-queue.blade.php:18`, `document-queue.blade.php:18`, `support-inbox.blade.php:19`, `activity-feed.blade.php:11`, `user-directory.blade.php:11`, `activity-chart.blade.php:45` | 11, 18, 19, 45 | All admin reactive list/chart regions | `Memuat...` | Consistent short loading text for equivalent reactive states. The ASCII ellipsis is a minor format choice, not AI-like copy. | KEEP | P3 | — | Preserve one short pattern; if typography is standardized later, change punctuation consistently rather than per page. | Loading text must remain scoped to the updating region and not imply a page-wide operation. |
| P6-015 | ACTIVE | SHARED | Operation-specific loading | LOADING | `resources/views/livewire/application-details-form.blade.php` | 19 | Chat send state at `resources/views/livewire/chat-thread.blade.php:146` | `Menyimpan...` / `Mengirim` | Both are short and correctly name different operations; no need to force one generic phrase. | KEEP | P3 | — | Preserve operation specificity. | A blanket replacement could make save/send states ambiguous. |
| P6-016 | ACTIVE / FRAMEWORK DEFAULT | CLIENT | Password-reset status translation | SUCCESS / ERROR | `app/Http/Controllers/Auth/PasswordResetController.php` | 26, 42 | `__($status)` resolves Laravel password-broker keys; no project `lang/id` directory was found during this audit | `__($status)` | Runtime copy is delegated to framework translation keys, so Indonesian output and terminology cannot be confirmed from this source alone. | NEEDS_CONTEXT | P1 | 2 / 4 / 5 / 2 | Confirm the active locale translation source and capture the rendered Indonesian strings before any rewrite. If English defaults leak, treat that as a runtime terminology fix. | Do not change password broker/token behavior while correcting wording. |
| P6-017 | ACTIVE | SHARED | Brand alternative text | ACCESSIBILITY_ALT | `resources/views/components/site-header.blade.php` | 8-9 | Reused by client/admin/auth shells; logo alt is also present in `layouts/guest.blade.php`, `layouts/admin-auth.blade.php`, and `components/client-header.blade.php` | `Bantu Daftarin` | Appropriate brand identification for a logo; decorative icons nearby correctly use empty alt/`aria-hidden`. | KEEP | P1 | — | Preserve the brand name and decorative-image distinction. | Removing the alt would make the brand unclear when images are blocked. |
| P6-018 | ACTIVE | SHARED | Date/time display formats | FORMAT_LABEL | `resources/views/admin/dashboard.blade.php` | 59, 96 | Dominant forms appear in `resources/views/livewire/admin/*: d M Y, H:i`; client workspace uses `d M Y`, `d M Y, H:i`, and cancellation uses `d F Y, H:i` | `translatedFormat('d M, H:i')` | Admin dashboard omits the year while adjacent admin tables/details include it. Client surfaces also mix abbreviated month (`M`) and full month (`F`) depending on context. | FORMAT_CONSISTENCY | P2 | 3 / 4 / 4 / 2 | Establish a documented date/time convention by context (recent activity vs full history) and apply it consistently; do not change timestamps in this audit. | Formatting changes can affect operational interpretation and locale readability. |
| P6-019 | ACTIVE | SHARED | Currency/number formatting | FORMAT_LABEL | `resources/views/client/services/index.blade.php` | 36 | Same `currency . ' ' . number_format(..., 0, ',', '.')` pattern in payment/application/activity/admin views; `resources/views/client/activity/show.blade.php:36` uses a `Rp` icon | `{{ $service->currency }} {{ number_format((float) $service->price_amount, 0, ',', '.') }}` | Monetary values consistently use a currency code plus Indonesian thousands separators; the separate `Rp` icon is a visual category marker, not an amount format. No confirmed active currency inconsistency found. | KEEP | P2 | — | Preserve the backend currency and Indonesian number formatting; document the visual `Rp` icon as non-value text. | Do not replace provider/backend currency codes or alter amounts. |

### Shared/system checks — strong copy and out-of-scope sources

- `resources/views/layouts/client.blade.php:12` (`Lewati ke konten utama`), the shared brand alt text, and the informative image alts in `resources/views/client/payment/show.blade.php:65`, `resources/views/client/applications/show.blade.php:79`, and `resources/views/components/personal-face-document.blade.php:26` are useful and should not be made more decorative or verbose.
- `app/Http/Controllers/Webhooks/XenditWebhookController.php` JSON messages and `resources/views/testing/fake-payment.blade.php` are API/test surfaces, not Indonesian production UI copy; they were inspected for scope but excluded from active UI counts.
- `resources/views/welcome.blade.php` is a legacy Laravel welcome template not used by the application home route; its generic Laravel copy is classified `LEGACY/UNUSED`, not an active rewrite target.

## Global findings

### Coverage and counting

The detailed tables contain **133 root audit items**: 25 in Pass 1, 17 in
Pass 2, 29 in Pass 3, 14 in Pass 4, 29 in Pass 5, and 19 in Pass 6. These are
source roots, not rendered occurrences. Shared components and duplicated
messages are deduplicated; their additional usages are listed in the row.

The detailed rows represent **60 distinct primary/usage source paths** across
public/authenticated client views, application workspace views, chat/support,
admin views and Livewire components, controllers, presenters, enums, and shared
components. Framework-rendered validation messages and runtime translation keys
do not have a project-owned source file to count; their uncertainty is called
out in P6-016. Production source files modified by this audit: **NO**.

### Decision totals (root findings)

| Decision | Count |
|---|---:|
| KEEP | 52 |
| REWRITE | 2 |
| SHORTEN | 34 |
| REMOVE | 1 |
| MERGE | 3 |
| TERMINOLOGY_FIX | 25 |
| DUPLICATE_COPY | 1 |
| FORMAT_CONSISTENCY | 3 |
| DOMAIN_REVIEW | 3 |
| SECURITY_REVIEW | 1 |
| ACCESSIBILITY_GAP | 1 |
| NEEDS_CONTEXT | 7 |
| **Total** | **133** |

Counts represent root findings. A row can mention several copies or usage
locations, but it is counted once. `KEEP` includes high-value security,
privacy, payment, status, and accessibility copy that should not be changed
for cosmetic reasons.

### Priority totals

| Priority | Count | Interpretation |
|---|---:|---|
| P0 | 0 | No active UI copy item was found that is itself a confirmed security/privacy breach. |
| P1 | 48 | Highly visible, clearly awkward, terminology-conflicting, or operationally sensitive. |
| P2 | 47 | Noticeable improvement or context/format review. |
| P3 | 38 | Minor polish or already-strong copy recorded for calibration. |

### Repeated anti-slop patterns

The audit found these recurring source-root patterns:

1. **Abstract three-part descriptions** — especially `Pantau ...`, `Tinjau ...`,
   and `Kelola ...` in landing/admin headers. They often put three actions in
   one sentence rather than naming the screen's one primary job.
2. **Rationale filler** — `agar ... tetap jelas`, `sesuai kebutuhan`, and
   `secara aman` appear where the surrounding UI already communicates the
   constraint or where a concrete action would be clearer. The phrase is
   legitimate only when it communicates a real privacy/security boundary.
3. **Internal English leakage** — `review`, `workflow`, `lifecycle`, `provider`,
   `support`, `password`, `reset`, and `private` remain in otherwise Indonesian
   UI. Technical/provider names are not automatically wrong; the flagged rows
   are user-facing contexts where an established Indonesian term exists.
4. **Repeated generic subtitles** — several cards use a title plus a second
   sentence that restates the title or describes the implementation (`konteks`,
   `operasional`, `dalam satu tempat`) without adding a next action.
5. **Product noun drift** — active UI still uses `aplikasi` in several client
   and admin status messages even though `pengajuan` is the established term.
6. **Mixed help vocabulary** — `FAQ`, `QnA`, `Bantuan`, and `Dukungan` are all
   active. Some differences are intentional by audience/context, but the
   public/client boundary needs one explicit decision.
7. **Long canned chat replies** — quick replies repeatedly use `Silakan`,
   `halaman pengajuan`, and `agar ...` in scripts that sound more like a
   template than a human first response.

### Areas with the strongest AI/corporate tendency

| Area | Tendency | Why |
|---|---|---|
| Admin Pengajuan | HIGH | Header and queue copy compress several abstract actions, add redundant safety/rationale language, and mix `review` with Indonesian workflow terms. |
| Admin Dashboard | HIGH | `Pantau`/`perkembangan layanan`, priority-queue subtitles, and workflow-empty-state wording are broad and repetitive. |
| Admin Dokumen | HIGH | Queue descriptions explain the UI's grouping rather than the admin decision and use `review`/`otorisasi` heavily. |
| Chat quick replies | HIGH | Canned responses contain repeated polite scaffolding and long multi-clause instructions. |
| Client landing/services/QnA | MEDIUM | Visual hierarchy is clear, but several marketing-like descriptions use abstract benefits and three-part promises. |
| Application workspace | MEDIUM | Security/payment/domain copy is generally strong; helper text and business/personal form subtitles sometimes repeat obvious structure. |
| Auth/security | LOW–MEDIUM | Security copy is mostly calm and precise; terminology such as `reset`/`password` and framework-default status text needs confirmation. |
| Client/Admin chat shells | LOW–MEDIUM | Empty states and labels are mostly direct; toast vocabulary and quick replies need alignment. |

### Strong copy that should not be touched casually

- `Buka pengajuan`, `Tinjau`, `Simpan`, `Unggah`, `Lanjutkan`, `Batalkan`,
  `Kirim`, `Arsipkan`, `Pulihkan`, and similar concrete action labels.
- Privacy/security boundaries such as `Nomor identitas ditampilkan dalam bentuk
  tersamarkan.`, `Hanya hasil terverifikasi yang dapat tersedia untuk klien.`,
  private-file notes, payment-gating notices, and cancellation finality.
- Empty-state titles that state a real absence (`Belum ada pengajuan`, `Belum
  ada hasil`, `Belum ada aktivitas pada periode ini`) when the surrounding
  description is not duplicative.
- Search placeholders that name the actual searchable fields.
- OTP, verification, and payment instructions that communicate expiry,
  security, or next action without hype.

### Explicit audit questions answered

- **Most AI/corporate:** Admin Pengajuan, Admin Dashboard, and Admin Dokumen;
  chat quick replies are the most mechanically scripted client-support copy.
- **Already strong:** concrete buttons, privacy/result gates, payment truth,
  read-only boundaries, document requirements, and concise empty-state titles.
- **Overused:** `Pantau`, `Tinjau`, `Kelola`, `workflow`, `review`, `agar ...`,
  `sesuai kebutuhan`, `secara aman`, and generic operational subtitles.
- **Unnecessarily long:** admin page descriptions, dashboard priority queue
  copy, document queue intro, some application form helpers, and quick replies.
- **Remove candidates:** descriptions that only repeat a title or visible table
  action, especially in admin activity/detail cards; preserve any sentence that
  explains privacy, payment gating, a condition, or irreversible consequence.
- **Terminology conflicts:** `aplikasi` for service requests, `review`,
  `workflow`, `lifecycle`, `private`, `reset/password`, `FAQ/QnA`, and
  `Buka chat` vs `Buka percakapan`.
- **Admin descriptions too corporate:** applications header, queue ordering,
  document grouping, activity feed, and priority queue.
- **Client descriptions too stiff:** public service/FAQ descriptions, some
  form helper text, and the longer document-revision instruction.
- **Generic empty states:** admin workflow/chart empty states and a few chat
  inbox variants where a next action is not named. Most absence titles are
  already clear.
- **Helpers that earn their space:** privacy/file-storage notes, accepted
  document/format/size rules, conditional Surat Kuasa guidance, autosave
  behavior, payment expiry, cancellation consequences, and revision reasons.
- **Buttons already good:** concrete one-action labels listed above; do not
  vary them merely to sound more conversational.
- **Accessibility:** one confirmed review item is the chat read-icon naming
  pattern (P6-013). Brand alts, decorative empty alts, skip links, menu labels,
  progress labels, QR alt, and document-preview alt are strong.
- **Date/time:** not fully consistent; admin recent-activity formatting omits
  the year while adjacent surfaces include it, and full vs abbreviated month
  varies by context. This needs a product convention, not a blind replacement.
- **Currency/numbers:** active amount rendering consistently uses the backend
  currency plus Indonesian thousands separators; no confirmed amount-format
  defect was found. The `Rp` activity icon is a category marker, not a second
  amount format.
- **Must remain formal:** OTP/security, private-document and result
  authorization, payment status/gating, cancellation, validation limits, and
  state-machine transitions.

## Page-level summary

Counts below are root findings from the detailed tables, not rendered usage
counts. `Other` includes `TERMINOLOGY_FIX`, `DUPLICATE_COPY`,
`FORMAT_CONSISTENCY`, `DOMAIN_REVIEW`, `SECURITY_REVIEW`,
`ACCESSIBILITY_GAP`, and `NEEDS_CONTEXT`.

### Client

| Page group | Overall quality | KEEP | REWRITE | SHORTEN | REMOVE | Other | Highest-priority recommendation |
|---|---|---:|---:|---:|---:|---:|---|
| Landing | MEDIUM | 3 | 0 | 5 | 1 | 4 | Shorten benefit/hero descriptions and remove abstract reassurance. |
| Auth/security | LOW–MEDIUM | 5 | 1 | 1 | 0 | 4 | Keep security precision; align reset/password terminology and verify framework status translations. |
| Dashboard | MEDIUM | 3 | 0 | 2 | 0 | 2 | Reduce duplicate descriptions and keep activity language concrete. |
| Services | MEDIUM | 2 | 0 | 2 | 0 | 3 | Make the primary next action singular and align `FAQ`/`Bantuan` vocabulary. |
| Pengajuan list | MEDIUM | 2 | 0 | 1 | 0 | 2 | Keep filters/status labels; remove repeated list-intro rationale. |
| Personal workspace | HIGH for domain clarity / MEDIUM for density | 9 | 0 | 3 | 1 | 5 | Preserve privacy/payment/document helpers; shorten repeated form subtitles. |
| Business workspace | HIGH for domain clarity / MEDIUM for density | 6 | 0 | 2 | 0 | 4 | Keep business-specific document semantics; do not copy personal wording literally. |
| Help/QnA | MEDIUM | 4 | 0 | 2 | 1 | 4 | Decide public `FAQ/QnA/Bantuan` vocabulary and keep no-result actions useful. |
| Chat | MEDIUM | 5 | 0 | 2 | 0 | 4 | Rewrite only long canned replies and align conversation CTA terminology. |

### Admin

| Page group | Overall quality | KEEP | REWRITE | SHORTEN | REMOVE | Other | Highest-priority recommendation |
|---|---|---:|---:|---:|---:|---:|---|
| Dashboard | MEDIUM–LOW | 3 | 0 | 5 | 1 | 4 | Replace broad `Pantau`/workflow wording with the actual operational task. |
| Pengajuan | LOW | 3 | 1 | 4 | 0 | 5 | Rewrite the header and queue intro first; preserve filter keys and action semantics. |
| Dokumen | LOW–MEDIUM | 2 | 0 | 3 | 0 | 4 | Remove implementation-focused explanations and standardize review vocabulary. |
| Dukungan | MEDIUM | 4 | 0 | 2 | 0 | 3 | Keep unread/archive semantics; shorten inbox instruction and support labels. |
| Aktivitas | MEDIUM–LOW | 3 | 0 | 3 | 0 | 4 | Keep the raw-audit exclusion, but make the surrounding copy direct and less internal. |
| Pengguna | HIGH | 5 | 0 | 0 | 0 | 1 | Preserve read-only/privacy copy; only align shared logout terminology. |
| Chat | MEDIUM | 4 | 0 | 2 | 0 | 3 | Keep conversation safety/presence language; use human, concise quick replies. |

These page summaries are directional and should not be read as permission to
rewrite every item. The source-level decision and risk in each row remain the
authority.

## Accessibility findings

1. **P6-013 — ACCESSIBILITY_GAP, P2:** `alt=""` plus `aria-label="Dibaca"` /
   `aria-label="Terkirim"` on chat status images needs screen-reader
   verification. The terms themselves are good; the semantic exposure pattern
   may be unreliable.
2. Brand/logo alts use the product name, decorative icons use empty alt and/or
   `aria-hidden`, and icon-only controls have names such as `Buka menu`, `Tutup
   menu administrasi`, `Tutup notifikasi pesan baru`, and `Tindakan untuk pesan
   Anda`. These are KEEP findings.
3. Progress, chart, QR, document-preview, skip-link, filter-group, and chat
   labels are present and generally describe the relevant control or region.
4. No source-grounded `ACCESSIBILITY_GAP` was added for decorative images; adding
   prose to decorative icons would make the experience noisier.

## Format consistency findings

- **Date/time:** pick a documented convention for compact recent-activity
  timestamps versus full history timestamps. Current active formats include
  `d M, H:i`, `d M Y`, `d M Y, H:i`, and `d F Y, H:i`.
- **Currency/numbers:** amounts use the backend currency code and
  `number_format(..., 0, ',', '.')`, which is internally consistent. No raw
  provider amount or US-style decimal was found in active UI copy.
- **IDs:** public IDs are intentionally shortened in list contexts and shown
  in full only where the user explicitly copies an application ID. This is a
  product/security convention, not a copy defect.

## Terminology findings

### Established terms to protect

`pengajuan`, `NPWP Perseorangan`, `NPWP Badan Usaha`, `Dokumen`, `Pembayaran`,
`Hasil`, and concrete Indonesian actions (`Buka`, `Tinjau`, `Simpan`, `Unggah`,
`Kirim`, `Batalkan`, `Arsipkan`).

### Terms requiring scoped alignment

- `aplikasi` → `pengajuan` when referring to the client's service request;
  do not change technical identifiers or unrelated software concepts.
- `review` → the established Indonesian operational term in user-facing copy;
  preserve backend action names.
- `workflow` / `lifecycle` → product-facing lifecycle/process wording where
  the user needs to understand state; do not expose raw enum names.
- `FAQ`, `QnA`, `Bantuan`, `Dukungan` → define public-help versus contextual
  support usage before batch rewriting.
- `Buka chat` / `Buka percakapan` → choose one CTA by audience/context and keep
  the exact thread destination unchanged.
- `private`, `password`, `reset`, `support`, `provider` → review only in
  user-facing contexts; do not translate proper names or operational provider
  identifiers automatically.

## Draft BantuDaftarin UI voice guide

This guide aligns the UI with the already-established transactional-email voice:

- **Ramah, bukan cerewet.** Use a warm sentence only when it helps the user
  understand a state or next action.
- **Profesional, bukan birokratis.** Prefer a concrete subject and verb over
  abstract nouns such as `solusi`, `pengalaman`, `konteks`, or `operasional`.
- **Jelas, bukan terlalu menjelaskan.** One UI copy element should have one
  primary job: name the state, explain the requirement, or give the next action.
- **Tenang, bukan dingin.** Avoid urgency, fear, hype, and unsupported promises;
  keep security/payment/cancellation copy precise.
- **Aktif, bukan marketing.** Use `Buka`, `Simpan`, `Unggah`, `Lanjutkan`, and
  `Tinjau` when the user can actually perform that action.
- **Natural Indonesian.** Use `pengajuan` for the product request and avoid
  mechanically retained SaaS English where Indonesian is already established.
- **Client voice:** slightly warmer, with concrete next steps and useful
  context; never patronizing or overly casual.
- **Admin voice:** compact, factual, operational; do not add customer-service
  framing to every table or filter.
- **Accessibility voice:** name the function, not the visual shape; keep
  decorative assets silent and informative assets concise.
- **Format voice:** choose one date/currency convention per context and keep it
  stable across related screens.

## Rewrite plan

No production copy is rewritten in this audit. Recommended implementation
passes, in order:

### Phase A — High-visibility client descriptions

Shorten landing hero/service/QnA descriptions, remove benefit filler, and align
public help terminology. Preserve the existing service scope and `COMING_SOON`
meaning.

### Phase B — Application workspace/forms

Apply the `pengajuan` terminology fix to active client/admin messages, shorten
repeated form/document helpers, and keep every privacy, conditional-document,
payment, cancellation, and result-gating explanation.

### Phase C — Admin operational copy

Rewrite the Admin Pengajuan/Dokumen/Dashboard headers and queue subtitles first.
Replace mixed English and implementation-language while preserving filter keys,
state-machine actions, and authorization boundaries.

### Phase D — Chat/support polish

Shorten canned quick replies, align `Buka percakapan`/support vocabulary, and
resolve the chat read-icon accessibility semantics through screen-reader QA.

### Phase E — Terminology, accessibility, and format consistency

Confirm framework password-status translations, establish date/time conventions,
align remaining shared terms, and run keyboard/screen-reader/image-blocked
acceptance checks. This phase should not change domain or security behavior.

## Audit completion checklist

- [x] Pass 1–6 recorded incrementally in this artifact.
- [x] Every flagged root item has a source file, line, verbatim copy, decision,
  priority, direction, and risk.
- [x] Shared copy is deduplicated and additional usages are named.
- [x] Indonesian active UI was prioritized; email body redesign was excluded.
- [x] Accessibility text and format consistency were audited.
- [x] No production source file was modified by this audit.
- [ ] Browser screen-reader and locale-runtime verification for P6-013/P6-016
  remains a manual acceptance item.

## Classification

**BANTUDAFTARIN UI COPY AUDIT COMPLETE WITH DOMAIN/CONTEXT UNCERTAINTIES**

The source-grounded inventory is complete for the inspected active surfaces.
The remaining uncertainty is deliberately isolated to context-dependent
vocabulary (`FAQ`/`QnA`/`Bantuan`/`Dukungan`, `provider`), framework-resolved
password statuses, and screen-reader behavior for chat read icons. No copy
rewrite has been applied.
