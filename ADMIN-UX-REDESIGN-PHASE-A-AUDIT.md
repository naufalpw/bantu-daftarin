# BantuDaftarin Admin UX Redesign — Phase A Read-Only Audit

**Tanggal audit:** 5 September 2026  
**Branch:** `ux-redesign`  
**Figma file:** `GnE1etQu2yNQlCuBUiLbKW`  
**Figma root:** `0:1`  
**Status:** `ADMIN READY WITH PRODUCT DECISIONS REQUIRED`

Audit ini membandingkan implementasi Laravel, domain/database/workflow, dan desain admin di Figma. Source code dan aturan bisnis merupakan sumber kebenaran. Figma digunakan sebagai referensi visual, bukan sumber route, lifecycle, authorization, atau fitur.

## A. Executive Summary

Admin BantuDaftarin sudah matang secara domain dan keamanan dasar, tetapi UI admin masih berupa operasional minimum.

- Kekuatan utama: state machine lengkap, review dokumen terkontrol, pembayaran server-side, result verification, private storage, authorization, audit trail, serta application/general-support chat.
- Kesenjangan terbesar dengan Figma: Figma memiliki shell admin, tabel, Member, Document, Activity, dan Chat yang lebih lengkap secara visual, tetapi banyak interaksi hanya visual atau berasal dari template finance.
- Masalah UX terbesar saat ini: hampir seluruh pekerjaan admin dipusatkan pada satu halaman detail pengajuan tanpa navigasi operasional, filtering, atau pemisahan prioritas yang kuat.
- Kesenjangan fungsional terbesar: belum ada queue operasional lintas tahap, visibilitas pembayaran yang layak di detail admin, halaman activity yang terdefinisi, atau modul pengguna.
- Kekhawatiran keamanan terbesar: bukan kerentanan kritis pada source saat ini, tetapi risiko besar bila Figma disalin mentah—khususnya penampilan NIK lengkap, dokumen identitas, foto wajah, dan kontrol perubahan status bebas.

Kesimpulan awal: backend tidak membutuhkan pembersihan domain sebelum redesign inti. Namun modul Activity, Member, dan Document queue masih memerlukan keputusan produk.

## B. Figma Admin Inventory

Ditemukan 18 frame admin/top-level variant. Semua frame desktop; tidak ditemukan referensi admin tablet/mobile.

Shell bersama yang muncul pada hampir seluruh frame:

- Sidebar sekitar 177 px.
- Header putih dengan global search, avatar, identitas “Super Admin”, dan logout.
- Latar halaman abu-abu sangat muda.
- Konten di dalam surface putih beradius besar.
- Navigasi: Dashboard, Pesanan produk, DOCUMENT, Messages, Member, Activity, Get Help, Settings.
- Banyak frame memiliki 24–37 layer tersembunyi dari template generik.
- Tipografi utama Inter 14 px; surface putih; icon line `#33363F`; shadow sangat ringan.

| Node | Nama persis / ukuran | Tujuan dan konten terlihat | Aksi, filter, status | Hubungan |
|---|---|---|---|---|
| `208:11014` | `Desktop`, 1440×1117 | Login Super Admin; logo, email/ID Pengguna, password, lupa password | Masuk; tidak ada OTP/email verification state | PRIMARY REFERENCE untuk visual auth |
| `208:11082` | `Document`, 1440×817.54 | Daftar dokumen dalam tabel: ID, NAME, PRODUCT, DATE | Search global, “Lihat Dokumen” | PRIMARY REFERENCE untuk queue dokumen |
| `208:11646` | `Desktop`, 1440×817.54 nominal, child overflow | Detail dokumen NPWP pribadi; identitas pengajuan, customer, data personal, KTP, KK, Foto Wajah | “Verifikasi Data”; status Proses | STATE VARIANT personal |
| `244:23529` | `Desktop`, 1440×817.54 nominal, child overflow | Detail dokumen NPWP badan; informasi badan, Akta Notaris, SK-AHU | “Verifikasi Data”; status Proses | STATE VARIANT badan |
| `208:12696` | `Dashboard`, 1440×817.54 | Grafik besar “Aktivitas Pengunjung” | Filter bulan “October” | PRIMARY visual shell; chart DESIGN-ONLY |
| `208:13253` | `member`, 1440×817.54 | Tabel “Manager Member”: Nama, ID Pengguna, Email | Checkbox tanpa bulk action yang terlihat | NEEDS DECISION |
| `208:13870` | `Desktop`, 1440×817.54 | Daftar Pesanan produk: ID, NAME, PRODUCT, DATE, STATUS | Filter “Semua jenis”, prev/next; status campuran | PRIMARY REFERENCE daftar pengajuan |
| `295:15105` | `Desktop`, 1440×817.54 | Daftar yang sama, seluruh baris Diajukan | Selector Diajukan; kuning | STATE VARIANT |
| `295:33762` | `Desktop`, 1440×817.54 | Daftar yang sama, seluruh baris Di Terima | Selector Di Terima; cyan | STATE VARIANT |
| `295:38425` | `Desktop`, 1440×817.54 | Daftar yang sama, seluruh baris Proses | Selector Proses; biru | STATE VARIANT |
| `295:42620` | `Desktop`, 1440×817.54 | Daftar yang sama, seluruh baris Selesai | Selector Selesai; hijau | STATE VARIANT |
| `208:14415` | `Desktop`, 1440×817.54 | Satu pengajuan dengan metode, nominal, status, kalender | “Pilih Status”, kalender, Konfirmasi | STATE/DETAIL VARIANT; logika status tidak valid |
| `208:14925` | `Live chat`, 1440×817.54 nominal, child overflow | Inbox chat: checkbox, star, avatar, customer, preview, waktu | Selection, archive/info/delete; badge Messages 5 | PRIMARY REFERENCE chat list |
| `208:15559` | `Desktop`, 1440×817.54 | Percakapan satu customer | Attachment `+`, send, audio bubble, info/delete | PRIMARY REFERENCE chat detail |
| `208:16099` | `Activity`, 1440×817.54 | Activity pembayaran: aktivitas, nama, waktu, status, detail | Semua jenis; Sukses/Gagal; Lihat Detail | PRIMARY, tetapi makna domain belum jelas |
| `291:15458` | `Activity`, 1440×817.54 | Daftar activity khusus Sukses | Selector Sukses | STATE VARIANT |
| `291:17454` | `Activity`, 1440×817.54 | Daftar activity khusus Gagal | Selector Gagal | STATE VARIANT; copy baris kontradiktif |
| `261:12462` | `Activity`, 1440×817.54 | Detail Pembayaran dan Status Pengerjaan | Timeline pembayaran–proses–selesai | DETAIL VARIANT; sebagian tidak didukung domain |

Figma tidak menunjukkan state berikut:

- Empty list.
- Search tanpa hasil.
- Loading.
- Server error.
- Authorization denied.
- OTP admin.
- Revisi dokumen beserta alasan/instruksi.
- Result verification.
- Archive confirmation.
- Responsive admin.

## C. Figma Cleanup Findings

### Hidden template residue

Layer tersembunyi yang berulang dan harus dibuang:

- OpenPay
- Money Flow
- Quick Transfer
- My Wallets
- Invoices
- Analytics
- Send Money
- Recent Transactions
- Savings
- Donation
- Watchtime
- Remittance
- Generic income/statistics/transaction cards
- Data kartu, Bitcoin, PayPal, recipient, dan saldo dummy

### Generic or unsupported artifacts

- Grafik “Aktivitas Pengunjung” tidak memiliki data source.
- Audio message dan attachment chat tidak didukung source.
- Star/archive/delete conversation tidak memiliki domain/action.
- Checkbox Member dan Chat tidak memiliki bulk action.
- “Pilih Status” memungkinkan perubahan status bebas dan bertentangan dengan state machine.
- “Menunggu antrean” pada detail Activity tidak merupakan status backend.
- Harga `Rp 999.999`, customer, email, tanggal, dan dokumen adalah dummy.
- Foto/dokumen contoh, termasuk foto identitas, tidak boleh menjadi production asset.
- Activity “Gagal” tetap menggunakan copy “Pembayaran berhasil diterima”; secara semantik kontradiktif.

### Duplicate/variant relationship

- Empat node `295:*` adalah varian state dari satu daftar pengajuan, bukan empat fitur.
- `208:11646` dan `244:23529` adalah varian layanan personal/badan.
- `208:16099`, `291:15458`, dan `291:17454` adalah filter-state dari satu Activity list.
- `261:12462` adalah detail dari Activity/payment.
- `208:14925` dan `208:15559` adalah list/detail chat.

## D. Current Admin Source Inventory

### Routes

Terdapat 19 route fungsional di grup admin dan 3 entry route auth admin.

Sumber: `routes/admin.php` dan `routes/web.php`.

Route utama:

- `GET /admin/dashboard`
- `GET /admin/applications`
- `GET /admin/applications/{publicId}`
- Review start/finalize
- Review dokumen
- Set estimate
- Mark waiting external
- Upload/review/verify result
- Complete
- Archive
- Document/result view dan download
- `GET /admin/support`
- `GET /admin/chat/{publicId}`
- Login GET/POST dan OTP GET

### Controllers dan requests

- `Admin\DashboardController`
- `Admin\ApplicationController`
- `Admin\SupportController`
- Shared `ChatController`
- Shared policy-controlled Document/Result controllers
- `EstimateRequest`
- `ReviewDocumentRequest`
- `UploadResultRequest`
- `VerifyResultRequest`

### Services

- `AdminWorkflowService`
- `ApplicationTransitionService`
- `ApplicationWorkflowService`
- `DocumentWorkflowService`
- `DocumentFileService`
- `AuditService`
- `NotificationService`
- `AuthOtpService`
- Payment gateway/router/provider services

### Views

- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/applications/index.blade.php`
- `resources/views/admin/applications/show.blade.php`
- `resources/views/admin/support/index.blade.php`
- Shared chat view dan Livewire component
- Shared `resources/views/layouts/app.blade.php`

Tidak ada dedicated admin CSS/component system; halaman admin masih dominan menggunakan utility class langsung di Blade.

### Models/domain records

Mencakup Application, Admin, User, Requirement, Document, DocumentReview, Payment, WebhookEvent, ResultDocument, ChatThread, ChatMessage, status/estimate histories, DocumentAccessLog, dan AuditLog.

### Policies

- ApplicationPolicy
- DocumentPolicy
- ResultDocumentPolicy
- ChatThreadPolicy
- PaymentPolicy

### Tests relevan

- `AdminWorkflowTest`
- `ChatTest`
- `HelpCenterTest`
- `DocumentWorkflowTest`
- `ApplicationWorkflowTest`
- `XenditWebhookTest`
- `PaymentPhaseTwoBTest`
- `SecurityRegressionTest`
- Ownership tests

## E. Current Admin Information Architecture

Current navigation nyata hanya:

1. Dashboard
2. Aplikasi
3. Bantuan
4. Logout

Halaman detail Aplikasi menampung hampir seluruh pekerjaan:

- Data pemohon/badan.
- Persyaratan dan dokumen.
- Review per dokumen.
- Result documents.
- Semua state-changing actions.
- Status history.
- Chat entry.

Kelebihan: sederhana dan tidak mengarang modul.

Kekurangan:

- Tidak ada admin-specific shell/sidebar.
- Tidak ada active navigation state yang kuat.
- Tidak ada pencarian/filtering.
- Dashboard queue hanya mencakup sebagian tahap actionable.
- Payment dan estimate history sudah diload tetapi belum dipresentasikan memadai.
- Banyak aksi berisiko tinggi muncul bersama tanpa hierarki yang kuat.
- Tidak ada pemisahan review, process, dan result verification secara visual.

## F. Authentication / Role Model

“Super Admin” adalah role domain nyata.

- Guard: satu `web` session guard.
- Role MVP: `CLIENT` dan `SUPER_ADMIN`.
- Admin merupakan profile terkait User.
- `EnsureAdmin` mewajibkan User aktif, role SUPER_ADMIN, dan Admin profile aktif.
- Admin login menggunakan form login bersama.
- Setelah kredensial valid, admin menerima challenge `ADMIN_LOGIN`.
- OTP diverifikasi sebelum `Auth::login()`.
- Session diregenerasi setelah login dan diinvalidate saat logout.
- Email harus terverifikasi.
- Login dan OTP memiliki throttle.
- Admin routes menggunakan `auth` + `admin`.
- State-changing requests dilindungi CSRF; webhook merupakan pengecualian terkontrol.

Figma login hanya memadai sebagai referensi surface/form. Figma tidak boleh menghapus OTP, email verification, inactive-account handling, throttle, atau session lifecycle.

Catatan UX rendah: admin OTP masih berbagi controller/view umum, dan missing pending-session diarahkan ke login umum. Ini bukan privilege flaw, tetapi dapat dipoles kelak.

## G. Application Workflow

Source of truth: `app/Enums/ApplicationStatus.php` dan `app/Services/AdminWorkflowService.php`.

```text
DRAFT
  → AWAITING_DOCUMENTS
  → DOCUMENTS_READY_FOR_PAYMENT
  → AWAITING_PAYMENT
  → PAYMENT_CONFIRMED
  → DOCUMENTS_SUBMITTED
  → UNDER_REVIEW
      ├─→ REVISION_REQUIRED
      │     → REVISION_SUBMITTED
      │     → UNDER_REVIEW
      └─→ DOCUMENTS_ACCEPTED
            → ESTIMATE_PENDING
            → IN_PROGRESS
            → WAITING_EXTERNAL_PROCESS
            → RESULT_UPLOADED
            → RESULT_REVIEW
            → COMPLETED
            → ARCHIVED
```

Setiap transition:

- Mengunci application row.
- Memeriksa `canTransitionTo`.
- Menyimpan actor, from/to state, timestamp, dan reason.
- Mencatat status history.
- Mencatat audit.
- Menjalankan notification yang relevan.

### Mapping status Figma

| Figma | Backend paling dekat | Penilaian |
|---|---|---|
| Diajukan | `DOCUMENTS_SUBMITTED`, `REVISION_SUBMITTED` | Valid sebagai grouping presentasi, bukan enum |
| Di Terima | `DOCUMENTS_ACCEPTED`, mungkin `ESTIMATE_PENDING` | Ambigu; lebih tepat “Dokumen diterima” |
| Proses | `UNDER_REVIEW`, `IN_PROGRESS`, `WAITING_EXTERNAL_PROCESS`, `RESULT_UPLOADED`, `RESULT_REVIEW` | Terlalu luas |
| Selesai | `COMPLETED` | Valid |
| Tidak ditampilkan | Draft, pembayaran, revisi, hasil uploaded/review, archive | Figma tidak lengkap |

Admin redesign tidak boleh mereduksi 17 state menjadi empat perubahan status bebas. Empat label Figma hanya dapat digunakan sebagai filter/grouping.

## H. Document Review Workflow

Source mendukung:

- Private upload.
- Quarantine.
- File signature dan MIME checking.
- Malware scanning.
- Randomized stored filename.
- Versioning.
- Active document version.
- PENDING, LOCKED, REJECTED, dan REVISION_REQUIRED.
- Accept, reject, request revision.
- Rejection reason dan revision instruction.
- Document review record.
- Authorized view/download.
- Access log.
- Document lifecycle locking.

Perbandingan Figma:

- `208:11082` merupakan document queue visual, tetapi route serupa belum ada.
- `208:11646` adalah detail personal.
- `244:23529` adalah detail badan.
- Figma belum menunjukkan action per dokumen, version, scan status, reason, instruction, atau revision cycle.
- “Verifikasi Data” terlalu umum; source memerlukan review per dokumen lalu finalisasi review.
- Figma menampilkan NIK penuh. Current admin Blade tidak menampilkan NIK/KK; keputusan ini lebih aman.

Rekomendasi: document review tetap berada dalam konteks Pengajuan. Dedicated “Dokumen” hanya layak bila dibentuk sebagai filtered review queue, bukan repositori dokumen bebas.

## I. Payment / Process / Result Workflow

### Payment

- Payment dilakukan sebelum document review admin.
- Source pembayaran berasal dari provider/webhook, bukan input manual admin.
- Webhook memvalidasi callback token, identity, amount, currency, event, dan idempotency.
- Payment rows dikunci selama update.
- Late failed event tidak menurunkan payment yang sudah PAID.
- Fake checkout hanya tersedia pada environment local/testing ketika driver fake.
- Tidak ada route admin untuk “menyetujui pembayaran”.

Figma Activity payment dapat diadaptasi sebagai read-only payment context, tetapi tidak boleh menjadi manual approval.

Current application detail sudah meload relasi `payments`, tetapi belum merender panel pembayaran yang berguna. Ini adalah presentation gap nyata.

### Process

- Estimate hanya dapat diatur setelah semua required documents diterima.
- Estimate membutuhkan reason dan memiliki history.
- External processing memiliki transition khusus.
- Tidak ada status “Menunggu antrean” seperti Figma.

### Result

- Upload result melalui quarantine/private pipeline.
- Result harus lolos scan.
- Result review memiliki tahap tersendiri.
- Rejected result membutuhkan reason.
- Application hanya dapat completed setelah primary result VERIFIED.
- Client hanya dapat melihat result VERIFIED.
- Archive hanya setelah completed.

Figma tidak memiliki representasi cukup untuk result upload/review/verification.

## J. Chat Workflow

Current source mendukung dua konteks:

- `APPLICATION`
- `GENERAL_SUPPORT`

Invariant model:

- APPLICATION wajib memiliki `application_id`.
- GENERAL_SUPPORT tidak boleh memiliki `application_id`.
- Satu general-support thread dapat digunakan kembali per client.
- Client hanya dapat melihat thread miliknya.
- SUPER_ADMIN dapat mengakses dan membalas.
- Application thread tetap terkait pengajuan.
- Admin ditetapkan pada thread ketika diperlukan.
- Message maksimal 2.000 karakter.
- Message body dirender escaped.
- Read state, read timestamp, typing cache, polling 5 detik, notification, dan audit tersedia.

| Figma feature | Source |
|---|---|
| Chat inbox | Implemented, visual berbeda |
| Conversation | Implemented |
| Timestamp | Implemented |
| Read indicator | Implemented |
| Typing | Implemented |
| Polling | Implemented |
| Audio | Tidak didukung |
| Attachment | Tidak didukung |
| Star | Tidak didukung |
| Archive/delete thread | Tidak didukung |
| Presence/online | Tidak didukung |
| General/application distinction | Source mendukung; Figma tidak menunjukkan |

## K. Activity / Audit Workflow

Data yang tersedia:

- `application_status_histories`
- `application_estimate_histories`
- `audit_logs`
- `document_access_logs`
- `webhook_events`
- Payment records/status
- Chat message audit

Current UI:

- Application status history tampil di detail pengajuan.
- Estimate history diload tetapi belum dipresentasikan.
- Audit log tidak memiliki admin route.
- Webhook/payment events tidak memiliki admin Activity page.
- Tidak ada `/admin/activity`.

Makna Figma Activity paling dekat dengan payment activity feed, bukan global audit log. Namun detailnya mencampur detail pembayaran, status pengerjaan, dan timeline operasional.

Karena raw audit log dapat mengandung reason, actor, IP, dan user-agent, audit log tidak boleh langsung dijadikan UI Activity tanpa redaction/presentation layer.

Status: **NEEDS DECISION** apakah Activity berarti:

1. Payment operations.
2. Application status history.
3. Global administrative audit.
4. Gabungan terkurasi dari beberapa event.

## L. Member / User Management

Source tidak memiliki admin Member module.

| Capability | Status |
|---|---|
| Daftar user | Data tersedia, UI tidak ada |
| Detail user | MISSING |
| Search/filter user | MISSING |
| Edit user | MISSING |
| Deactivate user | Schema mendukung `is_active`, tetapi action/UI tidak ada |
| Melihat pengajuan per user | Dapat diturunkan, tetapi UI tidak ada |
| Bulk selection | DESIGN-ONLY |
| Role management | Tidak dibutuhkan; role hanya CLIENT/SUPER_ADMIN |

Figma Member menunjukkan tabel read-only plus checkbox tanpa action. Itu tidak cukup menjadi spesifikasi modul.

Klasifikasi: **NEEDS DECISION**. Jika dibutuhkan, tahap pertama sebaiknya read-only customer directory, bukan user-management suite.

## M. Figma to Source Mapping Matrix

| Figma node | Figma screen | Figma purpose | Current route | Current source/functionality | Visual match | Functional match | Classification | Recommended treatment |
|---|---|---|---|---|---|---|---|---|
| `208:11014` | Super Admin login | Admin auth | `/admin/login`, `/admin/otp` | Login, OTP, verification, throttle | Rendah | Sebagian | PARTIAL | Adapt card; retain source auth |
| `208:11082` | Document list | Review queue | — | Documents nested in app detail | Tidak ada | Sebagian | PARTIAL | Consider filtered Pengajuan queue |
| `208:11646` | Personal document detail | Review personal docs | `/admin/applications/{id}` | Data/review/actions richer | Rendah | Tinggi | PARTIAL | Adapt composition, not exposed NIK |
| `244:23529` | Business document detail | Review business docs | `/admin/applications/{id}` | Business reps/docs supported | Rendah | Tinggi | PARTIAL | Adapt as service variant |
| `208:12696` | Dashboard | Visitor chart | `/admin/dashboard` | Operational counts + queue | Rendah | Rendah | PARTIAL | Keep shell; discard chart |
| `208:13253` | Member | User directory | — | User model only | Tidak ada | Rendah | NEEDS DECISION | Product decision first |
| `208:13870` | Pesanan list | All applications | `/admin/applications` | Pagination, basic table | Sedang | Sedang | PARTIAL | Primary list reference |
| `295:15105` | Diajukan | Filtered state | same index | Coarse mapping possible | Sedang | Rendah | DUPLICATE | Use grouping/filter only |
| `295:33762` | Di Terima | Filtered state | same index | Ambiguous mapping | Sedang | Rendah | DUPLICATE | Rename to domain-truth label |
| `295:38425` | Proses | Filtered state | same index | Multiple backend states | Sedang | Rendah | DUPLICATE | Operational grouping only |
| `295:42620` | Selesai | Filtered state | same index | Maps to completed | Sedang | Sedang | DUPLICATE | Filter variant |
| `208:14415` | Pesanan detail/status | Payment/status/date action | `/admin/applications/{id}` | Estimate and status actions guarded | Rendah | Sebagian | PARTIAL | Keep estimate picker; discard free status |
| `208:14925` | Live chat list | Support inbox | `/admin/support` | Application/general threads | Rendah | Tinggi | PARTIAL | Adapt inbox density |
| `208:15559` | Chat conversation | Admin conversation | `/admin/chat/{id}` | Text/read/typing/polling | Sedang | Sedang | PARTIAL | Discard audio/attachments |
| `208:16099` | Activity list | Payment event feed | — | Data exists; UI absent | Tidak ada | Sebagian | NEEDS DECISION | Define Activity meaning |
| `291:15458` | Activity success | Success filter | — | Derivable | Tidak ada | Sebagian | DUPLICATE | Filter variant only |
| `291:17454` | Activity failed | Failure filter | — | Derivable | Tidak ada | Sebagian | DUPLICATE | Correct contradictory copy |
| `261:12462` | Activity detail | Payment/process detail | app detail partly | Data distributed in app | Rendah | Sebagian | PARTIAL | Integrate into app detail unless Activity approved |

## N. Source to Figma Reverse Mapping

| Source capability | Route/source | Figma match | Visual reference | Recommendation |
|---|---|---|---|---|
| Admin OTP | `/admin/otp` | None | No | Add source-driven auth state |
| Email/admin active checks | AuthController/middleware | None | No | Preserve invisibly |
| Revision loop | AdminWorkflowService | None | No | Add explicit revision UX |
| Per-document reason/instruction | Document review | None | No | Preserve beside document |
| Document versioning | DocumentWorkflowService | None | No | Show current/history carefully |
| Malware/scan state | Document pipeline | None | No | Present only actionable states |
| Estimate reason/history | Estimate service/model | Calendar only | Partial | Adapt calendar; add reason/history |
| External-process transition | Workflow service | Generic Proses | Weak | Use exact contextual action |
| Result upload/review/verify | AdminWorkflowService | None | No | Build source-driven panels |
| Archive | AdminWorkflowService | None | No | Secondary destructive action |
| General-support thread | Support inbox/chat | No distinction | Partial | Add visible context labels |
| Assignment to admin | Review/chat services | None | No | Keep domain; present only if useful |
| Private access logging | Document controllers | None | No | Never bypass authorized routes |
| Notifications/read/typing | Livewire/services | Partial | Partial | Reuse current component |
| Payment webhook truth | Webhook/payment services | Activity detail | Partial | Read-only admin visibility |
| Audit/status histories | Models/detail view | Activity ambiguous | Partial | Define curated presentation |

## O. Terminology Audit

Rekomendasi final: **PENGAJUAN**.

Alasan:

- Backend canonical model adalah `Application`.
- Client UI sudah menggunakan “Pengajuan”.
- Workflow merupakan administrasi pengajuan, bukan pembelian barang.
- “Pesanan produk” menimbulkan kesan e-commerce.
- Dokumen, review, estimate, external process, dan result lebih natural berada dalam “Pengajuan”.
- “Aplikasi” pada current admin Blade juga sebaiknya diganti pada UI menjadi “Pengajuan”, tanpa merename class/model.

Rekomendasi konsisten:

- Pengajuan
- Daftar pengajuan
- Detail pengajuan
- Pengajuan perlu ditinjau
- Dukungan pengajuan

## P. Design System Findings

Figma variable definitions yang terbaca:

- Inter Regular 14 px / 20 px.
- White `#FFFFFF`.
- Line icon `#33363F`.
- `xsmall-shadow`: dua layer shadow sangat lembut.

Visual lain:

- Pale-gray page background.
- White sidebar/header/surfaces.
- Primary blue active nav.
- Light-blue active-nav background.
- Large 24–30 px radius.
- Table header uppercase kecil.
- Borders sangat ringan.
- Blue, cyan, yellow, green, dan red badges.

Rekonsiliasi dengan BantuDaftarin:

- Gunakan Inter.
- Gunakan navy `#031A5A`, blue `#0068FD`/`#1857E5`, pale blue `#EFF5FF`, dan white.
- Gunakan radius keluarga 12–18 px; jangan menyalin 30 px secara menyeluruh.
- Gunakan shadow minimal.
- Reuse status tones yang lebih lembut dan kontras.
- Hindari membentuk second brand khusus admin.
- Gunakan content density lebih tinggi daripada client tanpa menjadi spreadsheet mentah.

## Q. Reuse / Adapt / Discard Matrix

| Pattern | Treatment | Reason |
|---|---|---|
| Sidebar shell | ADAPT | Membantu orientasi; nav harus mengikuti source |
| Header/profile | ADAPT | Komposisi berguna, global search belum ada |
| White table surface | REUSE | Sesuai administrative workspace |
| Table header rhythm | ADAPT | Perlu text size/contrast lebih baik |
| Active nav state | REUSE | Jelas dan restrained |
| Status badge | ADAPT | Semantik terlalu kasar/saturated |
| Global search | ADAPT | Implement page-local dulu |
| Application table | ADAPT | Butuh public ID, service, client, status, updated, action |
| Document detail composition | ADAPT | Tambahkan lifecycle/review controls |
| Chat list/conversation | ADAPT | Reuse text chat only |
| Member table | DISCARD pending decision | Tidak ada domain workflow |
| Activity list | ADAPT only after decision | Data source/makna belum jelas |
| Visitor chart | DISCARD | Tidak didukung |
| Finance dashboard | DISCARD | Hidden template residue |
| Audio/attachment | DISCARD | Out of scope |
| Arbitrary status selector | DISCARD | Melanggar state machine |
| Full NIK/document exposure | DISCARD | Privacy risk |
| Giant radius/empty surface | DISCARD | Lemah untuk high-density operations |

## R. Responsive Findings

**NO ADMIN RESPONSIVE FIGMA REFERENCE.**

Semua frame admin ditemukan pada desktop 1440 px.

Strategi konservatif untuk implementasi mendatang:

- ≥1024: sidebar + content workspace.
- 768–1023: compact/collapsible navigation; tabel tetap tabel bila kolom penting muat.
- ≤767: top navigation/drawer dan content satu kolom.
- Application/document tables: horizontal-scroll container dengan sticky identity/action columns jika aman.
- Jangan mengubah setiap tabel menjadi card.
- Chat list/detail dapat menjadi master-detail desktop dan route-separated mobile.
- Detail pengajuan: information cards stack; actions tetap state-aware.
- Sidebar tidak sticky pada mobile.
- Touch targets minimal 44 px.
- Tidak boleh ada page-level horizontal overflow.

## S. Security / Privacy Findings

| Severity | Finding |
|---|---|
| CRITICAL | Tidak ditemukan temuan kritis terkonfirmasi |
| HIGH | Tidak ditemukan temuan tinggi terkonfirmasi |
| MEDIUM | Menyalin Figma akan menampilkan NIK/dokumen/foto wajah secara berlebihan. Current source lebih aman dan tidak boleh diregresikan |
| LOW | Application detail meload relasi sebelum `authorize('view')`; route tetap dilindungi admin middleware sehingga tidak ada output leak yang teramati, tetapi authorize-before-load lebih defensif |
| LOW | Disk `local` dan `private` berbagi root `storage/app/private`, sementara `local` memiliki signed serving route. Current document records memakai disk `private`; future UI jangan menghasilkan `temporaryUrl` dari disk `local` untuk dokumen sensitif |
| INFORMATIONAL | Semua SUPER_ADMIN dapat melihat semua pengajuan/thread; konsisten dengan role MVP tunggal, bukan assigned-admin isolation |
| INFORMATIONAL | Audit logs menyimpan reason, actor, IP, dan user-agent. Jangan menampilkan raw audit properties tanpa redaction |
| INFORMATIONAL | `.env` tidak tracked; hanya `.env.example` dan `phpunit.xml` tracked |
| INFORMATIONAL | CSRF aktif untuk web; hanya webhook yang dikecualikan dan webhook memiliki token/idempotency validation |
| INFORMATIONAL | Password, OTP, secret, dan isi dokumen tidak ditemukan dicatat ke audit/log application paths yang diperiksa |

Private document/result protections tetap harus dipertahankan:

- `serve=false` pada disk private/quarantine.
- Authorized streaming controller.
- Result hanya untuk client bila VERIFIED.
- No public permanent URL.
- Randomized stored filename.
- Encrypted original filename.
- Encrypted personal NIK/KK.
- Access logging.

## T. Missing / Redundant / Legacy Features

### Missing presentation yang relevan

- Admin-specific shell.
- Operational dashboard.
- Search/filter application.
- Full payment context.
- Estimate history presentation.
- Structured workflow actions.
- Result review surface.
- Revision reason/instruction hierarchy.
- Responsive admin states.
- Empty/loading/error/no-result states.
- General/application support distinction yang lebih kuat.

### Missing functionality—needs decision

- Member directory/management.
- Dedicated Activity page.
- Dedicated Document queue.
- Global admin search.
- Bulk actions.

### Redundant

- Empat full-page status copies.
- Tiga full Activity list variants.
- Personal/business detail frames sebagai modul terpisah.
- Global search yang diulang tanpa definisi scope.

### Legacy/design-only

- Finance cards/charts.
- Visitor analytics.
- Wallet/invoice/remittance/donation.
- Audio and attachment chat.
- Arbitrary status picker.
- Fake payment/process statuses.

## U. Proposed Final Admin IA

### Core navigation

1. **Dashboard**
   - Queue yang perlu tindakan.
   - Review dokumen masuk/revisi masuk.
   - Result waiting review.
   - Support unread.
   - Tidak menggunakan visitor/revenue metrics.

2. **Pengajuan**
   - Semua pengajuan.
   - Operational filters.
   - Detail data, payment, document review, process, results, history.
   - Documents tetap application-contextual.

3. **Dukungan**
   - Application Support.
   - Bantuan Umum.
   - Text chat list/detail.

### Conditional navigation

4. **Aktivitas** — hanya bila owner menyetujui definisi event dan audience.
5. **Pengguna** — hanya bila kebutuhan operasional Member terbukti.

### Tidak direkomendasikan sebagai nav awal

- Dedicated Document repository.
- Settings tanpa capability.
- Get Help admin yang tidak memiliki route.
- Analytics.
- Finance/template pages.

## V. Recommended Implementation Phases

### ADMIN PHASE B — Foundation dan operational dashboard

- Admin shell.
- Sidebar/header/responsive foundation.
- Canonical “Pengajuan” terminology.
- Shared admin tokens/status components.
- Dashboard queue berbasis data nyata.
- Preserve auth/OTP.

### ADMIN PHASE C — Pengajuan dan review

- Application list/search/filter.
- Detail hierarchy.
- Payment visibility.
- Personal/business data.
- Document review/version/revision.
- Estimate/process/result actions.
- Status and estimate histories.

Ini merupakan phase paling kritis karena mencakup pekerjaan admin utama.

### ADMIN PHASE D — Dukungan

- Support inbox.
- Application/general context labels.
- Current Livewire chat reuse.
- Read/typing/polling states.
- No attachment/audio.

### ADMIN PHASE E — Conditional modules

Hanya setelah keputusan produk:

- Activity.
- Member.
- Dedicated document queue bila volume operasional membenarkan.

### ADMIN PHASE F — Reconciliation dan acceptance

- Responsive 1440/1024/768/430/393/360.
- Keyboard/focus/table access.
- Empty/loading/error.
- Security regression.
- Cross-page visual consistency.
- Final Figma/source reconciliation.

## W. Baseline Test / Build Status

| Check | Status | Result |
|---|---|---|
| `php artisan optimize:clear` | PASS | Cache runtime dibersihkan |
| `php artisan migrate:status` | PASS | 7 migration berstatus Ran |
| `php artisan test --no-coverage` | PASS | 180 tests, 963 assertions |
| `npm run build` | PASS | Vite build berhasil, 57 modules |
| `git diff --check` | PASS | Tidak ada whitespace error |
| `vendor/bin/pint --test` | FAIL | 3 committed files belum sesuai Pint |
| Browser rendering current admin | NOT VERIFIED | Tidak menjalankan browser lokal |
| Figma screenshot inspection | PASS | Seluruh 18 frame/variant diperiksa |

Pint menemukan masalah existing pada:

- `app/Models/BusinessApplicationDetail.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/XenditPaymentProvider.php`

Audit tidak memperbaikinya karena phase ini read-only.

## X. File Modification Confirmation

Selama audit asli:

- Tidak ada source, Blade, CSS, JS, route, migration, test, atau dokumentasi yang diubah.
- Tidak ada dependency yang dipasang.
- Tidak ada formatting yang dijalankan dalam mode write.
- Tidak ada migration yang dibuat/dijalankan.
- Screenshot Figma hanya diunduh ke folder temporary OS untuk inspeksi, bukan ke repository.

Catatan concurrency: worktree memiliki perubahan existing pada awal audit. Git reflog menunjukkan commit `a83ed93 Redesign client UX pages and shared components` dibuat oleh `naufalpw` pada 2026-09-05 22:16:18 +0700 ketika audit berlangsung. Audit tidak menjalankan `git add` atau `git commit`.

Dokumen Markdown ini ditambahkan setelah audit atas permintaan eksplisit pemilik proyek.

## Needs Decision Sebelum Implementasi

1. Konfirmasi “Pengajuan” sebagai terminology admin pengganti “Pesanan/Aplikasi”.
2. Tentukan Activity sebagai payment operations, application history, atau curated global audit.
3. Tentukan apakah Member benar-benar memerlukan dedicated module dan apakah sifatnya read-only atau management.
4. Tentukan apakah Dokumen memerlukan standalone review queue atau cukup menjadi filter di Pengajuan.

## Final Classification

**ADMIN READY WITH PRODUCT DECISIONS REQUIRED**

Domain, security boundary, dan workflow inti cukup kuat untuk memulai redesign shell, Dashboard, Pengajuan, review, dan Dukungan. Implementasi Member, Activity, serta dedicated Document queue belum boleh dimulai sebelum keputusan produk di atas diselesaikan.
