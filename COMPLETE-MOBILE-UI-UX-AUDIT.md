# COMPLETE MOBILE UI/UX AUDIT — CLIENT + ADMIN

Tanggal audit: 12 September 2026  
Produk: Bantu Daftarin, checkout lokal yang sedang aktif  
Lingkup: mobile 320, 360, 375, 390, 412, dan 430px; Client + Admin; AUDIT ONLY  
Artefak ini bukan implementasi, mockup final, audit keamanan baru, atau sertifikasi aksesibilitas.

## A. SKILLS AND INSTRUCTIONS

### Instruksi dan sumber yang dibaca

- AGENTS.md: dibaca penuh, termasuk scope MVP, PostgreSQL, source-of-truth, workflow, security invariants, disiplin perubahan, dan aturan skill.
- DESIGN.md: dibaca penuh. Identitas layanan administrasi yang tenang dan praktis menjadi dasar rekomendasi; bukan produk baru.
- docs/00-product/README.md; docs/01-domain/lifecycle.md; docs/02-architecture/architecture.md; docs/03-security/security.md; docs/05-integrations/integrations.md; docs/06-ux/routes.md; docs/07-testing/manual-acceptance-checklist.md; docs/10-open-questions/README.md; docs/IMPLEMENTATION_STATUS.md.
- Route aktif, controller, Livewire, presenter status, enum, workflow service/action, dan policy yang relevan ditelusuri untuk membedakan representasi UI dari kewenangan bisnis. Dokumen historis tidak dipakai untuk menimpa implementasi aktif atau membuktikan hasil test terkini.
- Tidak ditemukan berkas master prompt terpisah melalui pencarian nama; tidak diklaim telah dibaca. Instruksi eksplisit task dan AGENTS.md yang tersedia tetap dipatuhi.

### Skill

Dibaca penuh sebelum keputusan desain:

1. .codex/skills/antislop/ANTISLOP.md
2. .codex/skills/antislop/SKILL.md
3. .codex/skills/antislop-ui/SKILL.md
4. .codex/skills/antislop-layoutmobile/SKILL.md

Core diterapkan selama penelusuran, penilaian, dan rekomendasi. Instruksi generik skill yang mengarah ke implementasi, pertanyaan preferensi layout, atau perluasan tema tidak dijalankan karena bertentangan dengan AUDIT ONLY dan scope proyek.

Skill computer-use juga dibaca untuk pemeriksaan ketersediaan browser. Tidak dilakukan kontrol native, login, atau perubahan aplikasi. Antislop-copywriting tidak dimuat: tidak ada penulisan ulang copy material; label dibaca untuk memahami hierarchy dan semantics. Antislop-human tidak dimuat: audit foto wajah menyangkut upload/kamera, bukan perubahan representasi manusia. Figma tidak digunakan sebagai sumber workflow atau layout baru.

### Metode, bukti, dan batas kepastian

Inventory dibangun dari routes/web.php, routes/client.php, routes/admin.php, controller tujuan, seluruh kelompok view aktif, komponen bersama, CSS yang benar-benar dimuat, dan JavaScript/Livewire terkait. View lama yang tidak dirender route dipisahkan.

Label bukti:

- SOURCE CONFIRMED: struktur, conditional branch, selector, ukuran deklaratif, atau perilaku event terbukti dalam source. Bukan klaim screenshot.
- RENDER RISK: konsekuensi visual/interaksi yang masuk akal dari struktur tersebut, tetapi perlu diuji pada browser/device.
- VISUAL QA NOT VERIFIED: tidak ada inspeksi rendered untuk surface itu.
- NOT VERIFIED: pengujian runtime atau perilaku perangkat belum dilakukan.

Browser inventory mengembalikan apps kosong dan browsers kosong; usaha memperoleh browser untuk http://127.0.0.1:8000 mengembalikan “No browser is available”. Maka tidak ada screenshot, pengukuran DOM, pengujian viewport, atau sesi Client/Admin yang berhasil dibuka. Semua screen di bawah mendapat structural source audit, bukan rendered pass.

Tidak dibuat akun/fixture, tidak dilakukan upload, pembayaran, chat read/send, atau transisi status. Membuka chat pun dapat mengubah read state; itu tidak dilakukan.

| Viewport target | Strategi aktif menurut source | Risiko utama yang harus diuji | Hasil visual |
| --- | --- | --- | --- |
| 320px | Client gutter 14px, header mobile, bottom nav; label nav menyusut pada aturan ≤370px. Admin gutter 16px, drawer, detail satu kolom. | Lebar efektif dalam nested panel, label/status panjang, action kecil, menu dan keyboard. | VISUAL QA NOT VERIFIED |
| 360px | Aturan mobile yang sama; nav masih cabang ≤370px. | Kepadatan dokumen, label navigasi kecil, composer dan payment code. | VISUAL QA NOT VERIFIED |
| 375px | Cabang nav di atas 370px; shell tetap mobile. | Peralihan ukuran label, filter lokal dan file actions. | VISUAL QA NOT VERIFIED |
| 390px | Header Client tidak menampilkan context title pada ≤430px. | Context setelah scroll, workspace panjang, drawer dan dialog. | VISUAL QA NOT VERIFIED |
| 412px | Semua layout operasional masih satu kolom; tabel Admin tetap min-width 900px. | Jarak identitas record ke tindakan, chat viewport. | VISUAL QA NOT VERIFIED |
| 430px | Batas aturan gutter/header kecil Client; bukan desktop. | Edge breakpoint dan perbandingan 430/431, sticky/safe area. | VISUAL QA NOT VERIFIED |

Angka pada tabel adalah pembacaan CSS, bukan hasil computed style. Tidak ada klaim “bebas overflow” atau “lulus keenam viewport”.

## B. EXECUTIVE SUMMARY

Client sudah memiliki fondasi mobile yang nyata, bukan sekadar seluruh grid ditumpuk. Empat tujuan utama tersedia pada bottom navigation, progress memiliki versi mobile yang ringkas, form utama memakai ukuran kontrol yang cukup baik, dan dokumen memiliki adaptasi satu kolom. Identitas, terminology, serta pola status layak dipertahankan.

Kelemahan Client paling besar adalah prioritas informasi pada halaman panjang. Seluruh sidebar workspace didahulukan sebelum data/dokumen; bantuan, ringkasan berulang, dan pembatalan mendapat posisi terlalu awal. Halaman Bantuan memiliki urutan CSS yang memisahkan pencarian dari hasil FAQ. Chat memiliki cascade ukuran viewport yang perlu dirapikan dan diuji dengan keyboard nyata.

Admin memiliki shell mobile dan filter yang dapat dipakai, tetapi area kerja utamanya masih desktop-oriented. Lima table surface menggunakan minimum 900px. Pada detail pengajuan, kontrol tindak lanjut turun sesudah keseluruhan konten panjang. Ini lebih serius daripada masalah radius, warna, atau dekorasi: identitas record, bukti, dan tindakan tidak berada pada jarak interaksi yang wajar untuk phone.

Prioritas tertinggi:

- P1: pengoperasian antrean Pengajuan/Dokumen Admin dengan identitas dan tindakan berjauhan.
- P1: kontrol workflow Admin terkubur setelah data, dokumen, hasil, dan riwayat.
- P2: urutan workspace Client, urutan Bantuan, hierarchy riwayat Admin, konsistensi feedback upload/form, serta keyboard/dialog/chat.
- P3: pengurangan chrome, gutter, metadata, repetisi status, dan polish public/auth.

Tidak ada P0 yang terbukti. P1 di atas merupakan penilaian usability berdasarkan struktur terkonfirmasi, bukan klaim bahwa endpoint gagal atau button tidak dapat diklik.

Filosofi redesign berikutnya: pertahankan produk, ubah prioritas dan penempatan informasi di mobile. Gunakan responsive adaptation untuk shell/form/payment yang sudah sehat; gunakan restructuring terarah untuk workspace panjang dan antrean operasional. Jangan mengganti semua isi menjadi card, mengecilkan semua teks, atau menambah bottom nav Admin hanya agar tampak seperti aplikasi mobile.

## C. UI INVENTORY

### Cara membaca inventory

Untuk setiap baris aktif, audit coverage mencakup struktur view, komponen utama, responsive CSS, dan branch/state yang terkait. “Source; VNV” berarti structural source audit selesai dan Visual QA status: VISUAL QA NOT VERIFIED pada seluruh enam viewport. Redirect tidak memiliki layout visual sendiri. Prefix view di bawah adalah resources/views/; nama layout menunjuk layouts/*.blade.php.

### CLIENT — screen aktif

| Route / screen | Purpose | Layout; primary view/component | Major shared components | Existing mobile strategy | Audit coverage / Visual QA status |
| --- | --- | --- | --- | --- | --- |
| GET /, pengguna belum login | Landing, pilihan layanan, cara kerja, FAQ awal, masuk/daftar | marketing; home.blade.php | site-header, favicon, tombol, JS FAQ/carousel | Header menu mobile; hero/layanan/steps ditumpuk; FAQ jawaban inline mobile | Source seluruh section; VNV |
| GET /qna, guest | Cari FAQ dan akses akun untuk bantuan | marketing; qna.blade.php | site-header; FAQ/search/filter | FAQ satu kolom, filter scroll lokal, bantuan login di bawah | Source guest/search/empty; VNV |
| GET /register | Pembuatan akun Client | guest; auth/register.blade.php | feedback, pb-field/pb-button | Form satu kolom pada phone; ilustrasi desktop tidak menjadi kolom mobile | Source normal/validation; VNV |
| GET /login | Login Client dan feedback verifikasi | guest; auth/login.blade.php | feedback; conditional verification panel | Single-column auth; kontrol utama besar | Source normal/error/unverified-email; VNV |
| GET /forgot-password | Permintaan reset password | guest; auth/forgot-password.blade.php | feedback/form | Form tunggal, satu kolom | Source request/feedback; VNV |
| GET /reset-password/{token} | Password baru | guest; auth/reset-password.blade.php | feedback/form | Form tunggal, satu kolom | Source form/error; VNV |
| GET /auth/otp | OTP Client | guest; auth/otp.blade.php, branch Client | flow card, feedback, resend | Satu input numerik, bukan enam field terpisah | Source input/resend/error; VNV |
| GET /app/dashboard | Next action, pengajuan aktif, pembaruan | client; client/dashboard.blade.php | client-header, mobile-bottom-nav, application-list-item, notifier | Priority panel dan list mobile; desktop columns ditumpuk | Source empty/active/updates; VNV |
| GET /app/services | Katalog layanan dan melanjutkan pengajuan aktif | client; client/services/index.blade.php | shell, service cards/status | Card satu kolom; min-height desktop dilepas | Source dua NPWP/COMING_SOON/resume; VNV |
| GET /app/applications | Daftar seluruh pengajuan Client | client; client/applications/index.blade.php | shell, status/action cards | Filter scroll lokal; cards stack | Source semua filter/empty; VNV |
| GET /app/applications/create/{service} | Preflight persyaratan dan membuat workspace | client; client/applications/create.blade.php | shell, service/requirement summary, form | Summary + form menjadi urutan vertikal | Source Perseorangan/Badan, consent/errors; VNV |
| GET /app/applications/{publicId} | Workspace pengajuan semua tahap | client; client/applications/show.blade.php | application-progress, application-details-form, personal-document-card, personal-face-document, status, cancellation dialog | Progress mobile; main/aside satu kolom, aside order -1; document cards mobile | Source semua branch yang tersedia; VNV |
| GET /app/bayar/{publicId} | Pemilihan metode/instruksi/status pembayaran | client; client/payment/show.blade.php | shell, payment status, copy-value JS, radio fields | Summary/action stack; metode satu kolom; QR responsif | Source semua state dan metode; VNV |
| GET /qna, Client | FAQ, bantuan pengajuan, percakapan dan kontak umum | client; qna.blade.php | shell/notifier, help cards, native details | Sidebar display:contents; urutan mobile berbasis order | Source active/archived/empty/search; VNV |
| GET /app/chat/{publicId} | Chat pengajuan atau bantuan umum | client; chat/show.blade.php + livewire/chat-thread.blade.php | ChatThread, notifier, presence | Panel thread dengan scroller dan composer; ukuran viewport bercampur | Source read/typing/send/edit/delete/archive/error; VNV |
| Menu akun Client, di seluruh shell | Identitas, Bantuan, logout | client; components/client-header.blade.php | native details; POST logout | Nama utama disederhanakan; menu ditempatkan fixed pada phone kecil | Source open/closed structure; VNV |
| GET /app/documents/{documentId}/view dan /download | Membuka/mengunduh file private | Stream dari DocumentController, bukan Blade viewer | Policy/private-file response | Viewer native browser/device, tidak dikendalikan layout aplikasi | Source rute/gate/tautan; VNV |
| GET /app/results/{resultId}/view dan /download | Akses hasil yang diizinkan | Stream dari ResultDocumentController | Policy dan verification gate | Viewer/download native | Source rute/gate/tautan; VNV |

GET / ketika sudah login mengarahkan ke dashboard sesuai role, bukan landing kedua.

### CLIENT — state dan embedded surfaces

| Surface di route induk | Primary view/component | Existing mobile strategy / coverage | Visual QA status |
| --- | --- | --- | --- |
| Data Perseorangan, Data Badan, penanggung jawab, perwakilan opsional | livewire/application-details-form.blade.php | Field grouping, blur save, explicit save, local error, locked read-only; seluruh branch source ditinjau | VISUAL QA NOT VERIFIED |
| Persyaratan, belum upload, file terkini, penggantian, penolakan, malware/scan/review state | components/personal-document-card.blade.php, dipakai lintas jenis layanan | Header/status/file actions + auto-upload, single column pada phone | VISUAL QA NOT VERIFIED |
| Foto wajah: kosong, file picker, kamera aktif, capture, fallback | components/personal-face-document.blade.php + app.js | Camera/upload stack; preview inline | VISUAL QA NOT VERIFIED |
| Kirim pengajuan, kirim dokumen, kirim revisi, state terkunci | client/applications/show.blade.php | CTA/conditional notice mengikuti presenter dan service | VISUAL QA NOT VERIFIED |
| Pembayaran di workspace | client/applications/show.blade.php | Ringkasan inline dan link halaman bayar, hanya pada branch terkait | VISUAL QA NOT VERIFIED |
| Pemeriksaan, revisi, proses eksternal, hasil, selesai, archived/cancelled | client/applications/show.blade.php + ApplicationStatusPresenter | Status/next action/progress; timeline dan hasil conditional, bukan route baru | VISUAL QA NOT VERIFIED |
| Riwayat dan estimasi | Workspace dan application-list-item | Timeline vertikal dan metadata; tidak dipaginasi pada workspace | VISUAL QA NOT VERIFIED |
| Pembatalan, alasan umum/lainnya, error dialog | Workspace native dialog | Lebar fluid, tinggi dibatasi, scroll internal, tombol bertumpuk | VISUAL QA NOT VERIFIED |
| BCA VA, BRI VA, QRIS, checkout URL fallback | client/payment/show.blade.php | Copy VA, gambar QR max lebar container, external checkout link | VISUAL QA NOT VERIFIED |
| Payment selection, waiting, success, failure, refund, cancelled, QR unavailable | client/payment/show.blade.php | Satu template bercabang; bukan layar fiktif per bank | VISUAL QA NOT VERIFIED |
| PayPal | Payment method disabled jika disediakan katalog | “Segera hadir”; bukan payment path produksi yang direkomendasikan | VISUAL QA NOT VERIFIED |
| Global chat notification | livewire/global-chat-notifier.blade.php | Badge di nav desktop; toast diteleport ke body; tidak ada badge setara pada bottom nav | VISUAL QA NOT VERIFIED |
| Validation, flash success/error, loading, no records/search results | Layouts, form, FAQ, chat dan lists masing-masing | Global feedback + local states; tidak satu template universal | VISUAL QA NOT VERIFIED |

### CLIENT — alias, actions, dan surface tambahan

| Route/kelompok | Hasil aktual / cakupan |
| --- | --- |
| /daftar | Redirect /register; tidak perlu audit layout kedua. |
| /jenis-badan | Redirect ke pembuatan pengajuan Badan; view business-type lama bukan screen aktif route ini. |
| /npwp-pribadi, /npwp-badan | Entry diarahkan ke workspace aktif atau create yang relevan. |
| /npwp-pribadi/{publicId}, /npwp-badan/{publicId} | Redirect workspace #data-dokumen setelah pemeriksaan ownership/type; bukan form mobile paralel. |
| /app/activity | Redirect listing Pengajuan. |
| /app/activity/{publicId} | Redirect workspace #proses setelah authorization. |
| /email/verify/{publicId}/{hash} | Signed verification action lalu redirect/feedback. Tidak ada layar verifikasi email terpisah yang dirender. Tidak dieksekusi dalam audit. |
| POST register/login/password/reset/verification resend/OTP/resend/logout | Action milik screen auth/akun di atas; feedback dan error ditinjau tanpa mengirim form. |
| POST /app/applications; PUT /app/applications/{publicId} | Create/update workspace, bukan screen tambahan. |
| POST .../{publicId}/submit, /documents/submit, /revision/submit, /payment; PATCH .../{publicId}/cancel | Workflow actions. Representasi CTA/audit dialog termasuk cakupan; state machine tidak diubah. |
| POST /app/bayar/{publicId} | Membuat instruksi pembayaran. Tidak dipanggil. |
| POST /app/applications/{applicationId}/requirements/{requirementId}/documents; DELETE /app/documents/{documentId} | Upload/delete private document; source UI dan error context ditinjau. |
| POST /app/bantuan/chat | Create/reuse general support thread lalu redirect; bukan halaman GET tersendiri. Tidak dipanggil. |
| GET /testing/fake-payments/{paymentId}/checkout; POST .../complete | Hanya local/testing dengan driver fake; testing/fake-payment.blade.php, layouts.app. Source audit tambahan; VISUAL QA NOT VERIFIED. Bukan produksi dan tidak dipakai untuk membenarkan fake production payment. |
| Email transaksi/auth/status | mail/transactional.blade.php dan transactional-text.blade.php; ancillary mobile email, source only; VISUAL QA NOT VERIFIED pada email client. |
| 403/404/419/429/500 | Tidak ditemukan custom application error Blade dalam inventory. Respons framework/domain belum dibuka; VISUAL QA NOT VERIFIED. |
| Akun/profil sendiri, service detail publik mandiri | Tidak ditemukan route screen terpisah; tidak direkomendasikan menambah fitur ini dalam redesign mobile. |

### ADMIN — screen aktif

| Route / screen | Purpose | Layout; primary view/component | Major shared components | Existing mobile strategy | Audit coverage / Visual QA status |
| --- | --- | --- | --- | --- | --- |
| GET /admin/login | Login Admin | admin-auth; branch auth/login.blade.php | admin auth controls/feedback | Form login tunggal | Source normal/error; VNV |
| GET /admin/otp | OTP Admin | admin-auth; branch auth/otp.blade.php | OTP/feedback/resend | Input numerik tunggal, one-time-code | Source normal/error/resend; VNV |
| GET /admin/dashboard | Prioritas, volume aktivitas, update terakhir | admin; admin/dashboard.blade.php | admin header/sidebar/page-header/status; livewire/admin/activity-chart | Metrics stack; chart fluid; priority table scroll lokal | Source metrics/chart/queue/empty; VNV |
| GET /admin/applications | Antrean dan pencarian pengajuan | admin; admin/applications/index.blade.php + livewire/admin/application-queue.blade.php | search/filter/status/pagination/loading | Toolbar stack; filter horizontal; table min-width 900px | Source seluruh filter/search/loading/empty/pagination; VNV |
| GET /admin/applications/{publicId} | Review, data, dokumen, pembayaran, proses, hasil | admin; admin/applications/show.blade.php | admin status, review/action forms, private file links | Header/detail grid stack; document grid satu kolom; aside setelah main | Source semua conditional action/state; VNV |
| GET /admin/documents | Antrean dokumen dan kebutuhan pemeriksaan | admin; admin/documents/index.blade.php + livewire/admin/document-queue.blade.php | search/filter/table/pagination/loading | Table min-width 900px dan scroller lokal | Source counts/filter/search/empty/pagination; VNV |
| GET /admin/support | Inbox dukungan | admin; admin/support/index.blade.php + livewire/admin/support-inbox.blade.php | filters/search/unread/archive/pagination | Row list, metadata kanan, menu archive lokal | Source application/general/unread/archived/empty/loading; VNV |
| GET /admin/chat/{publicId} | Percakapan operasional | admin; chat/show.blade.php + livewire/chat-thread.blade.php | ChatThread; quick replies; presence/notifier | Panel chat mobile, composer dan internal scroll | Source read/typing/edit/delete/archive/quick reply; VNV |
| GET /admin/users | Direktori Client | admin; admin/users/index.blade.php + livewire/admin/user-directory.blade.php | search/filter/table/pagination/loading | Table min-width 900px | Source all states yang ada; VNV |
| GET /admin/users/{publicId} | Detail Client baca-saja dan pengajuannya | admin; admin/users/show.blade.php | page-header, compact rows, pagination | Summary/detail stack; rows ringkas | Source metadata/empty/paginated applications; VNV |
| GET /admin/activity | Feed aktivitas operasional | admin; admin/activity/index.blade.php + livewire/admin/activity-feed.blade.php | search/filter/table/pagination/loading | Table min-width 900px | Source filter/feed/empty/loading; VNV |
| Drawer/header/identity/logout | Navigasi enam area Admin | components/admin/sidebar.blade.php; header.blade.php; layouts/admin.blade.php | app.js menu handling | Offcanvas ≤1023px; menu button; backdrop/focus trap ketika terbuka | Source open/closed/keyboard code; VNV |
| GET /admin/documents/{documentId}/view dan /download | Bukti dokumen private | DocumentController stream | Policy/private-file access | Native browser viewer/download; tidak ada custom modal viewer | Source gate/link/response; VNV |
| GET /admin/results/{resultId}/view dan /download | Bukti hasil private | ResultDocumentController stream | Policy/verification workflow | Native browser viewer/download | Source gate/link/response; VNV |

### ADMIN — embedded action/state inventory

Semua actions berikut berada pada detail pengajuan atau thread, bukan halaman tambahan:

- POST /admin/applications/{publicId}/review/start dan /review/finalize.
- POST .../{publicId}/estimate dan /external/waiting.
- POST .../{publicId}/results dan /results/review.
- POST /admin/results/{resultId}/verify.
- POST .../{publicId}/complete dan /archive.
- POST /admin/documents/{documentId}/review.
- POST /admin/support/{publicId}/archive dan /unarchive.
- Login/OTP/logout memakai auth action yang terkait; OTP Admin memakai verifikasi/resend bersama.

Source coverage mencakup keputusan dokumen, alasan/instruksi revisi, estimasi dan reason, upload hasil, verifikasi/penolakan hasil, completion gate, private preview/link, version history, cancellation/late-payment notice, assignment/locking notices, blank states, serta feedback error. Semuanya VISUAL QA NOT VERIFIED; tidak ada keputusan operasional yang disubmit.

Tidak ditemukan fitur edit profil Admin sendiri, manajemen role tambahan, bulk action generik, upload chat, audio, atau route “document viewer” khusus. Jangan mengarang inventory dari contoh dalam brief.

### Shared, dormant, dan non-screen

- Layout aktif: marketing, guest, client, admin-auth, admin; app dipakai checkout fake lokal.
- Komponen aktif lintas page mencakup favicon, presence-heartbeat, client-header, mobile-bottom-nav, site-header, application-list-item, application-progress, personal-document-card, personal-face-document, admin/page-header dan status-badge.
- View lama client/activity/*, client/registration/*, welcome.blade.php beserta komponen registration lama bukan dasar menemukan bug pada route aktif. Route alias sekarang mengarah ke workspace. Jangan redesign dua frontend atau menghapus legacy dalam task mobile.
- CSS/JS masih mempunyai selector/handler bagi pola lama, termasuk section selector workspace yang tidak ada pada markup workspace aktif. Keberadaan selector bukan bukti fitur tersedia.
- Presence heartbeat, Livewire transport/assets, health endpoint, webhook, notification jobs, dan email delivery bukan halaman mobile tersendiri. Tidak dipanggil untuk “melengkapi” screenshot.
- Direktori backupcode_bantudaftarin bukan source route runtime yang diaudit.

## D. CLIENT MOBILE FINDINGS

Semua temuan di bagian D bersifat source-grounded; visual/runtime tetap NOT VERIFIED. ID temuan dipakai pada priority matrix dan rencana implementasi.

### C01 — Public landing dan navigasi publik

Screen: / beserta #layanan, #cara-kerja, #qna.  
Current structure: header publik, hero/brand/art, manfaat, dua layanan aktif + coming soon, lima langkah, testimonial demo, FAQ, closing CTA, footer.  
What works: mobile menu sudah tersedia; FAQ memiliki jawaban inline khusus phone; langkah berurutan tetap terbaca; CTA awal tidak menunggu footer; label demo testimonial eksplisit.  
Problems: P3 density. Pengulangan register pada beberapa titik masuk akal pada landing panjang, tetapi semua tombol tidak perlu memiliki bobot sama di viewport pendek. Card testimonial masih mengalokasikan kolom dekoratif 30px + 3px dan dua gap 14px selain padding 22px per sisi pada mobile. Ini mengurangi lebar teks secara berarti pada 320px. Tinggi hero/section perlu dibuktikan dengan render sebelum dipangkas.  
Priority: P3; tidak ditemukan bukti P1/P0.  
Recommended direction: pertahankan urutan informasi, kurangi space dekoratif testimonial pada phone terkecil, bedakan CTA register dari link eksplorasi, uji menu saat landscape/text zoom. Jangan mengganti hero atau mengubah isi testimonial menjadi klaim pelanggan nyata.  
Elements to preserve: logo, artwork, warna, service identity, label demonstrasi, inline FAQ, akses login/daftar.  
Desktop regression risk: tinggi pada home karena beberapa generasi selector/override di phase-b.css; jangan mengubah style hero base secara global.

Hierarchy: layanan dan cara mulai primer; langkah dan persyaratan sekunder; testimonial/footer pendukung. Tidak ada destructive action.

### C02 — Register, login, verifikasi, reset password, OTP

Screen: /register, /login, /forgot-password, /reset-password/{token}, /auth/otp; verifikasi email sebagai state/redirect.  
Current structure: guest shell; intro/form; feedback global; input dan submit; OTP satu field + resend.  
What works: mobile tidak membawa ilustrasi desktop sebagai kolom; input Client umumnya 16px dan tinggi 46px; register sudah mengaitkan aria-invalid/describedby dengan error; OTP tidak dipecah menjadi enam kotak; verification/resend tetap mengikuti backend.  
Problems: target resend OTP Client tidak memiliki jaminan tinggi setara 44px; one-time-code ada pada branch Admin tetapi tidak pada Client. Feedback global pada form selain register tidak konsisten dengan association per-field. Intro + kicker + heading form dapat menambah scroll tanpa membantu tugas pendek; dampak actual keyboard belum diuji.  
Priority: P2 ergonomi target/error association; P3 konsistensi OTP/intro.  
Recommended direction: satu hierarchy judul → form → bantuan akun; perluas hit area link form yang sering dipakai; samakan association error dan dukungan autofill tanpa mengubah OTP/session/throttle. Jangan menambah login flow atau mengurangi keamanan demi kecepatan.  
Elements to preserve: label eksplisit, password rules, form tunggal, native keyboard intent, pesan keamanan dan verification panel.  
Desktop regression risk: sedang–tinggi karena satu view auth memiliki branch role dan beberapa shell; perubahan selector harus dibatasi per branch/mobile.

Primary action adalah submit screen aktif. Resend/reset link sekunder, bukan pesaing tombol utama. Tidak ada account/profile page baru yang diperlukan.

### C03 — Dashboard Client

Screen: /app/dashboard.  
Current structure: ringkasan next action yang dipilih dari status aktual, daftar pengajuan aktif terbatas, pembaruan terakhir, akses layanan.  
What works: next action bermakna; list ringkas lebih sesuai phone daripada tabel; tidak menampilkan angka dekoratif yang tidak membantu Client.  
Problems: panel ringkasan dan item pengajuan dapat mengulang status/CTA yang sama; shortcut layanan di bagian bawah menambah bobot setelah informasi tugas. Ini bukan alasan merombak dashboard.  
Priority: P3.  
Recommended direction: pertahankan satu tindakan terpenting; secondary list cukup identitas, status, dan konteks perubahan. Kurangi repetisi visual tanpa membuang akses layanan.  
Elements to preserve: empty state yang memberi jalur mulai, active/updates grouping, application-list-item.  
Desktop regression risk: sedang karena komponen list digunakan di area lain; jangan mengubah seluruh density desktop.

Hierarchy: tindakan mendesak primer; pengajuan lain sekunder; riwayat singkat dan layanan pendukung.

### C04 — Katalog dan preflight pengajuan

Screen: /app/services dan /app/applications/create/{service}.  
Current structure: dua layanan bookable, coming soon terpisah; create menampilkan konteks layanan, biaya/persyaratan, dan form pembuatan workspace.  
What works: resume pengajuan aktif; harga/persyaratan terlihat sebelum komitmen; single-column sudah ada; layanan coming soon tidak diperlakukan sebagai CTA aktif.  
Problems: preflight panjang berpotensi menunda input/tombol pada phone; requirements dan beberapa panel dapat menjadi stack seragam. Ini berbeda dari workspace: menempatkan ringkasan sebelum komitmen di preflight justru masuk akal.  
Priority: P2 untuk panjang preflight; P3 katalog.  
Recommended direction: overview ringkas biaya + cakupan + hal yang perlu disiapkan, kemudian pilihan/form; rincian persyaratan yang panjang dapat memakai disclosure dengan daftar tetap tersedia. Jangan menyembunyikan consent atau membuat langkah bayar sebelum submit.  
Elements to preserve: service terminology, snapshot biaya, requirement rules, consent, active-resume, backend refusal COMING_SOON.  
Desktop regression risk: sedang; form create juga memuat aturan bisnis sehingga perubahan hanya wrapper/hierarchy, bukan input name/value/validation.

### C05 — Daftar Pengajuan Client

Screen: /app/applications.  
Current structure: heading, filter state, list cards berisi identitas/status/metadata/actions di dalam panel.  
What works: bukan tabel lebar; filter mobile memiliki scroll lokal dan target yang layak; state/no-results berbeda dari empty total.  
Problems: card di dalam panel mengurangi ruang pada 320px. Secara deklaratif, page 292px dikurangi padding/border panel dan card dapat menyisakan sekitar 212px untuk isi; ini ilustrasi perhitungan CSS, bukan pengukuran DOM. Footer button memiliki override min-height 40px. Semua records diambil tanpa pagination; halaman akan memanjang seiring data.  
Priority: P2 density/long-list; P3 target/konsistensi.  
Recommended direction: satu boundary per pengajuan, status dan satu action jelas, metadata pendukung ringkas; pertahankan seluruh records yang tersedia. Pagination/backend query bukan perubahan UI-only otomatis—jika kelak perlu, scope-kan sebagai pekerjaan terpisah.  
Elements to preserve: filter aktual, public ID singkat, tanggal/status, resume action, empty states.  
Desktop regression risk: sedang; card CSS shared dan override akhir file perlu ditelusuri.

### C06 — Workspace: hierarchy pertama kali dibuka

Screen: /app/applications/{publicId}, Perseorangan dan Badan.  
Current structure: header service/ID/status/estimate, mobile progress, next action; kemudian main + aside. Main memuat data/dokumen/pembayaran/proses/hasil. Aside memuat summary, help, settings/cancellation.  
What works: next action sudah di header; ID dapat disalin; progress mobile sudah ringkas; anchor CTA menjaga hubungan dengan bagian yang dituju; baca-saja mengikuti status.  
Problems: SOURCE CONFIRMED, phase-b.css:2934 memberi order:-1 pada seluruh aside di mobile. Akibat urutan deklaratifnya, summary berulang, bantuan dan pembatalan hadir sebelum form/dokumen yang menjadi tugas utama. Tidak ada section selector aktif meskipun CSS/JS lamanya masih ada. Context title header disembunyikan pada ≤430px; pengguna kehilangan identitas pengajuan setelah scroll panjang.  
Priority: P2. CTA anchor di atas masih memberi jalan ke tugas, sehingga tidak dinaikkan menjadi P1 tanpa bukti penggunaan.  
Recommended direction: header konteks ringkas → tahap/next action → bagian yang membutuhkan tindakan → informasi tahap lain → summary/history/help/settings yang lebih tenang. Pisahkan isi aside berdasarkan kepentingannya; jangan sekadar membalik posisi seluruh sidebar. Disclosure cocok untuk detail summary/history, bukan mandatory field/error.  
Elements to preserve: status presenter, label tahap, next-action semantics, service identity, ID copy, estimate, private locking.  
Desktop regression risk: tinggi; main/aside markup dipakai desktop. Responsive CSS order saja juga berisiko membuat urutan baca/focus berbeda; restrukturisasi semantik perlu direncanakan sebelum coding.

Hierarchy yang direkomendasikan: status dan tugas saat ini primer; data/dokumen relevan sekunder; referensi lengkap/riwayat pendukung; bantuan sekunder; pembatalan destructive terpisah.

### C07 — Workspace: form data dan validasi

Screen: Data & dokumen di workspace.  
Current structure: Livewire form berdasarkan service; kelompok data pribadi/badan/penanggung jawab/perwakilan; save on blur plus explicit save; state terkunci menampilkan ringkasan baca-saja.  
What works: field order mengikuti domain, dua kolom menjadi stack, native select/radio/input, NIK/KK memakai intent numerik, draft berbeda dari readiness submit.  
Problems: indikator save berada di awal form, mudah jauh dari field terakhir; local error belum konsisten diasosiasikan via aria-describedby/aria-invalid; representative opsional tetap menambah panjang. Error dari submit/upload di feedback global bisa jauh dari sumber masalah.  
Priority: P2.  
Recommended direction: kelompok yang jelas dan ritme field konsisten; saving/saved/error dekat kelompok yang sedang diedit; optional representative dapat disclosure sambil mempertahankan mounted state, values dan error. Jangan menghilangkan field karena belum wajib pada draft; readiness tetap service authoritative.  
Elements to preserve: blur-save + tombol simpan, encryption/masking, required/conditional rules, locks, native controls, explicit field labels.  
Desktop regression risk: tinggi pada shared Livewire form; jangan mengubah wire bindings, validation, query, atau lifecycle hooks untuk layout.

### C08 — Workspace: document upload, replace, view, delete, foto wajah

Screen: dokumen persyaratan dan foto wajah.  
Current structure: tiap requirement punya card dengan ikon/status/instruksi, versi, upload/action area; foto wajah punya panel tambahan dan camera/upload UI.  
What works: requirement snapshot dan batas file tersedia; link private view/download dipertahankan; filename text yang ada memakai wrapping; layout dokumen turun satu kolom; camera memiliki file-picker fallback dan berhenti pada capture/pagehide.  
Problems: nested boundaries dan ikon besar tetap menghabiskan ruang ketika file sudah tersedia; versi/status berulang. Nama file aktual tidak menjadi identitas utama card yang telah terunggah. Auto-submit setelah memilih file tidak mempunyai feedback busy/error lokal yang setara kedekatannya dengan card. Tombol upload memakai min-height 42px pada aturan terkait. Camera capture langsung menuju submit tanpa affordance batal kamera yang jelas. Head foto/status perlu diuji dengan label panjang; bukan klaim overflow terukur.  
Priority: P2 untuk feedback/action density; P3 ukuran target kecil dan pengurangan dekorasi.  
Recommended direction: requirement title + kewajiban/status primer; file/current version dan tindakan relevan dekat situ; requirement detail sekunder. Bedakan state kosong dari terunggah agar tidak terus seperti dropzone. Pisahkan view/replace dari delete secara spasial; error/upload progress lokal. Camera boleh diberi kontrol batal sesi preview pada UI berikutnya tanpa menambah biometric, pemrosesan gambar, atau mengubah readiness.  
Elements to preserve: requirement rules, required/optional labels, scan/review status, version history, private routes, input accept/size rules, file picker fallback, permissions.  
Desktop regression risk: tinggi; personal-document-card dipakai juga Badan. Jangan gandakan form upload untuk mobile sehingga event/input/ID atau submission berjalan dua kali.

### C09 — Workspace: revisi, proses, hasil, selesai, pembatalan

Screen: conditional states workspace.  
Current structure: revision notices dan actions, timeline semua history, hasil conditional, cancellation native dialog.  
What works: hasil hanya muncul melalui gate yang benar; next action dapat menuju #hasil; chronology memiliki reason/timestamp; pembatalan dipisah dan meminta alasan; dialog memiliki batas tinggi serta scroll.  
Problems: history tumbuh tanpa batas dan hasil berada setelah area panjang; summary/status berulang. Payment success dan presenter archived mengarah ke #ringkasan, tetapi workspace tidak mempunyai id tersebut. Ini kegagalan target konteks, bukan broken route total.  
Priority: P2 untuk long-page/anchor; P3 repetisi.  
Recommended direction: ringkas “perubahan terakhir” lalu seluruh riwayat melalui disclosure; saat hasil tersedia, prioritaskan akses hasil tanpa mengubah order state; pastikan anchor existing benar-benar mempunyai target. Pertahankan cancellation sebagai dialog, bukan wajib sheet; jangan buat sticky destructive CTA.  
Elements to preserve: alasan pembatalan, server-error reopening, read-only archived/cancelled, history lengkap, hasil verified, next action authoritative.  
Desktop regression risk: tinggi untuk perubahan urutan workspace; sedang untuk target anchor, tetap diuji tanpa mengubah desktop appearance.

### C10 — Pembayaran

Screen: /app/bayar/{publicId}, semua state.  
Current structure: order summary lebih dulu, kemudian status dan action; BCA/BRI/QRIS radio; waiting berisi VA/QR/checkout link, expiry dan refresh; success/failure/refund/cancelled branch.  
What works: harga/snapshot terlihat; label radio memberi tap area luas; metode menjadi satu kolom; VA membungkus dan dapat disalin; QR memakai width min(260px,100%); provider confirmation dibedakan dari return browser; payment dan application status tidak disamakan.  
Problems: status sebagai heading sekaligus badge mengulang informasi; full summary mendahului instruksi pada waiting. QRIS hanya menyediakan konteks pindai—handoff menggunakan phone yang sama belum dibuktikan nyaman. Menyalin VA dan berpindah ke app pembayaran perlu context return yang jelas. Success #ringkasan tidak mempunyai target (C09). Expiry dan instruksi bisa jauh dari nominal pada panjang tertentu.  
Priority: P2 untuk hierarchy waiting/same-device handoff yang perlu QA; P3 repetisi.  
Recommended direction: selection menonjolkan layanan + nominal sebelum metode; waiting menonjolkan status + nominal + instruksi/expiry/copy; summary referensi sekunder. Uji same-device QRIS dengan perilaku payment app sebenarnya sebelum memilih affordance simpan/buka yang sesuai—jangan menganggap seluruh bank mendukung skema yang sama. Refresh tetap memeriksa status authoritative; tidak menambah “sudah bayar” manual.  
Elements to preserve: metode aktif/disabled, snapshot, provider truth, expiry, QR unavailable feedback, retry eligibility, refund/cancellation semantics, external link protections.  
Desktop regression risk: tinggi pada template stateful yang sama; perubahan conditional payment dilarang untuk kebutuhan tampilan.

### C11 — Pusat Bantuan, FAQ, bantuan pengajuan dan arsip chat

Screen: /qna sebagai Client dan guest.  
Current structure: heading/search/category, FAQ utama; sidebar daftar pengajuan, percakapan aktif/arsip, kontak umum.  
What works: native details, search hasil dan empty state, category state, bantuan yang membawa context pengajuan, link semua pengajuan bila daftar ringkas terpotong.  
Problems: SOURCE CONFIRMED, phase-b.css:6785–6809 membuat sidebar display:contents, applications order 1, FAQ order 2, general order 3; conversations tidak punya order sehingga default0. Client membaca percakapan, lalu pengajuan, baru hasil FAQ di bawah search yang berada di atas semuanya. Ini memutus hubungan search-result. List percakapan tidak menampilkan unread marker; bottom nav juga tidak memilikinya, walaupun toast global tetap ada. Daftar helpThreads dibatasi enam tanpa jalur “semua percakapan” yang sepadan; akses older general threads perlu diperiksa pada data banyak.  
Priority: P2.  
Recommended direction: search/category langsung berdekatan dengan FAQ results. Pilihan “bantuan pengajuan/percakapan” boleh berupa jalur ringkas atau disclosure terpisah pada page yang sama; jangan campurkan dua maksud sebagai stack tanpa hierarchy. Sediakan persistent unread representation dari sumber yang sudah authoritative, bukan polling kedua. Keterjangkauan arsip lebih dari enam adalah batas data/navigation; jika membutuhkan perubahan retrieval, scope-kan terpisah, jangan hilangkan record.  
Elements to preserve: FAQ content, native disclosure, application context, thread reuse, archive semantics, guest login boundary.  
Desktop regression risk: tinggi karena CSS order/display:contents juga memengaruhi urutan baca; jangan sekadar menambah order tanpa memeriksa DOM focus order.

### C12 — Chat Client

Screen: /app/chat/{publicId}, pengajuan dan bantuan umum.  
Current structure: back/context page, header thread, message scroller, typing, composer, action edit/delete, thread archive controls.  
What works: text-only, read label, date grouping, tombstone deletion, live states, follow-scroll hanya ketika dekat bawah; reduced-motion diperhatikan; form tidak menambah attachment/audio.  
Problems: app.css memiliki aturan dvh modern tetapi phase-b.css yang dimuat sesudahnya masih mengoverride .bd-chat-card pada mobile dengan height calc(100vh - 285px) dan min-height 420px. Tinggi minimum panel + shell/header/bottom nav berpotensi memaksa page scroll ketika keyboard muncul. Ini RENDER RISK, bukan bukti composer tertutup pada perangkat. Header/context berulang; textarea 14px, controls edit/delete/archive sebagian 38–40px. Message menu dan delete overlay perlu focus/scroll QA.  
Priority: P2.  
Recommended direction: satu pemilik aturan tinggi chat per shell; composer tetap di alur panel, message list satu scroll region utama; adaptasi keyboard/safe area diuji nyata. Hindari sticky CTA tambahan. Gunakan context thread secukupnya dan pertahankan jalur kembali ke workspace/Bantuan.  
Elements to preserve: polling/read/typing semantics, send/edit/delete constraints, IME guard, near-bottom behavior, reduced motion, archive rules.  
Desktop regression risk: sangat tinggi karena app.css, phase-b.css, shared view dan JS melayani Client/Admin sekaligus.

### C13 — Ancillary email, framework error dan fake checkout

Screen: email transaksi/auth; error framework; checkout fake lokal.  
Current structure: email HTML shell 600px yang menjadi fluid pada media mobile; alternative plain text; default framework errors; fake checkout memakai layout generik.  
What works: email body readable dan CTA jelas dalam source; OTP sederhana; fake route terbatasi environment/driver.  
Problems: dua kolom context email dan nilai panjang perlu uji inbox 320px; framework errors belum memiliki bukti return-context yang nyaman; nav generik fake checkout berisiko nowrap pada phone.  
Priority: P3, dengan status NOT VERIFIED; bukan prioritas produksi mengalahkan workspace/Admin.  
Recommended direction: uji email nyata memakai data sintetis saat fase QA berikutnya; jangan edit delivery/security. Evaluasi error mobile tanpa mengekspos detail internal. Fake checkout hanya regression lokal, bukan redesign payment produksi.  
Elements to preserve: plain-text email, secure links, non-disclosure errors, environment gating.  
Desktop regression risk: rendah–sedang, tetapi email memerlukan QA client email tersendiri.

## E. ADMIN MOBILE FINDINGS

### A01 — Auth Admin

Screen: /admin/login dan /admin/otp.  
Current structure: admin-auth shell, login form atau satu field OTP.  
What works: role identity terpisah, one-time-code pada OTP, resend button memadai, flow keamanan tidak dibuat berbeda demi mobile.  
Problems: posisi feedback/keyboard dan panjang pesan belum teruji; konsistensi association error perlu disejajarkan dengan form terbaik yang sudah ada.  
Priority: P3, P2 hanya bila error/keyboard QA menunjukkan hambatan nyata.  
Recommended direction: preserve single task, ukur 320px dan keyboard; shared standard error/target tanpa menyatukan branding role secara paksa.  
Elements to preserve: authentication/OTP/throttle/session regeneration dan role boundary.  
Desktop regression risk: sedang, branch auth shared.

### A02 — Shell, header dan drawer Admin

Screen: seluruh route /admin setelah login.  
Current structure: fixed sidebar desktop 232px dengan enam tujuan; offcanvas ≤1023px; header mobile dengan tombol menu, logo/avatar; context/identity text disembunyikan ≤767px.  
What works: struktur enam destinasi nyata cocok dengan drawer; active state, backdrop, Escape, return focus dan focus trap saat terbuka sudah ditulis; tombol menu/close/logout cukup besar.  
Problems: closed drawer hanya ditransform keluar viewport, tidak diberi inert/hidden sehingga link offscreen berisiko tetap menerima keyboard focus. Sidebar fixed tidak menyediakan overflow-y:auto untuk tinggi pendek; footer/logout berisiko tak terjangkau dengan landscape/text zoom. Context route/role tidak lagi terbaca saat isi page discroll.  
Priority: P2 untuk focus/height; P3 context ringkas.  
Recommended direction: pertahankan drawer; sempurnakan closed-state accessibility, internal scroll dan pemulihan focus. Header cukup identitas area aktif singkat; jangan menambah enam-item bottom nav.  
Elements to preserve: menu structure, role badge/identity, current-route state, logout POST, existing JS keyboard support.  
Desktop regression risk: tinggi pada breakpoint 1023/1024; inert/hidden state tidak boleh menonaktifkan sidebar desktop.

### A03 — Dashboard Admin

Screen: /admin/dashboard.  
Current structure: operational attention metrics, chart aktivitas, updates, kemudian priority queue table.  
What works: angka terkait pekerjaan nyata, link ke antrean, recent updates, chart tidak dibuat dari dummy decorative data.  
Problems: pekerjaan prioritas berada setelah grafik/update; table tetap900px. Chart SVG dengan viewBox lebar menyusut bersama labelnya pada phone, sehingga label berisiko terlalu kecil.  
Priority: P2.  
Recommended direction: perhatian mendesak → antrean ringkas untuk tindakan → updates → chart sebagai secondary disclosure/section. Jangan membuang chart atau mengganti data; pertahankan akses detail.  
Elements to preserve: metric meanings, query ranges, actionable counts, status colors.  
Desktop regression risk: tinggi jika shared order/grid diubah global; hanya susunan phone yang direkomendasikan.

### A04 — Pengajuan: antrean, search/filter, pagination

Screen: /admin/applications.  
Current structure: toolbar/search, delapan kategori filter, table identitas–klien–layanan–status–updated–Tinjau, pagination.  
What works: search luas, filter dan pagination tersimpan pada URL/Livewire, loading/aria-busy, empty state sesuai kondisi.  
Problems: SOURCE CONFIRMED, table min-width 900px; identitas record di kiri, action di kanan. Pada phone, admin perlu bergerak horizontal sambil menjaga row context. Ini technically responsive tetapi tidak operasional nyaman. Filter38px dan search 42px/font 13px juga perlu ergonomi mobile.  
Priority: P1 untuk representasi antrean; P2 target/input.  
Recommended direction: mobile operational row ringkas dengan client/service/status/next action dan tombol detail dalam satu unit; secondary ID/time/email dapat disclosure/wrap. Jangan menyembunyikan status/identitas untuk mengejar kerapian. Pertahankan dataset, sort, filter, authorization dan pagination.  
Elements to preserve: prioritas aktual, next-action label, search q/filter URL, loading, page state.  
Desktop regression risk: tinggi; table desktop harus tetap. Hindari dua daftar Livewire terpisah yang menjalankan query/pagination ganda.

### A05 — Detail pengajuan dan action stack

Screen: /admin/applications/{publicId}.  
Current structure: summary/data/documents/payment/result/history di main; action stack + support di aside; mobile satu kolom menempatkan aside setelah seluruh main.  
What works: data masked, status dan next-action explanation, form tindakan bersyarat, informasi payment readonly, audit history/reasons tersedia.  
Problems: P1 controls begin/finalize review, estimasi, upload hasil dan proses berikutnya berada sangat jauh setelah main. P2 tambahan: app.css:3284–3289 mengatur order section tetapi history pada view:106 hanya memakai bd-admin-surface, tanpa modifier history; default order 0 mendahului data/dokumen yang order 1/2. Ini source-confirmed mismatch, bukan sekadar dugaan class.  
Priority: P1 untuk action reachability; P2 untuk hierarchy history.  
Recommended direction: ringkas konteks dan safe next action di awal, lalu bukti yang relevan untuk tindakan; finalisasi dekat kumpulan keputusan dokumen. Ringkasan payment/data/history sekunder dapat disclosure. Jangan sticky-kan keputusan final sebelum bukti dibaca; sticky “ke tindakan/review” boleh dipertimbangkan jika pengujian menunjukkan scroll panjang tetap perlu.  
Elements to preserve: assignment/locking, review-after-payment, legal transitions, audit/reasons, verification completion gate, history lengkap.  
Desktop regression risk: sangat tinggi; perubahan DOM/order/form grouping berpotensi mengubah desktop dan binding tindakan. Penanganan mismatch history untuk mobile tidak memberi izin memperbaiki layout desktop global.

### A06 — Review dokumen, file viewer dan hasil

Screen: dokumen/hasil embedded pada detail, private stream endpoints.  
Current structure: document card berlapis, thumbnail/PDF placeholder, metadata, private view/download, collapsed decision form dan version history; hasil upload/verify terpisah.  
What works: keputusan berdekatan dengan dokumen; disclosure review mengurangi kebisingan; file tetap private; preview gambar tidak berpura-pura menggantikan dokumen asli.  
Problems: link view/download dan summary review tidak menjamin target44px; preview 174px dalam nested padding terlalu kecil untuk membaca detail dokumen dan memang memerlukan full view. Form keputusan tidak mengembalikan old values secara konsisten setelah validation redirect; feedback global jauh dari card. Reference/filename/status perlu wrapping.  
Priority: P2.  
Recommended direction: title/state → preview ringkas → “buka bukti” dengan target besar → keputusan lokal; pertahankan full view native/new tab dan lokasi kembali ke card. Tampilkan error lokal, pertahankan entered values, jangan menutup disclosure berisi error. Pastikan user memahami thumbnail bukan full-quality inspection.  
Elements to preserve: policy, no public permanent URL, scan/review state, version history, result verification, reason/instruction requirements.  
Desktop regression risk: tinggi pada detail shared. CSP object-src none dan frame-ancestors/X-Frame protections tidak boleh dilemahkan untuk membuat embedded iframe/PDF viewer.

### A07 — Antrean Dokumen

Screen: /admin/documents.  
Current structure: search/filter dan table per pengajuan dengan readiness/review counts serta link ke area dokumen.  
What works: pengelompokan menurut pengajuan, angka aktual dan direct context ke review.  
Problems: table 900px memakai solusi sama dengan antrean umum, padahal tugasnya memindai kelengkapan/masalah dan membuka dokumen.  
Priority: P1 untuk operability antrean.  
Recommended direction: grouped operational row per application: identitas → kelengkapan/ditolak/perlu review → tindakan dokumen. Rincian counts tetap dapat dibuka, bukan dihapus. Ini bukan file gallery atau thumbnail feed.  
Elements to preserve: calculation/readiness rules, search/filter/page, link ke application/anchor dan private gates.  
Desktop regression risk: tinggi, pertahankan table desktop dan data provider.

### A08 — Dukungan / inbox Admin

Screen: /admin/support.  
Current structure: search, jenis/unread/archived filters, row list avatar/client/context/snippet/time/unread, archive menu, pagination.  
What works: list sudah jauh lebih cocok phone daripada table; unread jelas; context pengajuan vs umum tersedia; row besar mudah dibuka; archive guarded.  
Problems: row punya beberapa metadata kanan dan archive menu absolute; potensi collision pada320px perlu render. Menu trigger 44px tetapi item 34px; filter 38px; snippet/email ellipsis bisa menyamarkan beda percakapan. Back link dari thread kembali ke inbox dasar, tidak memulihkan q/filter/page melalui link tersebut; browser Back memiliki perilaku berbeda dan perlu diuji.  
Priority: P2.  
Recommended direction: nama/context + unread primer; snippet/time sekunder; menu mendapat ruang sendiri. Pelihara context daftar saat kembali tanpa menambah sort/business rule baru. Jangan mengasumsikan “unread-first” jika query source mengurutkan last_message_at.  
Elements to preserve: current query semantics, archive/unarchive gate, unread counts, polling notifier, pagination.  
Desktop regression risk: sedang–tinggi pada row/grid dan popup positioning.

### A09 — Chat Admin

Screen: /admin/chat/{publicId}.  
Current structure: shared thread, admin wrapper, quick reply dropdown, composer, edit/delete/archive.  
What works: jawaban cepat mengisi composer, tidak mengirim otomatis; application context link tersedia; read/typing/text-only semantics sudah jelas.  
Problems: tinggi clamp minimum 420px masih dapat bersaing dengan keyboard/shell pada layar pendek; nested outer surface menambah padding; quick reply select sekitar12px/38px; edit/delete overlay focus/height tidak lengkap.  
Priority: P2, keyboard obstruction belum terbukti sehingga bukan P0/P1.  
Recommended direction: ringkas shell chat, satu scroller pesan, composer terjangkau saat keyboard; quick replies ergonomis dan tetap optional. Normalisasi modal focus handling dengan Client tanpa mengubah ChatThread business rules.  
Elements to preserve: polling, read state, typing TTL, send/edit/delete constraints, application link, no attachment/audio/WebSocket.  
Desktop regression risk: sangat tinggi karena shared view/JS/cascade; uji kedua role pada setiap perubahan.

### A10 — Pengguna dan detail pengguna

Screen: /admin/users dan /admin/users/{publicId}.  
Current structure: searchable/filterable directory table; detail readonly dengan metadata dan pengajuan paginated10.  
What works: tidak menambah admin edit-PII atau role management; detail memakai compact rows, bukan tabel lagi.  
Problems: directory900px tidak cocok untuk lookup sederhana; compact row detail memakai ellipsis pada informasi yang bersaing dengan badge sehingga layanan/identitas berisiko kurang jelas di320px.  
Priority: P2.  
Recommended direction: direktori mobile berupa row identitas/contact context/status + detail link; detail memprioritaskan nama dan pengajuan, metadata lain sekunder. Wrap nama/layanan penting, ellipsis hanya snippet yang dapat dibuka.  
Elements to preserve: masking, readonly permissions, search/filter/page, application authorization.  
Desktop regression risk: sedang–tinggi, compact rows digunakan area Admin lain.

### A11 — Aktivitas, filters, empty/loading/pagination

Screen: /admin/activity dan shared states seluruh listing.  
Current structure: curated operational event table dengan filters/search/pagination; loading overlay and aria-busy pada reactive lists.  
What works: ini feed operasional terpilih, bukan dump seluruh audit sensitif; empty state kontekstual; Livewire pagination mempunyai tampilan previous/next mobile bawaan.  
Problems: table 900px tidak membantu membaca urutan event pada phone; timestamp/metadata11–11.5px padat. Paginator default dapat scroll ke body; pengguna kehilangan konteks list setelah pindah halaman. Exact rendered size/translation/scroll belum diverifikasi.  
Priority: P2 feed representation; P3 pagination polish.  
Recommended direction: event chronology ringkas dengan actor/context/action/time yang tersedia; retain detail fields melalui disclosure; jangan menghilangkan evidence. Pertahankan pagination, arahkan perhatian kembali ke heading hasil dengan cara aksesibel bila dibutuhkan. Jangan edit vendor.  
Elements to preserve: event scope/privacy, chronology, filters, loading feedback, result count.  
Desktop regression risk: sedang–tinggi pada shared table/pagination CSS; jangan mengganti semua tabel dengan solusi universal.

## F. CROSS-CUTTING FINDINGS

### F01 — Navigation dan context preservation

Client memiliki empat tujuan utama yang cocok dengan bottom nav existing: Beranda, Layanan, Pengajuan, Bantuan. Ini bukan rekomendasi menambah bottom nav baru. Header menyediakan identitas/account; bottom nav mempertahankan route active state. Masalahnya bukan kekurangan destinasi, melainkan context pengajuan hilang saat scroll dan unread hanya persisten di nav desktop, bukan mobile.

Admin memiliki enam area kerja; drawer existing lebih masuk akal daripada memaksa enam item bottom nav. Perbaiki lifecycle focus, closed state dan scroll tinggi pendek. Page title tetap perlu terbaca pada awal halaman; header ringkas membantu orientasi saat sudah jauh dari judul.

Public navigation berbeda: section anchors + login/register. Pertahankan menu publik, jangan menyalin shell authenticated ke landing.

### F02 — Information hierarchy dan metadata

Aturan konseptual per tipe screen:

| Screen type | Primary information/action | Secondary | Supporting metadata | Destructive |
| --- | --- | --- | --- | --- |
| Client dashboard | Pengajuan yang memerlukan tindakan; lanjutkan | Pengajuan aktif lain | Pembaruan, shortcut layanan | Tidak ditonjolkan |
| Client workspace aktif | Status/tahap dan tugas sekarang | Data/dokumen/payment terkait tugas | ID lengkap, summary, history, help | Cancellation terpisah |
| Workspace selesai | Status selesai dan akses hasil yang diizinkan | Ringkasan proses | Riwayat, referensi | Tidak diberi prominence baru |
| Payment selection | Nominal, layanan, pilih metode/buat instruksi | Cara pembayaran | Reference/status detail | Tidak ada manual paid |
| Payment waiting | Instruksi aktif, nominal, expiry, refresh | Referensi pengajuan | Penjelasan pendukung | Cancellation mengikuti workspace |
| Admin queue | Identitas record, status, action detail | Readiness/next action | Email/time/reference | Bukan bulk destructive |
| Admin detail/review | Status operasional, bukti dan tindakan yang diizinkan | Data/payment untuk keputusan | History/version/reference | Keputusan final tetap deliberate |
| Support inbox | Thread identity/context/unread; buka thread | Snippet/time | Type/archive metadata | Archive dalam menu terpisah |
| Chat | Pesan relevan dan composer | Thread/application context | Time/read/typing | Delete eksplisit terpisah |

Jangan menjadikan status, ID, timestamp, kicker, heading, badge, dan CTA sama kuatnya. Jangan menghapus informasi; ubah prioritas dan kedalaman pengungkapan.

### F03 — Typography

Family Inter dan tone visual existing dipertahankan. Client form input 16px/46px merupakan pola baik; metadata/helper 11–12px, nav 10px pada ≤370px, Admin table 11–13px dan search 13px perlu perhatian. Mengecilkan judul atau body secara universal tidak menyelesaikan nested padding/table width.

Future direction: judul phone operasional cukup sekitar22–26px sesuai konteks; body tugas15–16px; metadata12–13px bila nonkritis; label status kritis jangan dibuat sekecil metadata. Angka ini arah desain, bukan token baru atau klaim semua teks harus sama. Gunakan wrapping dan line-height sebelum menurunkan ukuran. Judul marketing boleh lebih ekspresif daripada label antrean Admin.

### F04 — Gutter dan spacing

Client phone ≤430px memakai gutter14px; shell tablet/mobile lebih besar memakai16px. Admin memakai16px. Feedback Client masih mempunyai lebar calc(100% - 48px), sehingga pesan dapat berindentasi lebih dalam daripada page. Workspace/listing/nested document mengakumulasi padding; page bottom padding ditambah main reserve bottom nav berpotensi menyisakan tail whitespace.

Rekomendasi: satu outer gutter per shell, inner spacing berdasarkan kedekatan informasi; jangan menerapkan padding panel berulang di setiap lapisan. Periksa safe-area reserve sebelum menambah margin bawah. Ketidaksamaan 14 dan16px sendiri adalah P3, bukan bug workflow.

### F05 — Containers

Meaningful container: satu pengajuan, satu file requirement beserta tindakan, satu payment instruction, satu dialog keputusan. Grouping container: daftar dalam section, fieldset, timeline. Decorative/redundant container: panel luar ditambah card daftar berpadding besar, frame preview di dalam frame dokumen di dalam surface besar, status box berulang tanpa informasi baru.

Pertahankan boundary yang menyatakan ownership atau keputusan. Hilangkan hanya lapisan yang tidak menambah makna; jangan flatten data, file, dan destructive action menjadi satu aliran tanpa pemisah.

### F06 — Forms dan keyboard

Form harus menjaga urutan field domain, input names, old values, wire bindings, required/conditional rules, dan authorization. Standarkan hubungan label → control → helper → error; local error jangan hanya terlihat pada alert di atas halaman panjang. Saat disclosure mengandung error, buka dan arahkan focus secara wajar.

Input/select/textarea penting perlu target nyaman dan ukuran teks yang tidak mengundang zoom otomatis pada browser tertentu. Admin search/quick-reply/review controls lebih kecil daripada Client; ini kandidat penyamaan ergonomi, bukan penambahan CSS framework.

Checkbox/radio kecil boleh tetap visual kecil jika label hit area cukup besar, sebagaimana payment radio existing. Native date/file/select behavior perlu diuji pada device; jangan ganti dengan custom picker tanpa bukti.

### F07 — Documents dan file-picker ergonomics

Kelompokkan requirement, current file/version, scan/review state, dan action. Preserve status sebagai teks, bukan warna saja. Setelah file dipilih/diunggah, user perlu tahu card mana yang sedang bekerja, gagal, atau selesai. File error jauh di global feedback membuat recovery mahal di halaman panjang.

View/download, replace, dan delete tidak perlu semua berupa primary button. File viewing tetap native/private. Jangan mempublikasikan storage, membuat permanent URL, memasukkan dokumen ke log, atau menurunkan CSP agar preview terlihat lebih rapi.

### F08 — Buttons dan CTA

Existing primary Client umumnya min44px, input 46px. Pengecualian yang ditemukan: application-card footer40px, document-upload42px, chat controls38–40px, Admin filters38px, search 42px, archive menu item 34px, beberapa document/action links tanpa minimum touch target.

Bukan semua button kurang44px otomatis P1. Prioritaskan kontrol yang sering ditekan berdekatan atau berisiko salah aksi. Full-width tepat untuk submit form sempit, tetapi tidak untuk semua view/download/help/link pada setiap card. Satu primary action per konteks keputusan; destructive terpisah warna DAN posisi/konfirmasi.

Sticky behavior tidak default. Kandidat terbatas: context/link “kembali ke tindakan” pada detail panjang, atau submission action saat seluruh readiness telah jelas. Jangan membuat auto-finalize, sticky delete, sticky payment confirmation, atau bar kedua yang bertabrakan dengan bottom nav/composer.

### F09 — Status, progress dan workflow

application-progress.blade.php sudah membedakan desktop six-step list dan mobile “Tahap N dari 6”, stage label, bar. Ini KEEP. Tidak perlu menggantinya dengan stepper horizontal baru pada320px.

Perbaikan cukup menyatukan visual weight antara status dan next action, mengurangi label status identik berulang, serta memastikan archived/cancelled tidak ditafsirkan sebagai progres aktif. Angka tahap bukan estimasi persentase pekerjaan atau janji waktu. Application status, payment status, scan status, document review dan result verification berbeda domain; jangan digabung menjadi satu badge generik.

### F10 — Table/data-dense treatment per surface

| Table aktif | Existing | Future mobile treatment | Informasi yang wajib tetap tersedia | Mengapa bukan solusi universal |
| --- | --- | --- | --- | --- |
| Admin dashboard priority | Table900px | Compact action rows | Identity, status, next action, update | Tujuannya memilih pekerjaan, bukan membandingkan kolom |
| Admin application queue | Table900px | Responsive operational rows/hybrid detail | Client/service/ID/status/action; email/time secondary | Identitas dan Tinjau harus terlihat bersama |
| Admin document queue | Table900px | Grouped application readiness row | Requirement/review counts dan link dokumen | File list per pengajuan, bukan gallery |
| Admin user directory | Table900px | Lean lookup rows | Name/email/account metadata/detail | Lookup identity, bukan spreadsheet |
| Admin activity feed | Table900px | Chronological event entries | Actor/action/context/time yang ada | Urutan kejadian lebih penting dari kolom sejajar |

Localized horizontal scroll tetap opsi sah untuk data yang benar-benar perlu perbandingan banyak kolom atau optional “lihat tabel lengkap”. Jangan jadikan default untuk lima surface ini hanya karena sudah tidak meluber ke body. Jangan mengecilkan tabel 900px dengan transform/zoom.

### F11 — Dialogs, menus dan overlays

| Surface | Keputusan pola | Alasan / verifikasi berikutnya |
| --- | --- | --- |
| Pembatalan Client | KEEP native modal, REFINE only | Sudah fluid, bounded height, scroll internal, Escape/native modal semantics; uji keyboard dan error panjang |
| Delete message Client/Admin | KEEP confirmation concept, RESTRUCTURE mechanics | role dialog/aria-modal dan initial focus ada, tetapi belum ada trap/inert/restoration setara native dialog; absolute overlay tidak mempunyai batas internal yang memadai untuk semua keyboard heights |
| Client account details | KEEP menu | Bukan form panjang; uji email panjang, short height, focus dan dismissal |
| Admin sidebar | KEEP drawer | Enam destinations, perbaiki closed state/scroll; bukan sheet untuk setiap page |
| Admin document review/version | KEEP inline details | Mempertahankan hubungan bukti-keputusan; error harus terlihat ketika form gagal |
| Support archive/message action menu | KEEP contextual menu | Secondary/destructive action; perbaiki target, viewport-edge positioning, focus |
| Upload/file viewer | KEEP inline upload + private native viewer | Tidak ada upload dialog/viewer app yang perlu direkayasa sebagai modal baru |

Tidak ada alasan umum memindahkan semua dialog menjadi bottom sheet atau layar baru.

### F12 — Chat dan long conversations

Sumber utama: livewire/chat-thread.blade.php, app/Livewire/ChatThread.php, resources/js/app.js, app.css dan phase-b.css. Polling5 detik, typing/read state, text-only, tidak ada attachment/audio/WebSocket adalah kontrak yang dipertahankan.

JS menjaga scroll saat dekat bawah dan tidak otomatis mengikuti ketika user membaca pesan lama. Namun scrollIntoView pada sentinel dapat ikut menggeser ancestor page; tidak ada bukti real-device keyboard/visual viewport berhasil ditangani. Tidak ada indikator “pesan baru di bawah” yang setara persistent jump saat membaca lama. Pengambilan seluruh messages juga membuat thread panjang; mengubah pagination/query chat perlu scope terpisah dari styling.

Future QA wajib: incoming saat membaca lama, kirim pesan saat keyboard aktif, edit textarea panjang, delete confirm, archive/read state, quick reply, back ke filtered inbox, jaringan lambat/reconnect. Jangan mengubah interval polling atau read rules demi menyembunyikan layout jitter.

### F13 — Responsive overflow register

Tidak ada page-level overflow yang terukur karena browser tidak tersedia. Berikut meaningful risk register, bukan daftar kegagalan visual yang dikarang:

| Risk / evidence | Surface | Status / severity | Arah pemeriksaan/koreksi |
| --- | --- | --- | --- |
| min-width 900px | Lima tabel Admin | SOURCE CONFIRMED local scroll; P1/P2 menurut tugas | Record-action proximity, bukan global overflow hidden |
| .phase-b overflow-x:hidden, phase-b.css:268 | Client/public/auth | SOURCE CONFIRMED masking; P2 bila clipping terbukti | Ukur scrollWidth/rect tiap anak; jangan menganggap hidden berarti fit |
| Nested panel/card padding | Listing, file/face, Admin docs | SOURCE CONFIRMED structure; P2 density | Lebar efektif pada320, long labels/errors |
| Long UUID/reference | Workspace, payment, Admin detail | Sebagian dipendekkan/wrap; remaining RENDER RISK P2/P3 | Full reference harus wrap/copy, bukan dipotong tanpa akses |
| Long email/name | Account menu, Admin search rows, support | Beberapa min-width 0/ellipsis ada; RENDER RISK | Bedakan essential identity dari snippet yang boleh ellipsis |
| Long filename/status/version | Documents/results/review | Sebagian overflow-wrap:anywhere; RENDER RISK | Jangan klaim semua filename overflow; test long synthetic data |
| Badge + title + action rows | Face header, support row, user compact row, chat header | RENDER RISK P2 | Wrap/allocate action space, bukan shrink text |
| Table/filter/tab scroller | Client filters, Admin filters | Local scroll intentional; P2 target/P3 cue | Active filter reachable, tidak menyebabkan body scroll |
| Fixed header/account menu/drawer | Client/Admin | RENDER RISK P2 | Short-height, safe area, text zoom, focus offscreen |
| Chat min-height + vh/dvh + bottom nav | Client/Admin chat | SOURCE CONFIRMED cascade; RENDER RISK P2 | Dynamic keyboard/viewport resize, one scroller |
| Delete overlay/message menu | Chat | SOURCE CONFIRMED constraints; RENDER RISK P2 | Bottom-edge/keyboard, internal scroll/focus |
| QR image | Payment | Fluid max width ada; KEEP | Test scannability, actual generated QR, same-device use |
| Desktop progress | Workspace | Hidden and replaced on phone; KEEP | Pastikan tidak dua versi terbaca, current label benar |
| Public menu/testimonial | Landing | RENDER RISK P3 | Height/menu focus, narrow quote column |
| Transactional email context table | Email | RENDER RISK P3 | Real inbox long values, bukan browser app screenshot |

### F14 — Mobile accessibility

Positif: skip links/main target, labels pada form, aria-current navigasi, text status selain warna, semantic links/buttons, register error association, payment label hit areas, native details, native cancellation dialog, loading live states, Escape/focus handling drawer terbuka.

Perlu diperbaiki: closed drawer keyboard focus; error association tidak konsisten; icon/action controls kecil; beberapa focus indicator tipis; menu/overlay focus containment; readable metadata; scroll/focus order berbeda karena CSS order. Mobile zoom tidak dinonaktifkan oleh viewport maximum-scale/user-scalable=no dalam layout yang diaudit.

Spot-check kontras dihitung dari pasangan warna token source memakai luminance sRGB, bukan screenshot/computed style:

| Pasangan token | Rasio hitung | Penilaian terbatas |
| --- | --- | --- |
| Client muted #526174 / putih | 6.32:1 | Tidak perlu mengganti palette demi teks muted standar |
| Putih / Client primary #0068fd | 4.77:1 | Fondasi button primary layak dipertahankan |
| Client success #176b45 / #eaf7f0 | 5.91:1 | Status tetap pakai label, bukan warna saja |
| Client warning #8a4b08 / #fff5df | 6.27:1 | Tidak ada alasan merombak token ini |
| Client danger #a62929 / #fff0f0 | 6.39:1 | Preserve semantic family |
| Admin muted #52617a / putih | 6.27:1 | Ukuran/density lebih mendesak daripada warna dasar |
| Client focus #ffb800 / putih | 1.73:1 | Ring kuning tipis pada surface putih perlu penguatan kontras/ketebalan, bukan mengganti brand |

Angka tidak mencakup opacity, overlay, image backgrounds, hover/disabled, seluruh badge, atau rendered anti-aliasing. Ini bukan WCAG certification dan tidak membuktikan seluruh aplikasi lolos kontras.

### F15 — Long-page behavior

Masalah nyata menurut struktur: aside workspace Client terlalu awal; Admin action stack terlalu akhir; history penuh dapat dominan; preflight/documents sangat panjang; chat memiliki inner scroll plus page scroll. Solusi bukan selalu sticky.

Gunakan urutan relevansi, recent-first summary dengan disclosure history lengkap, section target valid, dan context ringkas. Field/error aktif tidak boleh terlipat otomatis. Hilangkan redundant summary sebelum menambah jump navigation. Sticky baru dinilai setelah urutan diperbaiki dan panjang aktual diukur.

### F16 — Responsive architecture dan coupling

- Client/marketing/guest memuat app.css lalu phase-b.css; Admin memuat app.css. Selector .bd-* dan .pb-* serta override berulang membuat stylesheet terakhir menentukan hasil; chat adalah contoh konflik nyata.
- Breakpoint bukan satu sistem sederhana: Client shell 850, banyak phone rules600/430/370, documents760/560, Help820; Admin shell 1023, detail767, document grid1180/767. Public memiliki batas tambahan959/860/760/600/380 dan override lain.
- Code cenderung desktop grid base lalu max-width adaptation, dengan beberapa mobile-specific presentation yang sudah bagus. Jangan menyebut seluruh project “mobile-first” tanpa kualifikasi.
- Duplicate presentation yang dibenarkan saat ini: progress mobile/desktop, nav desktop/mobile dan FAQ jawaban mobile, dengan state/data sumber sama. Risiko: global notifier hanya ditempatkan pada desktop nav.
- Banyak halaman shared static wrapper tetapi actions stateful. Kandidat reuse adalah shell spacing, action row, error association, operational row conventions; bukan framework atau frontend kedua.
- Jangan membuat broad rewrite CSS, membersihkan legacy, memindah semua Blade, atau menambah dependency. Future patch harus component-scoped dan route-by-route.

## G. KEEP / REFINE / RESTRUCTURE

### KEEP

- Logo, warna navy/blue, Inter, icon/button/border/status language: identitas sudah koheren.
- Client bottom navigation empat tujuan: struktur sesuai produk; bukan pattern yang perlu diganti.
- Mobile progress tahap aktif: sudah mengurangi noise enam langkah.
- Dashboard priority + compact application lists: fokus tugas nyata.
- Form native, Client input 16px, labels, register error association: dasar ergonomi.
- Payment radio full label, VA copy, QR fluid, provider-confirmation distinction.
- Admin drawer dan enam area kerja: preserve struktur, bukan semua implementation detail.
- Admin support row list, unread indicator, search/filter URL dan loading.
- Private native document viewing, inline review/version disclosure.
- Native cancellation dialog, read-only states, privacy masking dan safe action boundaries.
- Chat text/read/typing/near-bottom semantics; quick reply tidak autosend.

### REFINE

- Gutter/spacing/heading dan metadata: samakan ritme tanpa menyamakan density role.
- Small action targets, error association, local feedback, focus ring.
- Document uploaded state: kurangi empty-state decoration dan repetition.
- Payment waiting hierarchy/expiry/reference, auth vertical rhythm.
- Account/drawer/menu short-height behavior dan returning context.
- Support row archive placement, pagination scroll context.
- Public testimonial padding/decorative columns; tidak perlu hero redesign.
- Existing status/progress visual weight dan anchor correctness.

### RESTRUCTURE

- Client workspace: pisahkan isi sidebar berdasarkan prioritas, bukan whole-aside-before-main.
- Client Help: FAQ search/results berdekatan; percakapan/pengajuan tidak memotong hasil search.
- Admin operational queues: mobile record/action presentation, bukan table 900px sebagai satu-satunya cara.
- Admin detail: tindakan dan bukti berdekatan; history tidak mendahului data karena default order.
- Shared chat height/scroll ownership dan delete-overlay mechanics.

“Restructure” berarti representasi mobile yang berbeda dalam arsitektur existing; bukan perubahan workflow, route, domain, maupun redesign produk dari nol.

## H. ANTI-SLOP FINDINGS

| Pattern | Where | Why problematic | Future correction direction |
| --- | --- | --- | --- |
| Cardification / nested cards | Client listing, document/face; Admin document preview | Boundary berulang menghabiskan lebar dan membuat semua kelompok sama kuat | Pertahankan file/record boundary, sederhanakan wrapper dekoratif |
| Repeated status chips | Workspace header + summary; payment title + badge | Informasi sama diulang tanpa konteks tambahan | Satu status primer, ringkasan sekunder tidak meniru hero |
| Excessive visual chrome | File icon/preview decoration, testimonial quote/accent | Tugas atau teks menyisakan ruang sedikit pada320px | Perkecil/hilangkan dekorasi yang redundan hanya pada narrow layout |
| Sidebar-as-long-stack | Client workspace dan Admin detail | Responsive teknis tidak menerjemahkan prioritas | Pecah informasi berdasarkan tujuan, bukan asal kolom desktop |
| Dense UI called “clean” | Table900px atau metadata yang diperkecil | Kerja nyata bergantung horizontal scan, target kecil | Susun ulang record/action dan disclosure, jangan transform-scale |
| Hero-like repetition pada workspace | Header konteks diikuti summary/status serupa | Konteks bermakna berubah menjadi beberapa blok pembuka | Preserve satu header identitas yang fungsional |
| Duplicate CTA prominence | Dashboard/list/workspace; landing beberapa section | Semua tombol biru membuat next action sulit dibedakan | Satu primary per keputusan; link sekunder sesuai konteks |
| Potential app mimicry | Risiko future redesign | Bottom nav Admin/sheet untuk semua hal tidak didukung kebutuhan | Pertahankan client/admin patterns berbeda yang sudah beralasan |

Tidak ditemukan dasar untuk menyebut seluruh produk penuh glassmorphism, gradient acak, atau dekorasi chart palsu. Navy/soft surfaces/shadow dialog yang menjelaskan layer bukan slop otomatis. Tidak direkomendasikan menghapus semua badges, cards, shadows atau ilustrasi.

## I. CLIENT MOBILE DESIGN STRATEGY

1. Shell: pertahankan header/account dan empat bottom destinations. Sediakan persistent unread cue melalui data notifier yang sudah ada; jangan memasang Livewire polling kedua. Context pengajuan/chat perlu ringkas tanpa menambah header tinggi.
2. Page hierarchy: setiap page menjawab “saya di mana, statusnya apa, apa yang perlu dilakukan” sebelum metadata pendukung. Public/auth/service pages tetap punya kebutuhan berbeda.
3. Workspace: service + status + tahap/next action → active task section → informasi relevan berikutnya → secondary summary/history/help/settings. Ini urutan representasi, tidak mengubah sequence domain. Saat status berubah, primary content mengikuti presenter, bukan heuristik frontend.
4. Forms: pertahankan service-specific groups, satu kolom, explicit labels dan native keyboard; local save/error context; optional section disclosure tidak mengubah validity atau persistence.
5. Documents: satu requirement unit; current state/file + action dekat; file requirement detail/version secondary. Foto wajah tetap kamera atau file picker, bukan fitur identitas baru.
6. Payment: nominal tetap jelas; pilih metode dan waiting instructions memakai hierarchy berbeda. QR/VA mendapat ruang yang memadai; status authoritative dan expiry selalu visible pada konteks pembayaran. Tidak ada manual paid.
7. Help/chat: pisahkan intention cari jawaban dari membuka percakapan melalui grouping yang jelas dalam route yang ada. Thread context dan back path tidak hilang. Composer dan pesan menjadi fokus screen chat.
8. Long pages: pendekkan opening stack dan repetition dahulu; disclosure untuk history/detail baca-saja; section anchors nyata; sticky hanya jika hasil ukur menunjukkan perlu.
9. CTA: satu action primer sesuai status; view/download/help sekunder; cancellation destructive terpisah. Jangan membuat semua link full width atau menggandakan form submit untuk sticky presentation.

## J. ADMIN MOBILE DESIGN STRATEGY

1. Shell/navigation: drawer enam area tetap. Perbaiki closed focus state dan scroll, role/context ringkas; jangan imitasi nav Client.
2. Dashboard: prioritaskan pekerjaan yang perlu ditangani; chart dan trend secondary. Metrics tetap actual dan dapat ditindaklanjuti.
3. Listings: search + filter yang dapat dijangkau, lalu records yang dapat dipahami tanpa pan horizontal rutin. Preserve URL/page/filter/query semantics.
4. Tables: pilih lima treatment spesifik di F10; desktop table tetap ada. Shared mobile style dapat digunakan, tetapi content model per queue berbeda.
5. Application detail: ringkas identity/status/next action, kemudian evidence/action yang terkait. History dan payment details tetap dapat diakses tanpa mendahului review utama.
6. Review: keputusan dokumen dekat bukti, finalization sesudah keputusan yang relevan; error membuka kembali bagian yang gagal. Tidak ada default tindakan baru, bypass assignment, atau automatic finalize.
7. Document viewing: private full view browser/new tab; konteks file/application dan return-to-card jelas. Jangan embed dengan pelonggaran CSP.
8. Support: triage unread/context, archive menu aman, kembali ke query/filter yang sedang dipakai. Quick reply tetap bantuan input.
9. Dense operational UI: gunakan label ringkas, wrap identity penting, disclosure metadata; jangan menghapus evidence atau menyembunyikan status untuk estetika.

Admin mobile targetnya “bisa bekerja secara berurutan dari phone”, bukan menyamakan seluruh desktop information density dalam satu layar.

## K. SHARED MOBILE DESIGN SYSTEM RECOMMENDATION

Ini aturan desain masa depan, belum berupa token/code atau keputusan markup final.

| Area | Recommended rule | Preserve / exception |
| --- | --- | --- |
| Viewport/gutter | Satu gutter shell;16px normal,14px narrow bila dibutuhkan; hindari double inset | Tidak perlu memaksa semua surface tepat sama jika brand/public layout berbeda |
| Spacing rhythm | Jarak kecil untuk label/value/action terkait; sedang antar field; lebih besar antar section tugas | Kurangi wrapper padding, bukan semua whitespace |
| Typography | Body tugas15–16px, readable labels, metadata sekunder12–13px; judul mengikuti fungsi | Inter tetap; public heading dapat lebih besar |
| Containers | Satu boundary per record/keputusan; fieldset/divider untuk subkelompok | File dan destructive grouping tetap jelas |
| Radius/border | Gunakan keluarga radius/border existing, bukan radius baru per komponen | Dialog layer/shadow punya fungsi |
| Buttons | Target utama sekitar44px atau lebih; full width bila benar-benar membantu; satu primary per task | Small visual icon boleh dengan hit area lebih besar |
| Form controls | Native controls, label association,16px text untuk mobile input utama, local errors | Jangan mengganti schema/validation/permission |
| Badges/status | Text label wajib, warna sesuai domain; wrap jika panjang | Status application/payment/document tidak disatukan |
| Tables/lists | Identitas dan action satu unit; metadata dapat disclosure | Scroll lokal untuk comparison, bukan body |
| Modals | Fluid width, bounded dynamic height, scroll internal, focus containment/restore | Native cancellation tetap; sheet bukan default |
| Navigation | Client4 destinations, Admin6 in drawer, public anchors/menu | Jangan frontend mobile terpisah |
| Sticky | Satu kebutuhan tetap per screen; hormati keyboard dan safe area | No sticky destructive/finalize default; chat composer sudah punya peran |
| Feedback/loading | Dekat aksi, live status wajar, disabled hanya selama aksi terkait | Polling/status authority tidak diubah |
| Empty/error | Judul/penjelasan singkat, next safe action; invalid state mempertahankan context | Jangan leak PII, secrets, stack traces |

Client dan Admin berbagi ergonomi, focus/error primitives, border/status families, bukan otomatis layout, jumlah navigasi, atau density yang identik.

## L. PRIORITY MATRIX

### P0 — mobile functionality unusable / blocked

NONE VERIFIED. Tidak ada bukti rendered/runtime yang menunjukkan seluruh workflow terblokir. Browser tooling tidak tersedia adalah keterbatasan audit, bukan defect aplikasi.

### P1 — major workflow/usability

| ID | Masalah | Evidence/confidence | Urutan |
| --- | --- | --- | --- |
| A04, A07 | Antrean Pengajuan/Dokumen Admin memisahkan identity dan action dalam table 900px | SOURCE CONFIRMED; severity berdasarkan kebutuhan kerja, visual belum teruji | Pertama bersama mobile operational row |
| A05 | Action stack Admin sesudah keseluruhan detail panjang | SOURCE CONFIRMED DOM/grid; tidak menyatakan endpoint gagal | Pertama bersama detail hierarchy |

### P2 — meaningful usability/design

| ID | Masalah | Evidence/confidence |
| --- | --- | --- |
| C06 | Aside workspace Client mendahului tugas utama | SOURCE CONFIRMED order:-1 |
| C11 | Search FAQ terpisah dari hasil oleh conversations/applications | SOURCE CONFIRMED order/display:contents |
| A05 | History default order 0 sebelum data/dokumen | SOURCE CONFIRMED markup-selector mismatch |
| C07, C08, A06 | Local error/save/upload feedback dan association kurang konsisten | SOURCE CONFIRMED structure; waktu/kelambatan belum diuji |
| C05, C08, A06 | Nested width loss/action density | SOURCE CONFIRMED structure; clipped pixels belum terukur |
| C09 | #ringkasan tanpa target; history panjang | SOURCE CONFIRMED |
| C10 | Waiting instruction hierarchy; same-device QRIS handoff | Structure confirmed; handoff RENDER RISK |
| C12, A09 | Chat viewport cascade, keyboard/menu/delete overlay | Cascade/focus source confirmed; obstruction RENDER RISK |
| A02 | Closed drawer focus dan lack of short-height scroll | SOURCE CONFIRMED implementation; device impact belum diuji |
| A03, A10, A11 | Dashboard order, directory/feed mobile representation | SOURCE CONFIRMED structure |
| A08 | Inbox context return, metadata/menu collision | Back link source confirmed; collision RENDER RISK |
| F06–F08, F14 | Repeated small controls, error/focus ergonomics | SOURCE CONFIRMED declarations; severity bergantung frekuensi/risiko action |
| C11 | Persistent mobile unread cue dan older-conversation reachability | Source confirmed limits; many-thread behavior NOT VERIFIED |

### P3 — polish/consistency/refinement

- C01: public testimonial decoration, section/CTA rhythm.
- C02/A01: auth hierarchy dan one-time-code consistency.
- C03/C04: dashboard/catalog repetition dan secondary grouping.
- C05/C08/C10: minor target increments, repeated badge/version/status.
- F03/F04: heading/metadata/gutter/spacing.
- A08/A11: menu item/pagination polish setelah operability.
- C13: ancillary email/error/fake-checkout QA.

Prioritas tidak dihitung dari banyaknya selector. Temuan RENDER RISK harus dikonfirmasi sebelum patch khusus untuk defect yang belum terbukti.

## M. IMPLEMENTATION DEPENDENCY MAP

| Foundation / component | Dependent screens | Change type berikutnya | Risiko / urutan |
| --- | --- | --- | --- |
| Layout asset order + scoped mobile CSS | Client, public, auth, Admin; terutama chat | Tetapkan ownership rule sebelum edit | Pertama; bukan broad stylesheet cleanup |
| Form/action/error/focus conventions | Auth, details, upload, review, payment, chat | Ergonomi shared, business bindings tetap | Awal dan route-by-route |
| Client header/bottom nav | Dashboard/services/list/create/workspace/payment/help/chat | Context/unread/spacing | Sebelum page-specific sticky |
| Admin header/sidebar | Semua Admin | Drawer focus/scroll/context | Sebelum operational page QA |
| application-list-item + application card pattern | Dashboard/list dan entry context | Density/wrap/CTA hierarchy | Client-only, cek setiap consumer |
| application-progress | Workspace | Preserve atau refinement kecil | Jangan dibuat ulang |
| application-details-form + document/face components | Personal/business workspace | Grouping/feedback/action layout | Security/workflow-sensitive UI-only |
| Admin table/filter/pagination pattern | Dashboard/queues/users/activity | Per-surface mobile rows | Sebelum detail cross-link QA |
| Admin detail review sections | Review/payment/result/history | Evidence-action proximity | Sangat sensitif, jangan ubah services |
| qna + support-inbox | Client help/Admin triage | Grouping/context/unread | Setelah shell conventions |
| chat/show + chat-thread + JS + notifier | Kedua role/general/application threads | Height/focus/composer/back context | Lintas shell, dilakukan satu paket terisolasi |

Komponen Client-only: client-header, mobile-bottom-nav, application-progress, application-details-form, document/face cards, payment template. Admin-only: sidebar/header/page-header, operational queues/chart/feed/user directory. Lintas role: auth view branches, chat, notification/presence, app.js, sebagian app.css.

WorkflowService, PaymentController/provider/webhook, policies, middleware, encryption, scanner, retention dan database bukan dependensi yang boleh “disesuaikan” untuk menyederhanakan UI. Mereka adalah batas yang harus dipertahankan.

## N. RECOMMENDED IMPLEMENTATION PHASES

Semua fase di bawah adalah rencana untuk task berikutnya setelah persetujuan implementasi. Tidak satu pun dikerjakan dalam audit ini.

### Phase 0 — Rendered baseline dan state coverage sebelum patch

- Sediakan sesi Client/Admin yang sah dan data sintetis pada lingkungan yang disetujui; jangan bypass OTP atau policy.
- Ambil baseline keenam width, portrait/landscape/keyboard, serta desktop yang harus dilindungi.
- Reproduksi C06/C11/A04/A05 dan chat cascade lebih dahulu. Rekam false positives bila computed style berbeda.
- Fixture states: empty; draft personal/business; optional representative; incomplete docs; rejected/scan pending/scan rejected; ready payment; waiting BCA/BRI/QRIS; failure/expired/refund/cancelled; paid; review; revision; external process; result pending/verified; completed/archived; long history; long filename/name/email; support unread/archived/long thread.
- State yang tidak dapat dibuka diberi VISUAL QA NOT VERIFIED, bukan dibuat screenshot palsu.

### Phase 1 — Mobile shell dan ergonomi yang menghalangi semua page

Scope: Admin drawer closed-state/scroll, Client context/unread placement, target/error/focus conventions. Tetapkan asset/cascade ownership. Lindungi existing nav dan desktop. Bukan token migration global.

Exit: navigasi/focus/zoom/short-height nyaman pada kedua role; tidak ada duplicate notifier/polling atau tab-focus ke drawer tersembunyi.

### Phase 2 — Admin pekerjaan inti berprioritas P1

Urutan: application queue → document queue → application detail/review. Implement mobile record/action view dan action-evidence hierarchy, kemudian history representation. Pertahankan query/filter/page dan semua action gates.

Exit: admin dapat mencari record, membuka dokumen private, mencatat keputusan yang sah, kembali ke konteks yang benar, dan menemukan next action tanpa pan/scroll berulang yang tidak perlu. QA transisi dilakukan dengan data sintetis dan otorisasi task implementasi nanti.

### Phase 3 — Client workspace dan dokumen

Urutan: workspace header/aside priority → data form grouping/feedback → document/face states → history/result/cancellation. Gunakan mobile progress existing. Correct target anchors tanpa mengubah state semantics.

Exit: Client langsung memahami tugas sekarang; error/action terkait berada dekat; private upload/view/delete/locks tetap aman; desktop workspace sama.

### Phase 4 — Payment dan halaman pendukung Client

Payment selection/waiting/state hierarchy terlebih dulu, lalu create preflight/list/dashboard/services/auth. Public landing hanya P3 terarah. Jangan menggabungkan provider changes ke fase UI.

Exit: nominal/metode/instruksi/expiry/status jelas; state success bukan bukti pembayaran hasil redirect; active-resume dan COMING_SOON tetap benar.

### Phase 5 — Help, inbox dan shared chat sebagai satu alur

Perbaiki Help search-result order, inbox context/menu, persistent unread, lalu shared chat sizing/keyboard/focus. Lakukan bersamaan lintas role untuk menghindari Client membaik tetapi Admin regress.

Exit: FAQ dapat dicari tanpa hasil terpisah jauh; thread switching/back, read/typing, long-scroll, send/edit/delete/archive dan keyboard tervalidasi. Tanpa WebSocket/attachment/audio.

### Phase 6 — Admin supporting screens dan consistency

Dashboard priority/chart, user directory/detail, activity feed, pagination dan remaining small controls. Gunakan mobile patterns Phase 2, bukan copy satu solusi ke semua dataset.

Exit: lookup/chronology nyaman, informasi lengkap tetap tersedia; chart/table desktop terlindungi.

### Phase 7 — Full responsive regression dan handoff

- Ulang keenam width untuk semua screen/state yang dapat dicapai, bukan beberapa contoh.
- Cek batas430/431 serta breakpoint relevan560/600/767/820/850/959/1023 beserta sisi bersebelahannya untuk komponen yang berubah.
- Desktop baseline minimal1280/1440; cek1024 dan768 sesuai shell untuk menemukan regressions tablet/boundary.
- Keyboard, safe area, text zoom, touch spacing, focus, active navigation, long strings, modal/overflow dan orientation.
- Baru pada task implementasi: npm run build; php artisan optimize:clear; php artisan test --no-coverage; vendor/bin/pint --test; git diff --check sesuai AGENTS.md dan PostgreSQL testing terisolasi.
- Tidak perlu migrate:fresh jika tidak ada schema change; schema change memang bukan target redesign mobile.
- Bila PostgreSQL/browser/device tidak tersedia, laporkan NOT VERIFIED; jangan menggantinya dengan SQLite atau mengklaim pass.

## O. FILES LIKELY TO CHANGE

Daftar berikut adalah kandidat patch masa depan, bukan izin implementasi sekarang. Tidak semuanya harus diubah. Paths relatif root repository.

| File | Purpose / expected mobile change | Desktop risk | Logic/security sensitivity |
| --- | --- | --- | --- |
| resources/css/phase-b.css | Client/public/auth layout, workspace order, gutters, documents, payment, Help, chat override | Sangat tinggi: cascade besar dan shared desktop rules | UI-only; jangan mask overflow sebagai solusi |
| resources/css/app.css | Admin shell/table/detail/support, shared chat/focus | Sangat tinggi lintas role | UI-only; protected state selectors |
| resources/js/app.js | Drawer accessibility, local UI feedback/focus, chat viewport/menu/context handling bila perlu | Tinggi: banyak surface dalam entrypoint sama | Jangan ubah camera upload/privacy, submit authority, polling/read semantics |
| resources/views/layouts/client.blade.php | Shell space/context/notifier placement bila perlu | Tinggi | Presence/Livewire/CSRF/CSP tetap |
| resources/views/layouts/admin.blade.php | Shell/drawer mobile lifecycle | Tinggi | Persistent middleware/auth semantics tetap |
| resources/views/components/client-header.blade.php | Account/context/unread presentation | Tinggi | Logout/notifier identity; tidak menambah profile feature |
| resources/views/components/mobile-bottom-nav.blade.php | Persistent unread cue/hit area jika diperlukan | Rendah pada desktop tersembunyi, tinggi pada notifier integration | Satu sumber unread, tidak duplicate polling |
| resources/views/components/admin/header.blade.php | Compact area context/role | Sedang | Identity tidak boleh leak data baru |
| resources/views/components/admin/sidebar.blade.php | Drawer focus/scroll/close semantics | Tinggi pada1023/1024 | Preserve routes/role/logout |
| resources/views/components/admin/page-header.blade.php | Mobile heading/back/actions wrap | Sedang–tinggi semua Admin | Links/actions tetap authoritative |
| resources/views/components/admin/status-badge.blade.php | Wrap/readability bila perlu | Sedang | Jangan ubah status mapping |
| resources/views/components/application-list-item.blade.php | Compact metadata/CTA/wrapping | Sedang | Preserve presenter/action URL |
| resources/views/client/dashboard.blade.php | Reduce repetition/secondary hierarchy | Sedang | Jangan ubah query atau status priority |
| resources/views/client/services/index.blade.php | Catalog density/secondary details | Sedang | COMING_SOON/resume rules |
| resources/views/client/applications/index.blade.php | Remove redundant mobile wrapper, compact action | Tinggi | Dataset/filter semantics; pagination bukan scope otomatis |
| resources/views/client/applications/create.blade.php | Preflight grouping/summary/disclosure | Tinggi | Consent/price/requirements/create action |
| resources/views/client/applications/show.blade.php | Workspace hierarchy, anchors, history/result, cancellation grouping | Sangat tinggi | Seluruh state/actions/file gates; UI-only |
| resources/views/components/application-progress.blade.php | Hanya jika refinement status readability dibutuhkan | Tinggi untuk desktop six-stage | Preserve stage order/presenter; default KEEP |
| resources/views/livewire/application-details-form.blade.php | Groups/error association/local status | Tinggi | wire bindings/autosave/validation/PII; PHP logic bukan target |
| resources/views/components/personal-document-card.blade.php | File state/action grouping/local feedback | Tinggi, dipakai dua service | Upload/replace/delete/private policy/readiness |
| resources/views/components/personal-face-document.blade.php | Camera/file-picker ergonomics dan compact state | Tinggi | Camera consent/stream stop/private upload |
| resources/views/client/payment/show.blade.php | State-specific hierarchy/expiry/QR-VA/context anchor | Tinggi | Payment truth/method/eligibility/refund/cancel gates |
| resources/views/qna.blade.php | Search-result adjacency, help/conversation grouping | Tinggi guest+Client | Thread context/reuse/auth/archived retrieval boundary |
| resources/views/admin/dashboard.blade.php | Priority queue above secondary analysis | Tinggi | Preserve actual metrics/query |
| resources/views/livewire/admin/activity-chart.blade.php | Mobile chart labels/context bila perlu | Tinggi | Data/period meaning tetap |
| resources/views/livewire/admin/application-queue.blade.php | Mobile operational row | Sangat tinggi | Search/filter/page/authorization/data exposure |
| resources/views/livewire/admin/document-queue.blade.php | Grouped readiness row | Sangat tinggi | Counts/readiness/review rules |
| resources/views/admin/applications/show.blade.php | Next action/evidence placement, history, review errors/values | Sangat tinggi | Assignment, payment-before-review, decisions, result verification, completion |
| resources/views/livewire/admin/support-inbox.blade.php | Row metadata/menu/filter/context | Tinggi | Unread/archive gates/query semantics |
| resources/views/livewire/admin/user-directory.blade.php | Lookup mobile rows | Tinggi | PII access/readonly semantics |
| resources/views/admin/users/show.blade.php | Wrapping and compact application rows | Sedang | PII masking/pagination/policy |
| resources/views/livewire/admin/activity-feed.blade.php | Event list mobile representation | Tinggi | Curated events/privacy/ordering |
| resources/views/chat/show.blade.php | Shared chat shell/back context | Sangat tinggi kedua role | Context authorization/route targets |
| resources/views/livewire/chat-thread.blade.php | Composer/menu/dialog/quickreply markup | Sangat tinggi | wire actions/read/typing/edit/delete/escaping |
| resources/views/livewire/global-chat-notifier.blade.php | Persistent mobile presentation bersama shell | Tinggi | Thread unread semantics/toast/polling single instance |
| resources/views/auth/login.blade.php | Local feedback/auth spacing bila diperlukan | Tinggi Client/Admin branches | Login/verification/resend gates |
| resources/views/auth/register.blade.php | Hanya minor mobile rhythm, preserve error model | Sedang | Registration rules/CSRF |
| resources/views/auth/otp.blade.php | Client autofill/target/error consistency | Tinggi Client/Admin branches | OTP throttling/session/challenge |
| resources/views/auth/forgot-password.blade.php | Local form feedback/spacing | Sedang | Reset flow/no disclosure |
| resources/views/auth/reset-password.blade.php | Error association/input ergonomics | Sedang | Token/password constraints |
| resources/views/home.blade.php | Hanya bila markup testimonial/FAQ/CTA perlu refinement | Tinggi pada public desktop | Keep content/demo labels/routes |
| resources/views/components/site-header.blade.php | Public menu height/focus hanya jika QA membuktikan perlu | Tinggi | Navigation/login boundary |
| resources/views/mail/transactional.blade.php | Optional P3 setelah email QA | Sedang lintas mail clients | Secure links, no extra PII |

Wrapper admin/applications/index.blade.php, admin/documents/index.blade.php, admin/support/index.blade.php, admin/users/index.blade.php dan admin/activity/index.blade.php cukup dipertahankan kecuali container outer-nya benar-benar perlu diubah. Jangan edit semua wrapper hanya karena ada dalam inventory.

Tidak termasuk kandidat redesign: routes/*.php, migrations, provider/webhook code, workflow services, policies, middleware, scanner/retention/security checks, dependency manifests, .env, atau vendor files. Test masa depan boleh ditambah dalam task implementasi yang diotorisasi; tidak ada perubahan test sekarang.

## P. HIGH-RISK REGRESSION AREAS

| Area | Why desktop/regression risk | Boundary yang dilindungi | Markup / duplicate presentation decision |
| --- | --- | --- | --- |
| Client shell | Header/nav shared; fixed bottom reserve bisa bocor ke desktop | Phone320–430; existing shell switch850/851 | Shared markup; jangan dua notifier atau dua account forms |
| Workspace main/aside | CSS order mengubah hierarchy; state forms interleaved | Existing mobile850; desktop≥851 tetap;431–850 juga harus dibandingkan | Restrukturisasi mungkin perlu; hindari duplicate interactive forms |
| Document/face components | Dipakai personal/business; grid/status/action shared | Single-column rules560/760 dan sisi atasnya | Shared file input/action satu instance; jangan double upload |
| Payment | State branch + desktop 2 kolom | Existing850 serta phone 600/430 | Shared state data; no duplicate POST payment forms |
| Help | Guest/Client same view, display:contents/order |820/821 dan phone 430 | DOM/focus order perlu benar; jangan hanya visual order hack |
| Admin shell | Fixed sidebar vs drawer |1023/1024; short height | Closed-state inert tidak boleh terbawa desktop |
| Admin detail | Order/main-aside, review forms |767/768; docs1180 dan767 | Markup grouping mungkin perlu; actions/forms tetap satu sumber |
| Admin queues | Table desktop punya relational scan value |767/768 dan desktop≥1024 | Prioritaskan single data loop/presenter; dual presentation hanya bila semantik tabel tak dapat dipertahankan, noninteractive duplication terkontrol |
| Chat | Client CSS dimuat terakhir; Admin dan Client template sama | Semua phone, existing767/600 dan desktop | Satu composer/Livewire thread, satu scroller utama; no duplicate listeners |
| Auth | Satu view role branches dan layout berbeda | Existing850 dan phone 600 | Jangan ubah Admin OTP ketika memperbaiki Client branch |
| Public home | Override bertingkat, gambar/FAQ mobile-desktop |959/860/760/600/430/380 terkait selector | Existing duplicate FAQ presentation boleh dengan state konsisten dan hidden semantics |

Batas paling konservatif task berikutnya adalah phone≤430px untuk perubahan narrow-only. Penggunaan breakpoint existing yang lebih lebar harus dibenarkan oleh komponen dan diuji pada431px sampai batasnya; “mobile-only” tidak memberi izin silent tablet/desktop redesign.

Regressions sensitif yang wajib dicegah:

- Hilangnya csrf token, method spoofing, input names, wire target/loading/disable atau error associations akibat memindah form.
- Dua form/ID/file-input/composer aktif untuk “desktop dan mobile” yang mengirim dua kali.
- Status/readiness dihitung ulang di JS; review sebelum payment; manual paid; retry yang melanggar eligibility.
- Result terakses sebelum verification, dokumen private menjadi publik, iframe viewer yang melemahkan CSP, PII tampil lebih banyak.
- Archive/cancel/delete dibuat terlalu dekat primary CTA atau kehilangan reason/confirmation.
- Upload lock, scan failure, assignment/concurrency, webhook/idempotency, encrypted data, middleware atau retention dilemahkan untuk mencapai tampilan.
- Auto-scroll/focus yang membuat user kehilangan pesan lama, field invalid atau lokasi dokumen.
- Menganggap ngrok ERR_NGROK_6024 defect aplikasi. Itu interstitial free-plan; tidak direkomendasikan perubahan Laravel/Vite/CSP/proxy untuk mengatasinya.

## Q. OPEN QUESTIONS

NONE

Tidak ditemukan keputusan bisnis yang harus diubah untuk menjalankan strategi representasi mobile ini. Item historis pada docs/10-open-questions tidak dibuka kembali sebagai syarat redesign selama current rules dipertahankan.

Keterbatasan browser, same-device QRIS QA, banyaknya thread/records, dan state fixtures adalah kebutuhan verifikasi teknis, bukan pertanyaan estetika atau [BUSINESS CONFIRMATION REQUIRED]. Bila scope nanti mencakup pagination/retrieval baru, itu perlu otorisasi pekerjaan terpisah, bukan asumsi dalam UI-only patch.

## R. SOURCE CHANGE VERIFICATION

Application source modified during audit: NO

PRE-EXISTING CHANGES: checkout sudah dirty pada awal task, mencakup perubahan tracked dan untracked pada source, test, docs, assets dan .codex. Semua dipertahankan; tidak direvert, diformat atau “dibersihkan”.

CHANGES CAUSED BY THIS TASK: hanya artefak dokumentasi baru COMPLETE-MOBILE-UI-UX-AUDIT.md. Application source changes: NONE.

Metode verifikasi:

- Baseline git status --porcelain=v1, git diff --no-ext-diff --binary dan git diff --cached --no-ext-diff --binary diambil sebelum audit.
- Sebelum menulis laporan, output gabungan masih identik dengan baseline: 213236 karakter.
- Baseline SHA-256 mencakup 455 berkas yang tersedia dari app/bootstrap/config/database/docs/public/resources/routes/tests/.codex serta instruksi/manifests/config contoh terpilih, termasuk source untracked yang masuk scope tersebut.
- Pemeriksaan akhir terhadap git diff/status dan 455 hash dilakukan setelah penulisan laporan; hasil dicatat pada penutupan verifikasi di bawah.

Tidak menjalankan npm build, optimize:clear, test, formatter, migration, seeder atau perintah mutasi database. Tidak mengubah .env, dependency, route, asset atau source. Tidak ada pembayaran/upload/chat/action yang dikirim. PostgreSQL runtime/testing dan hasil test terkini: NOT VERIFIED dalam audit ini, bukan digantikan DBMS lain.

Verifikasi akhir: PASS untuk integritas source. Output status + unstaged binary diff + staged binary diff identik dengan baseline setelah satu baris untracked untuk laporan baru dikecualikan. Seluruh 455 hash SHA-256 identik. Tidak ada perubahan aplikasi yang disebabkan task ini; seluruh perubahan pre-existing tetap utuh. git diff --check tidak menghasilkan laporan masalah whitespace pada tracked diff. Pemeriksaan ini tidak merupakan runtime/visual test.

## S. FINAL CLASSIFICATION

Inventory aktif Client + Admin dan structural audit selesai; rekomendasi serta implementation plan tersedia. “Complete” berarti cakupan audit source dan laporan selesai, bukan keenam viewport lolos visual QA. Rendered/browser/device QA seluruh screen tetap VISUAL QA NOT VERIFIED dan wajib menjadi Phase 0 sebelum patch redesign.

MOBILE UI AUDIT COMPLETE — READY FOR REDESIGN
