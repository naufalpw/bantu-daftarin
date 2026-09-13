# COMPLETE MOBILE UI REDESIGN — CLIENT + ADMIN IMPLEMENTED

Tanggal: 12 September 2026
Lingkup: mobile Client + Admin, target 320/360/375/390/412/430px. Desktop dan identitas produk bukan target redesign.

## A. SKILLS USED

Dibaca dan diterapkan selama keputusan, implementasi, dan review:

- .codex/skills/antislop/ANTISLOP.md
- .codex/skills/antislop/SKILL.md
- .codex/skills/antislop-ui/SKILL.md
- .codex/skills/antislop-layoutmobile/SKILL.md

AGENTS.md, DESIGN.md, COMPLETE-MOBILE-UI-UX-AUDIT.md, serta dokumentasi lifecycle/security/UX yang relevan dibaca sebelum perubahan source. Antislop-copywriting tidak dimuat: tidak ada penulisan ulang copy material; tambahan hanya label fungsi/feedback singkat. Antislop-human tidak dimuat: tidak ada perubahan representasi manusia, aset foto, atau pemrosesan wajah.

Antislop memengaruhi hasil secara konkret: panel list berlapis dikurangi, status pembayaran berulang ditenangkan, dokumen yang sudah diunggah tidak diperlakukan sebagai dropzone kosong besar, dan daftar operasional tidak diberi dekorasi dashboard baru. Logo, navy/blue, Inter, ikon, radius/border family, serta terminology tetap.

## B. BASELINE

- CP0: git status --short kosong; tidak ada pre-existing working-tree changes pada awal implementasi ini. Catatan dirty tree dalam audit adalah catatan historis, bukan keadaan checkout saat CP0.
- Artefak authoritative: COMPLETE-MOBILE-UI-UX-AUDIT.md. Baseline 455 hash source/referensi cocok dengan snapshot audit; tidak ditemukan divergence material.
- RENDERED BASELINE NOT AVAILABLE. Inventory browser kosong dan permintaan browser lokal mengembalikan “No browser is available”. Tidak ada screenshot, pengukuran computed style, atau sesi browser Client/Admin yang berhasil dibuka.
- CP1–CP6 diimplementasikan; CP7 mencakup build, server-render/feature tests, pemeriksaan action/input, diff, dan perlindungan source. QA visual tetap dipisahkan dari pemeriksaan source.
- Tidak ada reset, revert perubahan pengguna, migrasi baru, dependency baru, atau perubahan backend.

## C. DESIGN DECISIONS

1. Phone memakai batas 430px, bukan mengubah seluruh rentang tablet. Pengecualian terarah adalah aksesibilitas drawer pada boundary existing 1023/1024, termasuk scroll pada tinggi ≤560px.
2. Tidak ada frontend mobile paralel. Table menggunakan record dan loop yang sama; CSS menyusun sel menjadi unit kerja berbeda sesuai tugasnya.
3. Pada bagian statis tertentu, node existing dipindahkan ke slot phone, bukan di-clone. DOM/focus order mengikuti tempat tampil. Ketika melewati 430px, node dikembalikan ke posisi asal desktop. Tidak ada Livewire component yang dipindahkan oleh mekanisme ini.
4. Summary/history/read-only detail memakai native disclosure. Server tetap merender isi lengkap dan open sebagai fallback; hanya phone yang menutup bagian pendukung. Anchor dan error membuka bagian terkait.
5. Tidak ada sticky Finalize, sticky Cancel, Admin bottom navigation, sheet universal, gradient baru, atau sistem token baru.

## D. ADMIN CORE

### Application queue — A04

Table 900px tidak lagi menjadi representasi kerja phone. Tiap record menampilkan ID/context, client, layanan, status/kebutuhan, pembaruan, lalu Tinjau dalam satu alur vertikal. Semua informasi tetap tersedia; identitas dan action tidak membutuhkan panning rutin menurut struktur/CSS baru.

Desktop tetap memakai table asli. Query, presenter next action, filter, pagination, dan authorization tidak berubah.

### Document queue — A07

Bukan gallery atau salinan dekoratif application card. Unit phone berpusat pada pengajuan, jumlah dokumen tersedia, kebutuhan perbaikan/review, status pemeriksaan, waktu, dan akses ke area dokumen. Angka tetap berasal dari kalkulasi existing; tidak dihitung ulang di frontend.

### Application detail/review — A05/A06

Identitas/status dan tautan bagian muncul lebih awal. Action stack tunggal ditempatkan menurut konteks presentasi dari status existing:

- memulai/operasi tahap lain: slot awal;
- UNDER_REVIEW: sesudah bukti dan keputusan dokumen;
- RESULT_REVIEW: sesudah hasil yang perlu diverifikasi.

Finalisasi tetap deliberate, tidak sticky, dan tidak mendahului bukti. Payment/data/history pendukung dapat dibuka tanpa mengubur area review. Urutan history phone mengikuti DOM setelah data/dokumen/hasil, tidak lagi default order 0 yang mendahului evidence.

Dokumen tetap memiliki private native open/new-tab, download, keputusan lokal, alasan/instruksi, dan riwayat versi. Target diperbesar; feedback Form Request ditampilkan pada form dokumen yang mengirim, dengan pilihan/teks lama dipulihkan pada form tersebut.

P1 A04/A07/A05 ditangani pada implementasi source. Kenyamanan rendered belum diberi visual PASS.

## E. CLIENT WORKSPACE

- C06: header identitas, compact progress dan next action tetap primer. Main task mengikuti header; aside tidak lagi mendahului form/dokumen pada phone. Ini disertai disclosure summary/history dan pengurangan density, bukan hanya membalik order sidebar.
- C07: form tunggal, field order, native controls, wire bindings, blur autosave, dan explicit Save dipertahankan. Helper/error diasosiasikan ke field; state simpan phone ditempatkan dekat tombol akhir. Optional representative tetap mounted agar values/error tidak hilang.
- C08: uploaded requirement menampilkan filename/version/status dengan treatment lebih ringkas. View/download/replace dipisahkan dari delete secara spasial. Status “mengunggah” menggunakan submit existing, tanpa progress persentase palsu atau uploader baru.
- Foto wajah: kamera + fallback file picker tetap. Phone mempunyai Batal mengambil foto yang menghentikan stream dan mengembalikan preview ke guide; tidak ada biometric/image-processing baru.
- C09: full history tersedia melalui disclosure dengan perubahan terakhir terlihat pada summary. Target #ringkasan ditambahkan untuk tautan existing. Result tetap di gate authorized/verified dan dapat dituju dari next action.
- Cancellation native dialog, alasan, konfirmasi, dan destructive separation dipertahankan. Tidak ada sticky cancel.

## F. PAYMENT

C10 selection mempertahankan konteks layanan dan nominal sebelum radio method/action. BCA, BRI, QRIS, dan disabled method menggunakan markup/form existing.

Pada waiting, status + nominal snapshot + VA/QR/instruksi/expiry/refresh didahulukan. Full reference summary dipindahkan setelah instruksi hanya pada phone. Badge status duplikat tidak lagi bersaing dengan heading phone.

VA copy, QR responsive sizing, checkout_url=null untuk VA, retry eligibility, provider confirmation versus browser return, failure/refund/cancel branches tetap.

Xendit/provider/controller/webhook: UNCHANGED. Tidak ada manual mark-paid, deep-link bank baru, atau asumsi sukses dari return URL. QRIS same-device handoff: MANUAL VISUAL/FUNCTIONAL QA REQUIRED.

## G. HELP / SUPPORT / CHAT

- C11: search/category dan hasil FAQ sekarang berurutan sesuai DOM pada phone. Bantuan pengajuan/percakapan menjadi kelompok sesudah FAQ, bukan menyela search-result. FAQ, guest boundary, thread reuse, dan arsip tetap.
- Mobile unread memakai satu global notifier existing, dengan presentasi badge noninteraktif yang diteleport ke tujuan Bantuan. Tidak ada notifier/polling kedua.
- A08: inbox Admin tetap row-list; context/snippet/metadata membungkus, action arsip mendapat ruang/tap target. URL inbox dengan filter/search dapat dipulihkan dalam tab yang sama dari chat, hanya jika origin/path sesuai route asal.
- C12/A09: app.css menjadi pemilik sizing kedua chat phone melalui selector scoped yang mengalahkan override vh lama di phase-b.css. Panel memakai dynamic viewport, menghapus minimum 420px yang konflik, dengan message area fleksibel dan composer tetap di alur panel.
- Delete overlay phone mendapat fokus awal, background inert, Tab containment, Escape existing, dan focus restoration. Composer mendapat error association dan ukuran teks phone yang sesuai.
- Polling, read, typing, send/edit/delete, archive, IME, near-bottom follow, reduced motion, dan quick reply non-autosend tetap. Tidak ada attachment/audio/WebSocket.
- Keyboard nyata, resize browser, posisi baca saat polling, dan lifecycle overlay setelah Livewire morph masih MANUAL QA REQUIRED.

Batas retrieval enam help threads tidak diubah. Jika QA data banyak membuktikan arsip lama tidak terjangkau, perubahan retrieval/navigation memerlukan scope terpisah; bukan diatasi diam-diam di backend.

## H. ADMIN SUPPORTING

- A03 dashboard: attention → priority work → recent updates → chart pada phone. Chart/data/period filter tidak dihapus; Livewire chart tetap di tempat mount asal.
- A10 Pengguna: lean lookup row; identity/email, status/context, dan detail tersedia tanpa representasi spreadsheet 900px. Detail pengguna memperoleh wrap/spacing foundation; masking dan read-only permissions tetap.
- A11 Aktivitas: event chronology dengan konteks/status/waktu yang tersedia, bukan kartu dashboard generik. Tidak menambahkan actor/metadata yang tidak disediakan dataset.
- A01 auth: error association dan ergonomics disempurnakan tanpa perubahan challenge/auth.
- C01–C05 supporting Client: landing P3 hanya refinement testimonial sempit; auth/control rhythm, service density, preflight requirement disclosure, application list satu boundary, dan dashboard secondary CTA disempurnakan.
- C13 email/framework errors/fake checkout: KEEP; tidak dilakukan invasive fix terhadap risiko visual yang belum terverifikasi.

## I. SHARED MOBILE SYSTEM

Konvensi terarah yang benar-benar diperkenalkan/refined:

- scoped ≤430px; outer gutter existing Client sekitar 14px dan Admin 16px dipertahankan, inset redundan pada list/form dikurangi;
- related content rapat, antarbagian task lebih lapang; body/field tetap terbaca dan metadata secondary;
- target utama/review/file/menu sekitar minimum 44px, native inputs phone 16px pada konteks yang disentuh;
- visible focus, long-string wrapping, label/helper/error association;
- meaningful requirement/record boundary, bukan semua informasi diberi card baru;
- native disclosure dengan anchor/error reveal;
- mobile-form-feedback shared untuk feedback form server; marker _ui_form hanya mengidentifikasi tempat presentasi error, bukan state/readiness;
- responsive table variants per tugas, bukan query/pagination baru;
- tidak ada overflow-x:hidden global baru untuk menyembunyikan masalah.

Ini bukan token migration, CSS cleanup global, atau perubahan loading order stylesheet.

## J. KEEP / REFINE / RESTRUCTURE

| Arah | Penerapan |
| --- | --- |
| KEEP | Empat tujuan Client bottom nav; enam tujuan Admin drawer; compact progress; dashboard priority model; native form; payment radio/copy/QR; private document viewing; native cancellation; masking; chat semantics. |
| REFINE | Gutter/spacing, field/error/save feedback, uploaded document density, camera exit, focus/tap targets, unread, support row, auth, service/preflight, landing P3. |
| RESTRUCTURE | A04/A07 phone records, A05 evidence/action placement, C06 task versus aside hierarchy, C09 supporting history, C10 waiting hierarchy, C11 FAQ continuity, C12/A09 chat cascade, A03/A10/A11 operational presentation. |

## K. FILES CHANGED

Desktop impact di bawah adalah analisis source, bukan screenshot comparison. “UI-only” berarti domain/security authority tetap; bukan klaim tidak ada perubahan interaksi presentasi.

| File | Reason / mobile effect | Desktop impact | Logic/security impact |
| --- | --- | --- | --- |
| resources/css/app.css | Shared targets/wrap, Admin row variants/detail/drawer, shared phone chat ownership | Main rules ≤430; drawer access ≤1023 dan short-height ≤560; perlu QA boundary | CSS saja |
| resources/css/phase-b.css | Client shell/workspace/form/doc/payment/help/supporting rhythm | Main rules ≤430; new-only utility defaults hidden | CSS saja |
| resources/js/app.js | Move same static nodes, disclosure/anchor/error reveal, drawer inert, support return, camera cancel/upload feedback | Node kembali pada >430; sidebar aktif ≥1024 | Presentasi saja; tidak clone/derive domain state |
| resources/views/admin/applications/show.blade.php | Evidence/action slots, disclosures, local document feedback | Original action placement restored; disclosure terbuka | Semua POST routes/gates tetap |
| resources/views/admin/dashboard.blade.php | Priority/recent slots dan labeled table cells | Original table/chart positions restored | Dataset/chart component tetap |
| resources/views/auth/forgot-password.blade.php | Field-error association | Tambahan feedback phone hidden desktop | Password broker unchanged |
| resources/views/auth/login.blade.php | Client/Admin local errors | Layout/flow unchanged | Auth unchanged |
| resources/views/auth/otp.blade.php | Client OTP autofill dan error association | No desktop redesign | Challenge/throttle unchanged |
| resources/views/auth/reset-password.blade.php | Error association | No desktop redesign | Token/reset unchanged |
| resources/views/chat/show.blade.php | Safe inbox-return presentation hook | Original desktop href restored | Route authorization unchanged |
| resources/views/client/applications/create.blade.php | Requirement detail disclosure | Open desktop | Consent/creation unchanged |
| resources/views/client/applications/show.blade.php | Summary/history disclosure dan #ringkasan | Original contents open; source order retained | Presenter/result/cancel gates unchanged |
| resources/views/client/payment/show.blade.php | Waiting amount/reference placement | Original summary position restored | Provider/state/form unchanged |
| resources/views/components/mobile-bottom-nav.blade.php | One unread destination target | Existing desktop hiding retained | Four routes unchanged |
| resources/views/components/mobile-form-feedback.blade.php | New reusable local error placement | Phone feedback hidden | UI marker only; server errors authoritative |
| resources/views/components/personal-document-card.blade.php | Filename, local upload/error feedback | New phone-only presentation hidden | One file input; private routes/rules unchanged |
| resources/views/components/personal-face-document.blade.php | Cancel camera and local upload/error UI | Cancel hidden desktop | Existing camera/fallback upload unchanged |
| resources/views/livewire/admin/activity-feed.blade.php | Chronological cell labels | Same table | Query/filter/pagination unchanged |
| resources/views/livewire/admin/application-queue.blade.php | Operational record labels | Same table/action | One dataset/action per record |
| resources/views/livewire/admin/document-queue.blade.php | Readiness/review record labels | Same table/action | Counts/review rules unchanged |
| resources/views/livewire/admin/support-inbox.blade.php | Return-context link marker | Row-list preserved | Sorting/archive unchanged |
| resources/views/livewire/admin/user-directory.blade.php | Lookup record labels | Same table/detail access | PII/auth/query unchanged |
| resources/views/livewire/application-details-form.blade.php | Associated errors, phone save state | Top status/form retained | Bindings/validation/autosave unchanged |
| resources/views/livewire/chat-thread.blade.php | Phone delete focus mechanics, composer error | Shared markup; phone focus branch | Chat commands/permissions unchanged |
| resources/views/livewire/global-chat-notifier.blade.php | Noninteractive mobile badge teleport | Original badge retained | One notifier/poll source |
| tests/Feature/MobilePresentationTest.php | Four semantic regression tests | Tests only | Synthetic PostgreSQL fixtures only |
| COMPLETE-MOBILE-UI-REDESIGN-IMPLEMENTATION.md | This implementation/verification handoff | None | Documentation only |

Build regenerates ignored public/build assets/manifest as expected. No dependency, source asset, route, backend class, migration, or existing test was rewritten.

## L. BUSINESS LOGIC CONFIRMATION

| Area | Status |
| --- | --- |
| Application state machine dan authoritative readiness | UNCHANGED |
| Data/dokumen → submit → payment → review ordering | UNCHANGED |
| Xendit/provider truth, webhook authentication/idempotency/retry | UNCHANGED |
| Document requirements, scanning, versions, locks, readiness | UNCHANGED |
| Admin assignment, review, finalization, result verification/completion | UNCHANGED |
| OTP, authentication, sessions, authorization | UNCHANGED |
| Chat text/read/typing/polling/send/edit/delete/archive semantics | UNCHANGED |
| Database schema/config/runtime workflow | UNCHANGED |

Tests use the isolated PostgreSQL testing database and synthetic fixtures. This does not mean tests perform no test-database writes. No migrate:fresh command or schema implementation was requested/run.

## M. SECURITY CONFIRMATION

205 existing files in backend/config/routes/database/dependency/test configuration scope have identical SHA-256 hashes to CP0. No diff in frozen authority.

Persistent middleware, policies, email verification, OTP, session regeneration, throttling, CSRF/CSP, private-file access, encrypted/masked PII, application/document/payment locks, assignment, provider evidence, webhook idempotency, malware scanning, retention, and production checks remain intact.

No public private-file URLs, iframe/CSP relaxation, new payment truth, or production fake-payment mechanism introduced. _ui_form is not trusted for authorization or state decisions. Existing tests remain authoritative; this is not a new full security certification.

## N. VISUAL QA

Exact browser screens and widths actually inspected: NONE.

| Coverage | Actual status |
| --- | --- |
| Client landing/auth/dashboard/services/create/list/workspace/payment/help/chat at 320, 360, 375, 390, 412, 430 | NOT VERIFIED — MANUAL VISUAL QA REQUIRED |
| Admin auth/drawer/dashboard/queues/detail/review/support/chat/users/activity at those six widths | NOT VERIFIED — MANUAL VISUAL QA REQUIRED |
| Short height/landscape, software keyboard, text zoom, long names/email/file/reference, edge menus | NOT VERIFIED — MANUAL QA REQUIRED |
| Server-side rendered branches, local errors, form counts, private links, authoritative actions | PASS through feature tests; not visual PASS |

No fabricated screenshots, dimensions, computed styles, or overflow measurements. ERR_NGROK_6024 is not treated as an application defect and no asset/CSP/proxy workaround was introduced.

## O. DESKTOP REGRESSION

Actual browser comparisons at 1280/1440/768/1024: NONE — NOT VERIFIED.

Source protection verified: layout-specific rules are inside ≤430px; desktop tables/datasets remain; static moved nodes return to their original position above430; new disclosure content stays open above430; original polling/form/file inputs are not duplicated.

Relevant manual boundaries:

- 430/431 for every changed phone surface;
- 560px height for landscape drawer scrolling, and 1023/1024 for drawer availability/inert reset;
- 767/768, 820/821, 850/851, 959, and 1180 where existing shell/help/workspace compositions change;
- 768/1024/1280/1440 for shared templates and chat.

These are QA targets, not widths claimed tested. Existing desktop rules were not broadly rewritten.

## P. TEST VERIFICATION

| Command | Result |
| --- | --- |
| npm run build | PASS |
| php artisan optimize:clear | PASS |
| php artisan test --no-coverage | 398 passed / 2558 assertions; final run 155.61s |
| vendor/bin/pint --test | PASS; executed Windows equivalent php vendor/bin/pint --test |
| git diff --check | PASS |

PostgreSQL is authoritative: pgsql, 127.0.0.1:5432, bantu_daftarin_mvp_test, public. No SQLite substitution or added skip.

Four new tests protect one authorized record action, one review/finalize form, private native evidence access, one named upload per requirement, stable anchor/notifier identity, and local error placement. They do not assert exact Tailwind class strings.

Source comparison: all 21 changed Blade templates retain original form/action signatures and file-input counts; the new feedback component has no form/file input.

An initial test run exposed a Blade directive compilation regression in the new NIK/KK error association. It was corrected with ordinary Blade expressions; targeted tests and the complete suite subsequently passed. No test expectation or skip was altered to hide that failure.

## Q. REMAINING MANUAL QA

1. On six phone widths, complete one synthetic Client personal and business application through editable/read-only/revision/result states; verify document action wrapping, returned validation location, Save feedback, cancellation focus, and #ringkasan/history anchors.
2. Operate Admin queues with long synthetic identity/reference, filters/search/pagination; inspect evidence, decisions, finalization and result review in their mobile positions. Confirm private PDF/image native viewing and return context.
3. On real phone keyboards, test Client + Admin chat typing/IME, send/edit/delete/escape/focus restoration, polling while reading older messages, near-bottom follow, and composer reachability. The CSS viewport correction has no real-device PASS yet.
4. Verify drawer closed focus exclusion, open/close/return focus, logout reachability at short height/landscape, and 430/431 plus 1023/1024 resizing. Check Client account menu and single-source unread updates.
5. Verify BCA/BRI copy/return and QRIS same-device handoff with existing provider-supported behavior; payment waiting/expiry/failure/success hierarchy. No new handoff capability is claimed.
6. Perform desktop/boundary visual comparisons listed in O, and check page-level horizontal overflow/text zoom/edge menus across changed surfaces.
7. C13 remains unverified in actual email clients/framework error/fake checkout rendering; no speculative modifications were made. Help archives with more than six threads need reachability verification before any separately scoped retrieval work.

No unresolved business/aesthetic decision is required to use this patch.

## R. FINAL CLASSIFICATION

MOBILE REDESIGN IMPLEMENTED — READY FOR REAL-DEVICE QA
