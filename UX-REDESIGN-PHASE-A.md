# UX Redesign Phase A

## Information Architecture and User Flow Design

Status: **Read-only architecture audit**  
Project: **Bantu Daftarin**  
Date: **4 September 2026**

## Executive summary

Rekomendasi utama Phase A:

- Primary navigation client dipadatkan menjadi **Beranda, Layanan, Pengajuan, Bantuan**.
- **Aktivitas tidak menjadi primary navigation** karena datanya lebih berguna sebagai pembaruan di dashboard dan timeline kontekstual dalam pengajuan.
- **Profile tidak menjadi halaman tersendiri** karena backend belum menyediakan pengelolaan profil.
- Detail pengajuan menjadi **central application workspace**.
- Pembayaran tetap dapat memakai halaman transaksi khusus, tetapi selalu dibuka dan kembali ke workspace pengajuan.
- Figma digunakan hanya untuk visual DNA, bukan information architecture atau route hierarchy.
- Alur nyata menempatkan **pembayaran sebelum pemeriksaan admin**.

Audit didasarkan pada `DESIGN.md`, `ANTISLOP.md`, Figma MCP, routes, application state enum, policies, workflow services, controllers, models, views, Livewire components, CSS, JavaScript, dan tests existing.

## A. Audit real user workflow

### Entry and authentication flow

| Tahap | Yang dilihat | Yang dapat dilakukan | CTA berikutnya | Perpindahan |
|---|---|---|---|---|
| Visitor | Home, layanan, QnA, Login, Register | Memahami layanan, membuka bantuan, masuk atau mendaftar | `Daftar` atau `Masuk` | Ya |
| Register | Form nama, email, password | Membuat akun client | `Daftar` | Dialihkan ke Login dengan pesan periksa email |
| Email verification | Tautan verifikasi melalui email | Membuka signed verification link | `Verifikasi email` dari email | Kembali ke Login |
| Login | Email dan password | Memulai authentication challenge | `Masuk` | Selalu menuju OTP setelah kredensial valid |
| OTP | Input enam digit dan resend cooldown | Verifikasi OTP atau kirim ulang setelah cooldown | `Verifikasi` | Dashboard client atau admin |
| Dashboard | Daftar seluruh pengajuan, create CTA, activity CTA | Membuka pengajuan atau layanan | Bergantung pada kartu yang dipilih | Ya |
| Select service | NPWP Perseorangan, NPWP Badan Usaha, Lapor Pajak coming soon | Memilih layanan aktif | `Mulai aplikasi` | Personal dapat resume draft; badan melewati pemilihan jenis |
| Create application | Form awal layanan dan consent | Membuat draft | `Buat draft` | Saat ini menuju halaman registrasi lama |
| Fill application | Form Livewire dan requirement | Mengubah data pada state yang diizinkan | `Lanjut ke dokumen` | Saat ini tersebar antara halaman registrasi dan detail pengajuan |

Email verification memiliki route resend, tetapi audit tidak menemukan permukaan UI khusus yang jelas untuk meminta resend setelah registrasi.

### State machine aktual

Urutan utama yang benar:

```text
DRAFT
  ↓
AWAITING_DOCUMENTS
  ↓
DOCUMENTS_READY_FOR_PAYMENT
  ↓
AWAITING_PAYMENT
  ↓
PAYMENT_CONFIRMED
  ↓
DOCUMENTS_SUBMITTED
  ↓
UNDER_REVIEW
  ├── REVISION_REQUIRED → REVISION_SUBMITTED ─┐
  │                                           └→ UNDER_REVIEW
  ↓
DOCUMENTS_ACCEPTED
  ↓
ESTIMATE_PENDING
  ↓
IN_PROGRESS
  ↓
WAITING_EXTERNAL_PROCESS
  ↓
RESULT_UPLOADED
  ↓
RESULT_REVIEW
  ↓
COMPLETED
  ↓
ARCHIVED
```

Lifecycle yang tervalidasi:

```text
Upload dokumen
→ dokumen lengkap
→ pembayaran
→ konfirmasi pembayaran
→ kirim dokumen
→ review admin
```

Review admin tidak terjadi sebelum pembayaran.

### Audit operasional setiap state

| Backend state | Pengalaman client saat ini | Aksi client dan CTA | Perpindahan saat ini | Khusus admin |
|---|---|---|---|---|
| `DRAFT` | Data dasar, requirement, dokumen, status draft | Edit data, upload dokumen, `Lanjut ke dokumen` | Create menuju halaman registrasi lama; detail juga tersedia | Tidak ada transisi admin |
| `AWAITING_DOCUMENTS` | Daftar dokumen wajib dan dokumen aktif | Upload, ganti versi, hapus jika UI mengizinkan | Tetap pada registrasi/detail; pembayaran muncul setelah lengkap | Tidak ada review |
| `DOCUMENTS_READY_FOR_PAYMENT` | Dokumen dinyatakan lengkap untuk lanjut | `Lanjut ke pembayaran`; upload versi baru masih didukung backend | Pindah ke `/app/bayar/{id}` | Tidak ada review |
| `AWAITING_PAYMENT` | Metode, nominal, instruksi atau provider checkout | Menyelesaikan pembayaran; mengganti dokumen masih dimungkinkan sebelum pembayaran | Halaman pembayaran/provider | Status pembayaran berubah melalui provider/webhook |
| `PAYMENT_CONFIRMED` | Pembayaran berhasil, dokumen terkunci | `Kirim dokumen untuk diperiksa` | Kembali ke detail pengajuan | Belum dapat review sebelum client submit |
| `DOCUMENTS_SUBMITTED` | Status dokumen telah dikirim | Menunggu; chat tetap tersedia | Activity dan detail sama-sama menampilkan status | `Mulai pemeriksaan` |
| `UNDER_REVIEW` | Status sedang diperiksa | Menunggu atau menghubungi admin | Detail atau activity | Review setiap dokumen dan finalisasi |
| `REVISION_REQUIRED` | Status perlu perbaikan | Upload hanya requirement yang ditandai; `Kirim perbaikan` setelah semua valid | Tetap pada pengajuan | Alasan dan instruksi berasal dari admin |
| `REVISION_SUBMITTED` | Perbaikan telah dikirim | Menunggu | Detail/activity | Mulai pemeriksaan ulang |
| `DOCUMENTS_ACCEPTED` | Dokumen diterima | Menunggu estimasi | Detail/activity | Menetapkan estimasi |
| `ESTIMATE_PENDING` | Status transisional singkat | Tidak ada aksi | Detail/activity | Menyimpan estimasi dan memulai proses |
| `IN_PROGRESS` | Pengajuan diproses, estimasi dapat tersedia | Menunggu | Detail/activity | Memindahkan ke proses eksternal |
| `WAITING_EXTERNAL_PROCESS` | Menunggu proses administratif di luar aplikasi | Menunggu atau chat | Detail/activity | Upload hasil |
| `RESULT_UPLOADED` | Workspace belum memperlihatkan hasil yang belum diverifikasi | Tidak ada aksi | Payment page saat ini keliru mengelompokkan state ini sebagai hasil tersedia | Memulai review hasil |
| `RESULT_REVIEW` | Workspace hanya memperlihatkan hasil berstatus `VERIFIED` | Menunggu | Detail/activity | Verifikasi atau tolak hasil; selesaikan jika hasil utama verified |
| `COMPLETED` | Hasil verified dapat dilihat dan diunduh | Lihat/unduh hasil | Workspace | Arsipkan |
| `ARCHIVED` | Pengajuan dan hasil menjadi referensi read-only | Lihat riwayat dan hasil yang tetap diizinkan | Workspace | Tidak ada transisi lanjutan |

Kontrol admin, result verification, dan dokumen private tetap dibatasi melalui policy existing.

## B. Current UX problems

### Structural UX problems

- Terdapat tiga bahasa layout: `layouts.client`, `layouts.marketing`, serta layout guest/admin generik.
- Client kehilangan konteks authenticated saat membuka registrasi lama, pembayaran, activity, atau chat.
- Pembuatan draft dan halaman registrasi berikutnya memiliki tanggung jawab form yang berulang.
- Payment page juga menampilkan state dokumen dikirim, proses, dan hasil. Tanggung jawabnya terlalu luas.
- Activity membagi data yang sama ke tiga kelompok: pembayaran, proses, dan pesanan.
- Activity detail mengulang payment dan status history yang seharusnya berada dalam workspace.
- Header tidak menunjukkan destination aktif.
- Global `Live Chat` dapat mengarah ke anchor dashboard ketika tidak ada thread, bukan ke bantuan nyata.
- Pemilihan jenis badan usaha menjadi intermediary screen besar, padahal dapat menjadi bagian dari start form.
- Dashboard mencampur pengajuan aktif, selesai, dan arsip dalam satu daftar tanpa prioritas.
- Result yang belum verified dapat disebut “tersedia” oleh payment page, sementara policy dan application detail menyembunyikannya dengan benar.

### Visual problems

- Radius sekitar 30 sampai 32px digunakan pada terlalu banyak panel.
- Card besar, pill, dan shadow digunakan merata sehingga hierarchy melemah.
- UI menggunakan Inter, Open Sans, DM Sans, Roboto, Nunito Sans, Urbanist, dan Montserrat pada permukaan berbeda.
- Tombol kembali terlalu kecil dibandingkan target sentuh yang disyaratkan.
- Halaman administrasi, client workspace, payment, dan chat terlihat seperti produk berbeda.
- CSS memiliki beberapa lapisan breakpoint dan override terpisah, sehingga hasil responsif bergantung pada cascade yang kompleks.

### Content problems

- Copy Home masih menyebut pelaporan pajak sebagai bagian alur aktif.
- Ada testimoni bernama “Andi Pratama” tanpa sumber yang terverifikasi.
- Klaim seperti “100% online”, “Aman & Terpercaya”, dan “Proses Cepat” belum memiliki bukti.
- Static five-step journey tidak sesuai state machine aktual.
- Beberapa QnA tidak memiliki jawaban.
- Terdapat typo dan campuran istilah seperti `primary representative`.
- CTA `Konfirmasi` pada payment result tidak menjelaskan hasil tindakannya.
- UUID penuh ditampilkan sebagai “Nomor aplikasi”, padahal belum ada nomor pengajuan khusus yang human-friendly.
- Revision reason dan revision instruction tersedia di model, tetapi belum cukup terlihat bagi client.

### Backend limitations

- Tidak ada route atau functionality untuk mengubah profil.
- Tidak ada global support thread di luar konteks application.
- Belum ada satu read model untuk activity gabungan yang diurutkan berdasarkan waktu.
- Belum ada application reference yang terpisah dari UUID.
- Dukungan beberapa pengajuan aktif untuk layanan yang sama belum dibatasi.

Apakah client boleh memiliki beberapa pengajuan aktif dari jenis layanan yang sama perlu ditandai `[BUSINESS CONFIRMATION REQUIRED]`. Workflow saat ini tidak memberlakukan unique active application secara umum.

## C. Information architecture final

```text
PUBLIC
├── Home
├── Bantuan
│   └── QnA
├── Login
├── Register
└── Flow-only
    ├── Email verification
    ├── OTP
    └── Password reset

AUTHENTICATED CLIENT
├── Beranda
├── Layanan
├── Pengajuan
│   ├── Perlu tindakan
│   ├── Sedang diproses
│   └── Selesai
├── Bantuan
│   ├── QnA
│   └── Chat dalam konteks pengajuan
├── Application Workspace
│   ├── Ringkasan
│   ├── Data & Dokumen
│   ├── Pembayaran
│   ├── Proses
│   └── Hasil
└── Account menu
    ├── Identitas read-only
    ├── Email read-only
    ├── Bantuan
    └── Keluar

ADMIN
├── Dashboard / Work queue
├── Pengajuan
├── Admin Application Workspace
└── Account / Logout
```

`Aktivitas` dan `Profile` tidak direkomendasikan sebagai destination primer. Ini menghasilkan arsitektur yang lebih sederhana tanpa menghilangkan data atau fungsi.

## D. Primary navigation

| Item | Responsibility | Route/domain | Kapan terlihat | Badge |
|---|---|---|---|---|
| Beranda | Orientasi, next action utama, pengajuan aktif, pembaruan terbaru | `/app/dashboard` | Client authenticated | Tidak perlu |
| Layanan | Menjelaskan layanan dan memulai atau melanjutkan pengajuan | `/app/services` | Client authenticated | Tidak |
| Pengajuan | Daftar seluruh pengajuan berdasarkan kebutuhan user | Existing `/app/activity` dapat dipresentasikan sebagai index Pengajuan | Client authenticated | Hanya jumlah pengajuan yang benar-benar memerlukan tindakan |
| Bantuan | QnA yang valid dan jalan masuk ke bantuan kontekstual | `/qna` | Public dan authenticated | Tidak |
| Account | Identitas, email, bantuan, logout | Menu pada header, bukan halaman | Client authenticated | Tidak |

Badge tidak boleh menunjukkan seluruh pengajuan aktif. Badge hanya layak untuk state yang meminta tindakan client:

- `DRAFT`
- `AWAITING_DOCUMENTS`
- `DOCUMENTS_READY_FOR_PAYMENT`
- `AWAITING_PAYMENT`
- `PAYMENT_CONFIRMED`
- `REVISION_REQUIRED`

Unread chat badge hanya boleh ditambahkan bila count aktual diekspos ke navigation.

## E. Central application workspace

Konsep ini dapat dibuat dengan routes dan business semantics sekarang.

### Workspace header

```text
NPWP Perseorangan
ID Pengajuan: ...8f2c   [Salin ID]

Perlu melengkapi dokumen
2 dari 4 dokumen wajib sudah tersedia

Langkah berikutnya:
Unggah Kartu Keluarga                         [Unggah dokumen]
```

Header harus berisi:

- Nama layanan.
- ID pengajuan dari `public_id`, ditampilkan pendek dengan aksi salin full value.
- Status user-friendly.
- Enam tahap progress.
- Satu next action utama.
- Estimasi jika benar-benar tersedia.

Jangan mengarang nomor pengajuan baru. Nomor human-friendly baru memerlukan keputusan dan kemungkinan perubahan schema: `[BUSINESS CONFIRMATION REQUIRED]`.

### Existing route reconciliation

| Existing route | Peran dalam workspace | Rekomendasi |
|---|---|---|
| `/app/applications/{publicId}` | Central workspace | Pertahankan |
| `/npwp-pribadi/{publicId}` | Form dan dokumen personal lama | Jadikan compatibility alias/redirect ke `Data & Dokumen` |
| `/npwp-badan/{publicId}` | Form dan dokumen badan lama | Jadikan compatibility alias/redirect ke `Data & Dokumen` |
| `/app/bayar/{publicId}` | Pemilihan metode dan provider handoff | Pertahankan sebagai transactional subpage |
| `/app/activity/{publicId}` | Payment dan status history | Alias/redirect ke bagian `Proses` |
| `/app/chat/{threadId}` | Chat application-specific | Pertahankan sebagai dedicated contextual page |
| Document view/download | Authorized file response | Tetap dedicated |
| Result view/download | Authorized verified-result response | Tetap dedicated |

Pembayaran layak menjadi dedicated page karena dapat melibatkan redirect provider dan status pending. Namun halaman tersebut hanya bertanggung jawab atas payment, bukan seluruh lifecycle.

## F. Dashboard redesign

Responsibility dashboard:

- Memberi orientasi personal.
- Menampilkan satu next action paling penting.
- Menampilkan pengajuan aktif secara ringkas.
- Memberikan jalan ke layanan.
- Menampilkan update terbaru tanpa menduplikasi full timeline.
- Memberikan bantuan yang relevan.

```text
┌──────────────────────────────────────────────────────────────┐
│ Selamat datang, [Nama]                                      │
│ Lanjutkan proses yang sedang berjalan.                       │
├──────────────────────────────────────────────────────────────┤
│ PRIORITAS ANDA                                               │
│ NPWP Perseorangan                                            │
│ Perlu melengkapi 2 dokumen                                   │
│ [Buka pengajuan]                                             │
├─────────────────────────────────────┬────────────────────────┤
│ PENGAJUAN AKTIF                    │ PEMBARUAN TERBARU       │
│ NPWP Badan · Sedang diperiksa      │ Pembayaran diterima    │
│ Estimasi: 12 Sep 2026              │ 4 Sep 2026, 09:40      │
│ [Lihat semua pengajuan]            │ [Lihat proses]         │
├─────────────────────────────────────┴────────────────────────┤
│ Butuh layanan lain?                                          │
│ NPWP Perseorangan · NPWP Badan · Lapor Pajak: Segera hadir  │
│ [Lihat layanan]                         [Buka bantuan]        │
└──────────────────────────────────────────────────────────────┘
```

Tidak ada KPI, statistik dekoratif, atau grid kartu seragam.

## G. Services experience

Flow yang direkomendasikan:

```text
Layanan
→ pilih NPWP Perseorangan atau NPWP Badan Usaha
→ lihat deskripsi, harga, dan dokumen yang dibutuhkan
→ lanjutkan pengajuan yang sudah ada atau mulai draft
→ Application Workspace
```

Keputusan:

- `/app/applications/create/{service}` tetap diperlukan secara teknis untuk membuat draft.
- Secara UX, route tersebut menjadi halaman **Persyaratan & Mulai**, bukan intermediary form yang kemudian diulang.
- `/jenis-badan` tidak perlu menjadi layar terpisah. Business type dapat dipilih pada start form.
- Setelah draft dibuat, arahkan pengguna ke workspace, bukan ke shell registrasi lama.
- Layanan coming soon harus tetap terlihat non-interaktif dan jujur sebagai `Segera hadir`.
- Bila ada pengajuan nonterminal, CTA utama menjadi `Lanjutkan pengajuan`.
- Pilihan `Buat pengajuan baru` hanya ditampilkan setelah kebijakan concurrent application dikonfirmasi.

Application creation logic, validation, consent, requirement snapshot, dan state awal tetap dipertahankan.

## H. Workspace section design

### Pilihan navigation

Gunakan **in-page section navigation**, bukan wizard atau tab yang memalsukan state linear.

Alasannya:

- State machine memiliki revision loop.
- User perlu melihat hubungan data, dokumen, pembayaran, dan proses.
- Anchor section dapat memakai satu route.
- Data tidak hilang hanya karena tab tidak aktif.
- Dedicated payment/provider flow tetap dapat dibuka bila diperlukan.

Pada mobile, section navigation berubah menjadi kontrol `Bagian pengajuan` yang ringkas dan accessible, bukan lima tab sempit.

### Section responsibilities

| Section | Isi |
|---|---|
| Ringkasan | Current stage, next action, layanan, ID, estimasi, recent contextual activity |
| Data & Dokumen | Data personal/badan, representative, requirement, versi aktif, scan/review status, revision reason dan instruction |
| Pembayaran | Harga snapshot, metode, payment reference, status, history; CTA hanya saat valid |
| Proses | Satu timeline gabungan dari application history dan payment events yang relevan |
| Hasil | Hanya hasil berstatus `VERIFIED`, dengan view/download melalui route terotorisasi |

Progress bukan navigation. Progress menjelaskan posisi. Section navigation membantu melihat informasi.

Visibility:

- Ringkasan dan Data & Dokumen selalu tersedia.
- Pembayaran tampil sejak dokumen siap dibayar atau jika payment history sudah ada.
- Proses tampil sejak dokumen dikirim untuk review.
- Hasil mulai diperkenalkan saat result phase, tetapi link dokumen hanya muncul setelah verified.
- Chat selalu kontekstual terhadap pengajuan dan tidak menjadi progress step.

## I. Status and next-action presentation model

| Backend status | Label client | Deskripsi | Next action | CTA |
|---|---|---|---|---|
| `DRAFT` | Pengajuan belum dikirim | Data pengajuan masih dapat dilengkapi | Periksa data dasar | `Lanjutkan pengajuan` |
| `AWAITING_DOCUMENTS` | Lengkapi dokumen | Dokumen wajib belum lengkap | Unggah dokumen yang belum tersedia | `Unggah dokumen` |
| `DOCUMENTS_READY_FOR_PAYMENT` | Dokumen siap untuk pembayaran | Dokumen minimum telah lengkap | Pilih metode pembayaran | `Lanjut ke pembayaran` |
| `AWAITING_PAYMENT` | Menunggu pembayaran | Pembayaran belum diterima | Selesaikan instruksi pembayaran | `Lihat pembayaran` |
| `PAYMENT_CONFIRMED` | Pembayaran diterima | Dokumen siap dikirim kepada tim | Konfirmasi pengiriman dokumen | `Kirim untuk diperiksa` |
| `DOCUMENTS_SUBMITTED` | Dokumen telah dikirim | Pengajuan menunggu pemeriksaan dimulai | Tunggu pemeriksaan | Tidak ada CTA utama |
| `UNDER_REVIEW` | Dokumen sedang diperiksa | Tim sedang memeriksa kelengkapan dokumen | Tunggu hasil pemeriksaan | Tidak ada CTA utama |
| `REVISION_REQUIRED` | Dokumen perlu diperbaiki | Satu atau beberapa dokumen memerlukan perbaikan | Baca instruksi dan unggah versi baru | `Perbaiki dokumen` |
| `REVISION_SUBMITTED` | Perbaikan telah dikirim | Dokumen pengganti menunggu pemeriksaan ulang | Tunggu pemeriksaan | Tidak ada CTA utama |
| `DOCUMENTS_ACCEPTED` | Dokumen diterima | Semua dokumen telah disetujui | Tunggu estimasi proses | Tidak ada CTA |
| `ESTIMATE_PENDING` | Jadwal proses sedang disiapkan | Tim sedang menentukan estimasi | Tunggu pembaruan | Tidak ada CTA |
| `IN_PROGRESS` | Pengajuan sedang diproses | Proses administrasi sedang berjalan | Pantau estimasi | `Lihat proses` sebagai secondary CTA |
| `WAITING_EXTERNAL_PROCESS` | Menunggu proses instansi | Proses dilanjutkan secara manual di luar aplikasi | Tunggu hasil | Tidak ada CTA |
| `RESULT_UPLOADED` | Hasil sedang disiapkan | Dokumen hasil telah diterima tim dan belum dapat dibuka | Tunggu verifikasi | Tidak ada CTA |
| `RESULT_REVIEW` | Hasil sedang diverifikasi | Tim sedang memastikan hasil sebelum tersedia | Tunggu verifikasi | Tidak ada CTA |
| `COMPLETED` | Pengajuan selesai | Hasil terverifikasi tersedia | Simpan atau lihat hasil | `Lihat hasil` |
| `ARCHIVED` | Pengajuan diarsipkan | Pengajuan selesai disimpan sebagai riwayat | Lihat kembali bila diperlukan | `Buka arsip` |

Payment status memiliki mapping terpisah:

- `PENDING` → Menunggu pembayaran.
- `PAID` → Pembayaran berhasil.
- `FAILED` → Pembayaran gagal, coba metode lagi jika workflow mengizinkan.
- `EXPIRED` → Instruksi pembayaran kedaluwarsa.
- `CANCELLED` → Pembayaran dibatalkan.
- `REFUND_REQUESTED` → Pengembalian diajukan.
- `REFUNDING` → Pengembalian sedang diproses.
- `REFUNDED` → Dana telah dikembalikan.

Backend enum tidak perlu diubah.

## J. Support and chat

Gunakan kedua jalur, tetapi dengan responsibility berbeda:

- **Bantuan global:** QnA dan penjelasan umum layanan.
- **Chat kontekstual:** komunikasi mengenai satu pengajuan tertentu.

Aturan:

- Jangan tampilkan global `Live Chat` yang memilih thread secara implisit.
- Dari workspace, gunakan `Tanya tentang pengajuan ini`.
- Dari Bantuan, pengguna dapat memilih salah satu pengajuan sebelum membuka chat.
- Tanpa pengajuan, QnA tetap tersedia tetapi jangan membuat global support thread fiktif.
- Typing indicator dipertahankan.
- Attachment dan audio tetap out of scope.
- Tombol attachment disabled yang sekarang ada sebaiknya tidak ditampilkan pada redesign karena bukan kemampuan aktif.

## K. Activity

Rekomendasi: kombinasi global dan contextual, tetapi bukan primary destination bernama Aktivitas.

- Dashboard: maksimal beberapa update terbaru.
- Pengajuan index: status terakhir dan next action setiap application.
- Workspace Proses: timeline penuh aplikasi tersebut.
- Payment history masuk bagian Pembayaran.
- Jangan menampilkan satu application tiga kali sebagai transaksi, proses, dan pesanan.

Existing `payments` dan `statusHistories` tetap menjadi sumber data. Presentation layer hanya menggabungkan event secara kronologis.

## L. Profile and account

Dedicated Profile tidak direkomendasikan karena backend belum mempunyai fungsi edit profile.

Minimum account menu:

```text
[Nama user]
[email user]

Bantuan
Keluar
```

Nama dan email bersifat read-only. Jangan menambahkan edit nama, ganti email, avatar upload, preferensi, atau account deletion tanpa backend dan business requirement.

## M. Desktop navigation

```text
┌────────────────────────────────────────────────────────────────────┐
│ [BantuDaftarin]  Beranda  Layanan  Pengajuan  Bantuan   [Account] │
└────────────────────────────────────────────────────────────────────┘

Application Workspace
┌────────────────────────────────────────────────────────────────────┐
│ NPWP Perseorangan · ID ...8f2c             [Perlu dokumen]        │
│ Tahap 2 dari 6 · Berikutnya: unggah Kartu Keluarga                 │
│                                                                    │
│ Ringkasan | Data & Dokumen | Pembayaran | Proses | Hasil           │
├──────────────────────────────────────────────┬─────────────────────┤
│ Konten section                              │ Next action         │
│                                              │ Bantuan pengajuan   │
└──────────────────────────────────────────────┴─────────────────────┘
```

Pada 1024px, workspace masih dapat memakai sidebar sempit. Di bawah 768px, sidebar menjadi bagian alur utama.

## N. Mobile navigation

Gunakan hybrid:

- Compact top bar untuk logo, judul konteks, dan account.
- Bottom navigation untuk empat destination utama.
- Section selector lokal di dalam workspace.

```text
┌──────────────────────────────┐
│ [Logo]              [Account]│
├──────────────────────────────┤
│ NPWP Perseorangan            │
│ Perlu melengkapi dokumen     │
│ Tahap 2 dari 6               │
│                              │
│ [Bagian: Data & Dokumen  v]  │
│                              │
│ Kartu Keluarga               │
│ Belum diunggah               │
│ [Unggah dokumen]             │
│                              │
│ Butuh bantuan? [Buka chat]   │
├──────────────────────────────┤
│ Beranda Layanan Pengajuan    │
│               Bantuan        │
└──────────────────────────────┘
```

Profile/account tidak dimasukkan ke bottom navigation.

## O. Figma visual DNA audit

Figma MCP diverifikasi dan digunakan untuk membaca design context serta screenshot Home, registration/upload, document/admin, activity, payment, chat, dan QnA pada file `GnE1etQu2yNQlCuBUiLbKW`.

### KEEP

- BantuDaftarin wordmark.
- Navy `#031A5A`.
- Primary blue sekitar `#0068FD` dan `#1857E5`.
- Pale-blue background sekitar `#EFF5FF`.
- White surfaces.
- Ilustrasi administrasi pajak yang memang terkait layanan.
- Upload/document icon language.
- Kontras navy dan blue untuk hierarchy.

### ADAPT

- Service cards: gunakan hierarchy berdasarkan availability dan next action.
- Upload cards: tambahkan version, scan, review, error, dan revision status.
- Payment card: gunakan hanya dalam konteks pembayaran.
- Back control: tingkatkan menjadi target minimal 44px.
- Forms: konsolidasikan label, help text, error, dan required state.
- Status chips: gunakan terbatas pada status penting.
- Typography: kurangi menjadi satu UI family.
- Radius/shadow: 30px tidak dipakai pada semua permukaan.
- QnA split-panel: ubah menjadi stacked disclosure pada mobile.
- Illustration: hanya pada orientasi layanan atau empty state yang relevan.

### DISCARD

- Navigation dan route hierarchy Figma.
- Cart icon.
- Global Live Chat tanpa application context.
- Active-looking Lapor Pajak action.
- Static five-step flow yang tidak sesuai backend.
- Testimoni atau klaim tanpa sumber.
- Generic admin sidebar seperti Member, Settings, Reports, atau menu lain yang tidak ada.
- Payment screens untuk process/result states.
- Giant rounded container sebagai pola default.
- Semua flow-dependent labels dari frame Figma.

## P. Design system direction

| Area | Direction |
|---|---|
| Page background | Pale blue untuk membedakan workspace dari white surfaces |
| Primary | Bright BantuDaftarin blue untuk satu CTA utama |
| Secondary | Navy untuk heading, contextual support, dan high-emphasis text |
| Typography | Inter sebagai UI family utama; wordmark tetap menjadi identitas |
| Surfaces | White, border ringan; shadow hanya saat elevation dibutuhkan |
| Card roles | Action panel, service option, document requirement, payment summary; bukan setiap section |
| Buttons | Primary, secondary outlined, text action, destructive; label selalu spesifik |
| Forms | Label permanen, help text dekat field, inline error, satu kolom pada mobile |
| Status | Blue informational, amber waiting/action, red revision/error, green verified/completed, neutral archived |
| Radius | 8px controls, 12 sampai 16px regular surface, radius besar hanya untuk branded focal area |
| Spacing | Basis 4/8px dengan jarak lebih besar antarkelompok informasi |
| Icons | Hanya untuk fungsi atau domain yang jelas; tidak menggantikan label |
| Illustrations | Layanan, onboarding, atau empty state; tidak pada setiap card |
| Motion | Minimal untuk feedback, disclosure, dan transition state; tidak dekoratif |

## Q. Anti-slop review

Design Read:

- Identity: layanan pendampingan administrasi NPWP yang human-assisted.
- ENERGY 1: tenang dan tidak agresif.
- RHYTHM 2: cukup bervariasi antara action panel, list, form, timeline, dan document rows.
- MOTION 1: hanya feedback dan state transition yang berguna.

### Issues found and corrected

| Risiko awal | Koreksi proposal |
|---|---|
| Enam primary navigation items | Dipadatkan menjadi empat |
| Activity menjadi silo terpisah | Digabung ke dashboard dan workspace |
| Dashboard menjadi card mosaic | Satu priority panel, list pengajuan, update, dan service shortcut |
| Lima bagian workspace menjadi wizard palsu | In-page section navigation dengan progress terpisah |
| Semua status menjadi pills | Pill hanya untuk status ringkas; penjelasan memakai teks |
| Radius besar pada semua permukaan | Radius dibedakan berdasarkan role |
| Figma testimonial dan klaim | Dihapus sampai ada sumber nyata |
| Lapor Pajak tampak aktif | Hanya `Segera hadir`, tanpa CTA order |
| Generic admin/sidebar IA | Dibuang; admin fokus queue dan application workspace |
| Icon dekoratif | Hanya ikon yang menjelaskan layanan, file, payment, atau action |
| Gradient/glow | Tidak digunakan sebagai default |
| Semua section memiliki pola seragam | Form, timeline, document list, payment summary, dan result list memiliki komposisi berbeda |

### Phase A delivery gate

- R-02 PASS: proposed UI copy tidak bergantung pada gaya marketing generik.
- R-03 SPECIFIED: perilaku untuk enam viewport telah ditentukan; runtime belum diverifikasi karena belum ada implementasi.
- R-17 PASS: tidak ada statistik buatan.
- R-18 PASS: testimoni tanpa sumber dibuang.
- R-23 PASS: visual assets hanya berasal dari Figma yang diperiksa.
- R-24 PASS: primary destinations memiliki route/domain nyata.
- R-25 SPECIFIED: contrast AA menjadi acceptance criterion implementation.
- R-26 PASS: tidak ada control disabled dekoratif atau destination fiktif dalam proposal.
- R-27 PASS: loading, empty, error, locked, revision, payment, dan result states diwajibkan.
- R-28 PASS: QnA tanpa jawaban tidak dianggap content final.
- R-32 SPECIFIED: section navigation, bottom nav, form, chat, dan disclosure harus keyboard accessible.
- R-33 PASS: tidak ada source atau CSS yang dipatch pada Phase A.
- R-34 PASS: tidak ada theme toggle atau dark mode fiktif.
- R-35 NOT APPLICABLE: Phase A tidak mengirim running UI.
- R-36 PASS: klaim keamanan, kecepatan, dan customer trust tanpa bukti dibuang.
- R-37 PASS: DESIGN.md dibaca lebih dulu dan dials telah ditetapkan.
- R-38 PASS: coming-soon dan data kosong diberi representasi jujur.
- R-01 PASS: flat brand colors dipilih untuk hierarchy; tidak ada gradient default.
- R-04 PASS: icon dibatasi pada makna domain atau action.
- R-06 PASS: satu UI type family direkomendasikan.
- R-07 PASS: pale-blue background berasal dari identitas BantuDaftarin.
- R-08 PASS: tidak ada arrow dekoratif pada semua CTA.
- R-09 PASS: badges hanya menyampaikan status atau jumlah action nyata.
- R-10 PASS: tidak ada glassmorphism.
- R-12 PASS: shadow hanya untuk elevation yang diperlukan.
- R-13 PASS: tidak ada glow.
- R-14 PASS: service, document, activity, dan payment tidak dipaksa memakai card identik.
- R-19 PASS: MOTION 1 membatasi animasi pada feedback fungsional.
- R-22 PASS: hanya ilustrasi Figma yang berhubungan dengan administrasi pajak.
- R-05 PASS: dashboard dan workspace mengikuti content hierarchy, bukan template SaaS.
- R-11 PASS: radius bervariasi berdasarkan component role.
- R-15 PASS: CTA menggunakan tindakan spesifik seperti `Unggah dokumen`.
- R-16 PASS: copy diarahkan ke bahasa Indonesia yang langsung.
- R-20 PASS: workflow tetap jelas sebagai pendampingan NPWP meskipun logo dilepas.
- R-21 PASS: tidak ada dark mode tanpa kebutuhan.
- R-29 PASS: tiga core colors ditambah semantic status colors.
- R-30 PASS: proposal tidak meniru dashboard produk populer.
- R-31 PASS: alasan navigation, typography, cards, spacing, dan illustration telah dicatat.
- C-1 sampai C-5 PASS pada level architecture: semua keputusan terikat workflow, content nyata, state lengkap, dan evidence source. Runtime quality tetap untuk phase implementation.

## R. Responsive principles

| Area | 1440 | 1024 | 768 | 430 / 393 / 360 |
|---|---|---|---|---|
| Header | Full navigation dan account | Gap lebih rapat, label tetap penuh | Logo + account; primary nav pindah ke bawah | Compact top bar |
| Primary nav | Horizontal header | Horizontal header | Bottom navigation | Bottom navigation 4 item, safe-area aware |
| Workspace | Main + sidebar | Main + sidebar sempit | Satu kolom | Satu kolom, next action dekat status |
| Section nav | Horizontal anchors | Horizontal anchors | Wrap atau compact selector | `Bagian pengajuan` selector |
| Progress | Enam tahap lengkap | Enam tahap lebih ringkas | Current stage + bar | `Tahap x dari 6`, status, dan next action |
| Cards | Hanya role-specific panels | Grid maksimal dua kolom | Satu kolom | Sebagian surface diratakan menjadi rows |
| Forms | Dua kolom bila field berhubungan | Dua kolom selektif | Satu kolom | Satu kolom, 16px minimum input text |
| Documents | Requirement rows dengan status dan action | Rows | Stacked row | Nama, status, reason, lalu full-width action |
| Payments | Summary dan method dapat berdampingan | Dua kolom sempit | Stacked | Nominal, status, method, CTA dalam satu alur |
| Activity | Timeline utama + metadata | Timeline | Timeline satu kolom | Timestamp ringkas, tanpa tabel horizontal |
| Chat | Context panel atau dedicated page | Dedicated panel/page | Full-height page | Composer sticky, 44px controls, tanpa attachment |
| Admin tables | Full table | Reduced columns | Stacked records | Focused records; tidak horizontal-pan |

Semua viewport `1440`, `1024`, `768`, `430`, `393`, dan `360` harus diverifikasi secara terpisah saat implementasi. Tidak boleh hanya mengasumsikan 430 mewakili 393 dan 360.

## S. Route strategy

Tidak ada route yang diubah pada Phase A.

| Current route | Current purpose | New UX purpose | Strategy | Notes |
|---|---|---|---|---|
| `/` | Landing page | Public Home | KEEP | Koreksi copy dan layanan coming soon |
| `/qna` | Static QnA | Bantuan | KEEP | Hanya pertanyaan dengan jawaban nyata |
| `/register` | Register | Register | KEEP | Jadikan canonical |
| `/daftar` | Alias register | Alias register | REDIRECT / ALIAS | Jangan dipromosikan sebagai destination terpisah |
| `/login` | Client login | Login | KEEP | Authentication flow-only |
| `/forgot-password` | Reset request | Reset request | KEEP | Flow-only |
| `/reset-password/{token}` | Reset password | Reset password | KEEP | Flow-only |
| `/email/verify/{...}` | Signed verification | Email verification | KEEP | Hidden from nav |
| `/email/verification-notification` | Resend verification | Resend verification | KEEP | Perlu permukaan UI yang jelas |
| `/auth/otp` | Client OTP | Client OTP | KEEP | Hidden from nav |
| `/admin/login`, `/admin/otp` | Admin auth | Admin auth | KEEP | Tidak masuk client navigation |
| `/logout` | Logout | Account action | KEEP | Account menu |
| `/jenis-badan` | Business type intermediary | Compatibility entry | HIDE FROM NAV / REDIRECT | Fold type selection into start form |
| `/npwp-pribadi` | Resume draft atau start | Service entry | ALIAS | Route dapat memilih resume/start |
| `/npwp-badan` | Menuju jenis badan | Service entry | ALIAS | Jangan membuka intermediary besar |
| `/npwp-pribadi/{id}` | Legacy personal form/documents | Workspace Data & Dokumen | REDIRECT / ALIAS | Preserve deep links initially |
| `/npwp-badan/{id}` | Legacy business form/documents | Workspace Data & Dokumen | REDIRECT / ALIAS | Preserve deep links initially |
| `/app/dashboard` | Paginated application list | Beranda overview | KEEP | Fokus active application dan next action |
| `/app/activity` | Three activity accordions | Pengajuan index | KEEP URL / REPURPOSE PRESENTATION | URL architecture tidak harus sama dengan IA |
| `/app/activity/{id}` | Payment and status detail | Workspace Proses | REDIRECT / ALIAS | Hilangkan duplicate page |
| `/app/services` | Service catalog | Layanan | KEEP | Tambahkan requirement/start/resume context |
| `/app/applications/create/{service}` | Create form | Persyaratan & Mulai | KEEP | Tetap menjadi titik pembuatan draft |
| `POST /app/applications` | Create draft | Create draft | KEEP / HIDE FROM NAV | Business logic tidak berubah |
| `/app/applications/{id}` | Application detail | Central workspace | KEEP | Canonical pengajuan |
| `PUT /app/applications/{id}` | Update details | Workspace form action | KEEP / HIDE FROM NAV | State/policy tetap |
| `POST .../submit` | Draft submit | Workspace CTA | KEEP / HIDE FROM NAV | Label client diperjelas |
| `POST .../payment` | Payment creation | Legacy payment action | ALIAS / HIDE | Ada overlap dengan payment store |
| `/app/bayar/{id}` | Payment view | Payment transactional subpage | KEEP | Hanya payment responsibility |
| `POST /app/bayar/{id}` | Create payment | Payment CTA | KEEP / HIDE FROM NAV | Kandidat canonical payment action |
| `POST .../documents/submit` | Submit after payment | Workspace CTA | KEEP / HIDE FROM NAV | Tidak boleh muncul sebelum paid |
| `POST .../revision/submit` | Submit revision | Workspace CTA | KEEP / HIDE FROM NAV | Enable hanya saat semua revisi lengkap |
| Document upload/delete | Document lifecycle | Data & Dokumen actions | KEEP / HIDE FROM NAV | Preserve state guards |
| Document view/download | Private file access | Data & Dokumen links | KEEP / HIDE FROM NAV | Preserve policy dan scan checks |
| Result view/download | Verified result access | Hasil links | KEEP / HIDE FROM NAV | Hanya verified |
| `/app/chat/{threadId}` | Application chat | Contextual application chat | KEEP | Preserve Livewire behavior |
| `/admin/dashboard` | Stats and queue | Operational queue | KEEP | Kurangi decorative counts |
| `/admin/applications` | Application list | Admin Pengajuan | KEEP | Tambahkan filtering saat implementation bila diperlukan |
| `/admin/applications/{id}` | Admin detail/actions | Admin workspace | KEEP | Canonical processing workspace |
| Admin transition routes | Review, estimate, process, result, complete, archive | Contextual admin actions | KEEP / HIDE FROM NAV | Jangan menjadi navigable pages |
| `/webhooks/xendit` | Payment webhook | Internal integration | KEEP / INTERNAL | Idempotency dan signature wajib dipertahankan |
| `/testing/fake-payments/*` | Local fake checkout | Development-only checkout | KEEP / HIDE | Hanya local/testing |

## T. Implementation risk analysis

| Risk | Level | Recommendation |
|---|---:|---|
| Authentication and OTP | High | Ubah shell/view saja; jangan mengubah pending challenge session atau throttle |
| Email verification | High | Pertahankan signed URL, verified middleware, dan account lookup |
| State workflow | Critical | Semua CTA tetap memanggil workflow service existing; jangan set status langsung |
| Livewire forms | High | Pilih satu canonical form per workspace dan pertahankan validation/autosave behavior |
| Duplicate registration forms | Medium | Migrasikan presentation bertahap; jangan menghapus legacy route sebelum deep links diuji |
| Document authorization | Critical | Selalu gunakan existing policy dan private stream routes |
| Document locking | Critical | Derive upload/delete/replace visibility dari rules existing |
| Revision process | High | Tampilkan reason/instruction dan disable submit sampai semua requirement valid |
| Payment | Critical | Pertahankan price snapshot, reference, idempotency, provider callback, dan fake driver |
| Activity consolidation | Medium | Buat presentation/read model dari payments dan status histories, tanpa membuat data duplikat |
| Chat | High | Pertahankan thread ownership, polling/Livewire, read state, dan typing indicator |
| Result | Critical | Jangan expose file sebelum `VERIFIED`; hilangkan klaim premature dari payment page |
| Admin processing | Critical | Tombol admin hanya muncul untuk transition valid, bukan generic status dropdown |
| Responsive CSS | Medium | Konsolidasikan shells dan breakpoints bertahap; hindari menambah override terakhir lagi |
| Existing tests | High | Pada implementation, jalankan auth, policy, workflow, payment, document, result, dan chat tests |

Pendekatan backend-minimal:

1. Pertahankan semua route dan action endpoint.
2. Jadikan application detail canonical workspace.
3. Alihkan legacy presentation secara bertahap.
4. Gunakan current relationships yang sudah di-load.
5. Tambahkan read-model query hanya bila dashboard atau merged activity benar-benar membutuhkannya.
6. Jangan mengubah enum, transition, policy, atau schema untuk kebutuhan visual.

## U. Proposed final sitemap

### Hierarchical sitemap

```text
Bantu Daftarin
├── Public
│   ├── Home
│   ├── Bantuan
│   ├── Login
│   └── Register
│       └── Email verification
│
├── Client
│   ├── Beranda
│   ├── Layanan
│   │   ├── NPWP Perseorangan
│   │   ├── NPWP Badan Usaha
│   │   └── Lapor Pajak: Segera hadir
│   ├── Pengajuan
│   │   ├── Perlu tindakan
│   │   ├── Sedang diproses
│   │   └── Selesai
│   ├── Application Workspace
│   │   ├── Ringkasan
│   │   ├── Data & Dokumen
│   │   ├── Pembayaran
│   │   ├── Proses
│   │   ├── Hasil
│   │   └── Chat pengajuan
│   ├── Bantuan
│   └── Account menu
│
└── Admin
    ├── Dashboard / Queue
    ├── Pengajuan
    └── Application Workspace
        ├── Client & service context
        ├── Document review
        ├── Revision
        ├── Estimate
        ├── External process
        ├── Result verification
        ├── Completion
        ├── Chat
        └── Audit history
```

### Final user journey

```text
Visitor
→ Register
→ Buka email verification link
→ Login
→ OTP
→ Beranda
→ Layanan
→ Pilih NPWP Perseorangan atau NPWP Badan Usaha
→ Baca persyaratan dan harga
→ Buat draft atau lanjutkan pengajuan
→ Application Workspace
→ Lengkapi data
→ Lengkapi dokumen
→ Lanjut ke pembayaran
→ Pembayaran dikonfirmasi
→ Kirim dokumen
→ Review admin
   ├→ Jika revisi: baca instruksi
   │  → unggah versi baru
   │  → kirim perbaikan
   │  → review ulang
   └→ Jika diterima
      → estimasi
      → proses
      → proses eksternal
      → hasil diunggah admin
      → hasil diverifikasi admin
      → hasil tersedia untuk client
      → selesai
      → arsip
```

## Verification and change status

- `DESIGN.md` dibaca penuh terlebih dahulu.
- `ANTISLOP.md` dibaca penuh sebagai quality filter.
- Figma MCP digunakan secara live untuk visual reference.
- Routes, controllers, services, enums, policies, models, Blade, Livewire, CSS, JavaScript, dan tests diaudit secara statis.
- `php artisan route:list` digunakan untuk memverifikasi route surface.
- Tests dan build tidak dijalankan karena Phase A diwajibkan read-only dan test suite dapat menulis database/cache.
- Tidak ada application source, Blade, CSS, JavaScript, route, backend, database, workflow, atau component yang diubah pada Phase A.
- Dokumen ini adalah artefak audit. Dokumen ini tidak mengimplementasikan rekomendasi UX.
