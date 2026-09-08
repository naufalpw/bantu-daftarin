# Manual Browser Acceptance Checklist

Checklist ini digunakan untuk acceptance manual MVP Bantu Daftarin. Checklist tidak menggantikan automated test dan tidak mengklaim hasil browser sebelum operator menjalankannya.

## Aturan dan evidence

- Jalankan pada database development PostgreSQL yang terisolasi. Jangan gunakan data client nyata.
- Gunakan hanya nama, email, nomor identitas, gambar, dan PDF sintetis. Jangan upload KTP, KK, foto wajah, atau hasil layanan nyata.
- Simpan `public_id`, timestamp, screenshot seperlunya, dan status terakhir untuk setiap case.
- Status database pada checklist ditulis dengan nama internal; UI seharusnya menampilkan label bahasa sederhana, misalnya `REVISION_REQUIRED` sebagai `Perlu perbaikan dokumen`.
- Jangan mengubah status atau pembayaran langsung di database. Untuk payment, gunakan gateway fake dan endpoint webhook lokal yang tervalidasi.
- `migrate:fresh` hanya boleh digunakan pada database lokal/test yang memang boleh dihapus, tidak pada database production.

## Prasyarat lokal

1. PostgreSQL aktif pada `127.0.0.1:5432`, database `.env` sudah tersedia, dan migration/seeder berhasil. XAMPP bila digunakan hanya menyediakan Apache/PHP.
2. Asset sudah dibangun dengan `npm run build`.
3. Cara menjalankan aplikasi yang direkomendasikan untuk acceptance dari repository ini:

   ```powershell
   php artisan optimize:clear
   php artisan migrate --seed --force
   php artisan serve --host=127.0.0.1 --port=8000
   ```

   Sebelum command terakhir, set sementara `APP_URL=http://127.0.0.1:8000` pada `.env` agar link signed/email/payment mengarah ke port yang benar, lalu jalankan `php artisan optimize:clear` lagi. Buka `http://127.0.0.1:8000`. Apache XAMPP tetap boleh aktif untuk kebutuhan development lain, tetapi command `artisan serve` yang direkomendasikan tidak menggunakan DocumentRoot Apache. Jika Apache sudah dikonfigurasi ke folder `public` project, gunakan `APP_URL=http://localhost` dan buka `http://localhost` sebagai alternatif.

4. Jalankan worker pada terminal terpisah karena notification menggunakan database queue:

   ```powershell
   php artisan queue:work database --tries=1
   ```

5. Untuk acceptance email, gunakan SMTP mail catcher lokal (Mailpit, MailHog, atau layanan setara) pada localhost. Contoh konfigurasi SMTP lokal yang harus disesuaikan dengan catcher:

   ```dotenv
   MAIL_MAILER=smtp
   MAIL_HOST=127.0.0.1
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   ```

   Setelah mengubah `.env`, jalankan `php artisan optimize:clear`. Buka UI mail catcher sesuai port yang dikonfigurasi. Konfigurasi repository saat ini memakai `MAIL_MAILER=log`; jangan menjadikan isi `storage/logs/laravel.log` sebagai cara mengambil OTP, karena email/OTP dapat tertulis ke log. Jika mail catcher belum tersedia, case email verification, OTP, password reset, dan email notification berstatus `[BLOCKED - SMTP lokal belum dikonfigurasi]`.

6. Untuk happy-path upload lokal, gunakan ClamAV bila tersedia. Jika ClamAV belum tersedia, gunakan hanya pada environment lokal:

   ```dotenv
   MALWARE_SCAN_DRIVER=testing
   ```

   Lalu `php artisan optimize:clear`. Driver ini hanya abstraction testing yang sudah ada, bukan konfigurasi production. Untuk negative case scanner unavailable, kembalikan `MALWARE_SCAN_DRIVER=clamav` tanpa ClamAV dan pastikan upload ditolak. Setelah acceptance, kembalikan konfigurasi lokal ke `clamav` atau konfigurasi scanner yang benar-benar digunakan.

7. Untuk payment boundary lokal, set sementara pada `.env`:

   ```dotenv
   XENDIT_DRIVER=fake
   XENDIT_CALLBACK_TOKEN=local-only-callback-token
   ```

   Jalankan `php artisan optimize:clear`. Fake gateway membuka checkout lokal di `/testing/fake-payments/{paymentId}/checkout`; halaman tersebut hanya simulasi dan bukan pembayaran Xendit live. Tombol simulasi mengirim payload ke endpoint webhook lokal yang sama. Untuk acceptance webhook manual, gunakan `POST /webhooks/xendit` dengan callback token lokal, reference/amount/currency/email yang benar, dan event ID baru. Jangan memakai credential production.

   Untuk mengirim event `PAID` secara lokal tanpa mengubah database langsung, ambil nilai payment sintetis terakhir dari Tinker, lalu kirim request berikut dengan nilai yang terlihat pada payment tersebut:

   ```powershell
   php artisan tinker
   # Di prompt Tinker:
   # $p = App\Models\Payment::with('application.user')->latest()->first();
   # $p->only(['reference_id', 'external_id', 'amount', 'currency']);
   # $p->application->user->email;
   ```

   ```powershell
   $body = @{ id = '<external_id>'; external_id = '<reference_id>'; status = 'PAID'; amount = 150000; currency = 'IDR'; payer_email = 'client.one@example.test' } | ConvertTo-Json
   Invoke-RestMethod -Method Post -Uri 'http://127.0.0.1:8000/webhooks/xendit' -Headers @{ 'x-callback-token' = 'local-only-callback-token'; 'x-event-id' = 'manual-paid-001' } -ContentType 'application/json' -Body $body
   ```

   Ganti URL jika aplikasi dibuka melalui Apache (`http://localhost`) dan ganti amount/email sesuai payment yang diuji. Untuk fake gateway, `id` harus sama persis dengan `payments.external_id`; `external_id` pada payload adalah `payments.reference_id`.

## Data development yang aman digunakan

### Admin seeded

- Email: `admin@example.test` (nilai `ADMIN_EMAIL` pada `.env` lokal).
- Nama: `Super Admin`.
- Role: `SUPER_ADMIN`, aktif, email sudah terverifikasi.
- Password tidak ditulis di dokumentasi atau laporan. Seeder mengambilnya dari `ADMIN_SEED_PASSWORD` pada `.env` lokal. Untuk melihatnya hanya pada terminal lokal, jalankan:

  ```powershell
  Select-String .env -Pattern '^ADMIN_EMAIL=|^ADMIN_SEED_PASSWORD='
  ```

  Jangan menyalin nilai password ke chat, screenshot, commit, atau log.

### Client test accounts

Tidak ada client yang diseed. Buat dua account sintetis melalui UI:

- Client A: `client.one@example.test`, nama `Client Satu`, password sintetis lokal minimal 12 karakter.
- Client B: `client.two@example.test`, nama `Client Dua`, password sintetis lokal minimal 12 karakter.

Gunakan mail catcher untuk verifikasi email dan OTP masing-masing account. Client A dipakai untuk happy-path personal end-to-end; Client B dipakai untuk business flow dan ownership/IDOR negative case.

### Service dan requirement seeded

Nilai berikut berasal dari `DatabaseSeeder` dan konfigurasi development saat ini; harga ini bukan keputusan harga production:

- `NPWP_PERSONAL` / NPWP Perseorangan: `ACTIVE`, IDR 150000 melalui `NPWP_PERSONAL_PRICE`; requirement wajib `KTP` 5 MB, `KK` 5 MB, `FOTO_WAJAH` 5 MB.
- `NPWP_BUSINESS` / NPWP Badan Usaha: `ACTIVE`, IDR 500000 melalui `NPWP_BUSINESS_PRICE`; requirement wajib `KTP_PENANGGUNG_JAWAB` 5 MB, `AKTA_NOTARIS` 15 MB, `SK_AHU` 10 MB; `SURAT_KUASA` 5 MB optional dengan condition snapshot `jika diwakilkan`.
- `TAX_REPORTING` / Lapor Pajak: `COMING_SOON`, harga `NULL`, tanpa active requirement dan tanpa application workflow.

## Urutan eksekusi yang direkomendasikan

1. Siapkan environment, mail catcher/worker, synthetic files, dan catat baseline database.
2. Jalankan C-01 sampai C-05 untuk Client A, lalu ulangi authentication dasar untuk Client B.
3. Jalankan C-06 sampai C-13 untuk application personal Client A. Sisipkan A-01 sampai A-03 saat application mencapai tahap admin review.
4. Jalankan A-04 lalu C-14 untuk revision; setelah Client A submit, ulangi A-03 untuk review ulang, kemudian lanjut A-05 sampai A-11 dan C-15.
5. Selesaikan A-06 sampai A-10 untuk hasil, lalu C-16 untuk akses/download client.
6. Buat application business memakai Client B dan jalankan C-07 sampai C-10; tidak perlu membuat workflow tambahan di luar lifecycle MVP.
7. Jalankan N-01 sampai N-08 dengan resource Client A/B yang sudah dicatat.

## Client journey

### C-01 — Registration

- Prasyarat: mail catcher aktif; belum ada user dengan email Client A.
- Account/role: anonymous, membuat Client A.
- Route/page: `GET /register`, submit `POST /register`.
- Action: isi nama, email sintetis, password dan konfirmasi; submit.
- Expected: pendaftaran berhasil dan user diminta memeriksa email; tidak langsung mendapat session client penuh.
- Expected state: row `users` dibuat dengan role `CLIENT`, `email_verified_at` masih `NULL`; email verification notification masuk queue/mail catcher.

### C-02 — Email verification

- Prasyarat: C-01 berhasil; worker dan mail catcher aktif.
- Account/role: Client A melalui link email.
- Route/page: signed `/email/verify/{publicId}/{hash}`.
- Action: klik link verifikasi sekali; ulangi link yang sama atau link rusak sebagai negative check.
- Expected: verifikasi valid berhasil; link invalid/expired tidak memverifikasi user.
- Expected state: `users.email_verified_at` terisi pada link valid; event audit/notification tidak berisi password atau OTP.

### C-03 — Login password dan email OTP

- Prasyarat: Client A sudah verified; mail catcher aktif.
- Account/role: Client A.
- Route/page: `GET /login`, lalu `GET /auth/otp`.
- Action: login dengan password; ambil OTP dari mail catcher; submit enam digit; coba OTP salah, expired, dan reuse pada percobaan terpisah.
- Expected: password benar belum cukup untuk session penuh; OTP valid membuka `/app/dashboard`; OTP salah/expired/reuse ditolak dan percobaan dibatasi.
- Expected state: `auth_challenges` menyimpan hash, expiry, attempts, dan consumed/used state; session diregenerasi setelah sukses; OTP tidak muncul di response atau log aplikasi.

### C-04 — Logout

- Prasyarat: C-03 berhasil.
- Account/role: Client A.
- Route/page: tombol `Keluar`, `POST /logout`.
- Action: logout lalu buka `/app/dashboard` atau tekan Back dan refresh.
- Expected: session invalid; halaman client meminta login kembali.
- Expected state: session client diinvalidasi dan token/session diregenerasi sesuai flow logout.

### C-05 — Service selection dan scope lock

- Prasyarat: Client A login penuh.
- Account/role: Client A.
- Route/page: `GET /app/services`.
- Action: periksa dua service active; buka Personal dan Business; coba membuka/mengirim service `TAX_REPORTING` dengan public ID atau request yang dimanipulasi.
- Expected: Personal dan Business memiliki tombol mulai; Lapor Pajak hanya tampil sebagai segera hadir dan tidak dapat dipesan.
- Expected state: hanya service active yang dapat membuat `applications`; request `TAX_REPORTING` ditolak backend dan tidak membuat payment/application.

### C-06 — Personal application, draft, autosave, consent

- Prasyarat: C-03 dan C-05 berhasil; gunakan Client A.
- Account/role: Client A.
- Route/page: `GET /app/applications/create/{personalPublicId}`, `GET /app/applications/{applicationPublicId}`.
- Action: isi nama, email, jenis kelamin, status pernikahan, status keluarga, keperluan, dan consent; buat draft; ubah field pada Livewire form lalu simpan/autosave; refresh.
- Expected: form personal tampil, data tetap setelah refresh, status UI `Draft`, tombol `Lanjut ke dokumen` tersedia.
- Expected state: satu `applications` berstatus `DRAFT`; `personal_application_details`, `application_consents`, `chat_threads`, status history awal, audit `application.created`; `price_amount_snapshot` dan `currency` terisi.

### C-07 — Business application dan representative relationship

- Prasyarat: Client B sudah verified/login; C-05 berhasil.
- Account/role: Client B.
- Route/page: `GET /app/applications/create/{businessPublicId}` dan detail application.
- Action: isi nama badan usaha, jenis badan usaha, tujuan, satu penanggung jawab utama dengan relationship yang tersedia (`OWNER`, `DIRECTOR`, `MANAGEMENT`, `EMPLOYEE`, `AUTHORIZED_REPRESENTATIVE`, atau `OTHER`); isi representative tambahan hanya bila semua field wajibnya diisi.
- Expected: application business tersimpan; hubungan representative terlihat; kombinasi representative tambahan tidak lengkap ditolak dengan pesan validasi.
- Expected state: `business_application_details`, satu representative primary, optional additional representative; requirement snapshot business; consent, chat, history, dan audit dibuat.

### C-08 — Requirement snapshot

- Prasyarat: C-06 atau C-07 sudah membuat draft.
- Account/role: Client A atau B.
- Route/page: detail application dan database `application_requirements`.
- Action: catat requirement/kode/batas file yang tampil; perubahan catalog service setelah draft (bila dilakukan pada database development melalui seeder/config yang sah) tidak boleh mengubah snapshot application lama.
- Expected: checklist application tetap berisi requirement saat draft dibuat; Lapor Pajak tidak memiliki snapshot aktif.
- Expected state: row `application_requirements` menyimpan `code`, `name`, required, allowed extension/MIME, max size, condition, sort order; service catalog tidak dibaca ulang untuk mengubah application lama.

### C-09 — Upload dokumen dan validation happy path

- Prasyarat: application personal Client A berstatus `AWAITING_DOCUMENTS`; driver scanner lokal tersedia/diatur sesuai bagian prasyarat; siapkan file sintetis JPG/PNG/PDF valid.
- Account/role: Client A.
- Route/page: detail application; `POST /app/applications/{applicationId}/requirements/{requirementId}/documents`.
- Action: upload satu file valid per KTP, KK, dan foto wajah; amati checklist dan versi file.
- Expected: upload berhasil, file dapat dilihat/diunduh melalui route aplikasi, nama storage random; UI menampilkan versi dan status menunggu pemeriksaan.
- Expected state: physical file berada di private disk, quarantine kosong setelah sukses; `documents` memiliki checksum SHA-256, metadata, `scan_status=PASSED`, `active=true`, `version_number=1`, `review_status=PENDING`; requirement status `PENDING`; audit upload tercatat.

### C-10 — Replace/delete sebelum payment

- Prasyarat: application masih sebelum payment; satu dokumen C-09 aktif.
- Account/role: Client A.
- Route/page: detail application.
- Action: unggah file valid kedua untuk requirement yang sama; gunakan `Hapus` pada versi aktif sebelum payment.
- Expected: replacement tidak overwrite file lama; UI menunjukkan versi baru aktif. Delete hanya boleh pada tahap sebelum payment.
- Expected state: versi lama `active=false` tetap ada; versi baru `version_number=2`, `active=true`; bila dihapus, file aktif dihapus/nonaktif sesuai workflow dan audit tercatat; tidak ada permanent public URL.

### C-11 — Payment-ready dan payment pending

- Prasyarat: seluruh requirement wajib personal valid; set `XENDIT_DRIVER=fake`; `optimize:clear`; C-09 selesai.
- Account/role: Client A.
- Route/page: detail application; `POST /app/applications/{publicId}/payment`.
- Action: tekan bayar; amati redirect dan kembali ke aplikasi.
- Expected: aplikasi melewati `DOCUMENTS_READY_FOR_PAYMENT` ke `AWAITING_PAYMENT`; checkout lokal terbuka dan tidak dianggap pembayaran sukses sebelum tombol simulasi/webhook valid diproses.
- Expected state: `payments` memiliki `PENDING`, `reference_id` unik, amount/currency dari snapshot, expiry, external ID/checkout URL; application belum `PAYMENT_CONFIRMED`, belum `paid_at`, dan dokumen belum terkunci hanya karena browser return.

### C-12 — Webhook payment valid dan submit dokumen

- Prasyarat: C-11 memiliki payment PENDING; callback token lokal di `.env`; gunakan reference, amount, currency, payer email, dan `external_id` yang tersimpan untuk payment tersebut.
- Account/role: local operator untuk webhook, lalu Client A.
- Route/page: `POST /webhooks/xendit`, kemudian detail application.
- Action: kirim event `PAID`/`SETTLED` valid dengan header `x-callback-token` dan `x-event-id` baru; kirim event ID sama sekali lagi; setelah status confirmed, Client A tekan `Kirim dokumen untuk diperiksa`.
- Expected: event valid diterima; event duplikat idempotent; browser return tanpa webhook tidak mengubah payment; client dapat mengirim dokumen setelah confirmed.
- Expected state: payment `PAID`, application `PAYMENT_CONFIRMED` lalu `DOCUMENTS_SUBMITTED`, `paid_at` terisi; `webhook_events` berstatus `PROCESSED`; dokumen client tidak dapat diubah setelah payment; status histories/audit/queued notification tercatat.

### C-13 — Status timeline

- Prasyarat: C-06 sampai C-12 atau application pada status yang lebih lanjut.
- Account/role: Client A.
- Route/page: detail application.
- Action: refresh setelah setiap transition yang dilakukan admin/system; periksa progress indicator dan riwayat.
- Expected: status timeline berurutan dan menggunakan label bahasa sederhana; tidak ada dropdown status untuk client.
- Expected state: setiap perubahan memiliki `from_status`, `to_status`, actor, timestamp, dan reason bila ada pada `application_status_histories`; audit sensitif tercatat.

### C-14 — Revision flow

- Prasyarat: admin sudah mengirim satu requirement ke revision pada A-04.
- Account/role: Client A.
- Route/page: detail application berstatus `REVISION_REQUIRED`.
- Action: baca alasan/instruction; upload replacement hanya untuk requirement yang diminta; coba mengganti dokumen yang tidak diminta; tekan `Kirim perbaikan`.
- Expected: UI memakai label `Perlu perbaikan dokumen`; hanya requirement target terbuka; requirement lain tetap locked; submit tanpa replacement ditolak; submit lengkap mengubah status ke `REVISION_SUBMITTED`.
- Expected state: versi baru requirement target menjadi aktif dengan versi naik; versi lama tetap berhistori; application `REVISION_SUBMITTED`; audit, document review, dan status history tercatat.

### C-15 — Chat client

- Prasyarat: application memiliki `chat_thread`; Client A login; admin dapat login.
- Account/role: Client A lalu Super Admin.
- Route/page: `/app/chat/{threadPublicId}` dan link Chat client dari admin application.
- Action: kirim pesan sintetis dari client; buka sebagai admin; tandai dibaca; balas; refresh/polling.
- Expected: pesan tampil hanya pada thread application terkait; pengirim, waktu, unread/read state terlihat; isi HTML/script tidak dieksekusi.
- Expected state: `chat_messages` berisi sender/body/timestamp; `read_at` dan `read_by_user_id` berubah; notification database dan queued email unread dibuat tanpa attachment sensitif; audit message tercatat.

### C-16 — Result access/download

- Prasyarat: A-08 sampai A-10 selesai dan primary result sudah diverifikasi.
- Account/role: Client A.
- Route/page: detail application; `/app/results/{resultId}/view` dan `/app/results/{resultId}/download`.
- Action: buka dan download primary result yang sudah verified; logout lalu ulangi; gunakan Client B untuk mencoba public ID yang sama.
- Expected: hanya Client A yang dapat view/download hasil verified; Client B/guest ditolak; hasil tidak dikirim sebagai email attachment.
- Expected state: `result_documents.verification_status=VERIFIED`; view/download menambah audit action `VIEW` atau `DOWNLOAD` untuk result; file tetap pada private disk.

## Admin journey

### A-01 — Admin login dan mandatory OTP

- Prasyarat: admin seeded; mail catcher aktif.
- Account/role: `admin@example.test`, `SUPER_ADMIN`.
- Route/page: `GET /admin/login`, lalu `/admin/otp`.
- Action: submit password; ambil OTP dari mail catcher; coba masuk ke `/admin/dashboard` sebelum OTP; submit OTP valid dan logout.
- Expected: password saja tidak memberi full admin session; OTP selalu wajib; route admin setelah OTP berhasil.
- Expected state: challenge type admin, hashed/single-use/expiry/attempt fields; session diregenerasi; login/2FA audit event tercatat.

### A-02 — Application queue dan detail

- Prasyarat: A-01; minimal satu application client sudah dibuat.
- Account/role: Super Admin.
- Route/page: `/admin/dashboard`, `/admin/applications`, `/admin/applications/{publicId}`.
- Action: buka queue dan detail application Client A/B; gunakan public ID valid.
- Expected: queue menampilkan application; detail menampilkan service, user, snapshot requirement, dokumen, status, timeline, dan action sesuai state.
- Expected state: tidak ada data application lain yang berubah hanya karena view; assignment terjadi saat mulai review.

### A-03 — Start review dan document review

- Prasyarat: Client A sudah `DOCUMENTS_SUBMITTED`.
- Account/role: Super Admin.
- Route/page: detail admin; `POST /admin/applications/{publicId}/review/start`; `POST /admin/documents/{documentId}/review`.
- Action: mulai review; buka private document; untuk dokumen valid pilih `Terima`; untuk satu dokumen pada run terpisah pilih `Tolak` atau `Minta perbaikan` dengan reason.
- Expected: review hanya dapat dimulai pada status yang benar; accept mengunci dokumen; reject/revision memerlukan alasan; alasan/instruction terlihat pada client jika revision.
- Expected state: application `UNDER_REVIEW`; `assigned_admin_id` dan thread assignment terisi; accepted doc `LOCKED`, rejected/revision sesuai action; `document_reviews`, audit, reviewer/timestamp terisi.

### A-04 — Revision request

- Prasyarat: application `UNDER_REVIEW`, minimal satu requirement target.
- Account/role: Super Admin.
- Route/page: admin detail, review form.
- Action: pilih `REQUEST_REVISION`, isi reason dan instruction, simpan; coba simpan tanpa reason.
- Expected: request valid mengubah application ke `REVISION_REQUIRED`; request tanpa reason ditolak validasi; hanya requirement target terbuka di sisi client.
- Expected state: document/requirement `REVISION_REQUIRED`; rejection reason/instruction, `document_reviews`, status history, audit, dan notification tersimpan.

### A-05 — Accept all dan finalize review

- Prasyarat: semua dokumen wajib active dan sudah accepted/locked; revision cycle selesai bila ada.
- Account/role: Super Admin.
- Route/page: admin detail; `POST /admin/applications/{publicId}/review/finalize`.
- Action: selesaikan review.
- Expected: application lanjut ke `DOCUMENTS_ACCEPTED` lalu form estimasi tersedia; finalize sebelum semua wajib diterima ditolak.
- Expected state: `documents_accepted_at` terisi; status history/audit transition; client tidak dapat replace/delete dokumen accepted.

### A-06 — Estimate

- Prasyarat: application `DOCUMENTS_ACCEPTED` dan seluruh dokumen wajib accepted.
- Account/role: Super Admin.
- Route/page: admin detail; `POST /admin/applications/{publicId}/estimate`.
- Action: isi tanggal masa depan dan reason; coba tanggal lampau atau reason kosong.
- Expected: data valid mengubah status `ESTIMATE_PENDING` lalu `IN_PROGRESS`; invalid input ditolak.
- Expected state: `estimated_completion_at`, `application_estimate_histories` previous/new value, admin, reason, timestamp; notification estimate dan audit dibuat.

### A-07 — External process status

- Prasyarat: application `IN_PROGRESS`.
- Account/role: Super Admin.
- Route/page: admin detail; `POST /admin/applications/{publicId}/external/waiting`.
- Action: tandai menunggu proses eksternal.
- Expected: status UI menjadi `Menunggu proses eksternal`; tidak ada request ke DJP/Coretax.
- Expected state: application `WAITING_EXTERNAL_PROCESS`, `external_process_started_at`, history/audit; proses eksternal tetap manual di luar aplikasi.

### A-08 — Result upload

- Prasyarat: application `WAITING_EXTERNAL_PROCESS`; scanner lokal tersedia/diatur; file hasil sintetis valid <=15 MB.
- Account/role: Super Admin.
- Route/page: admin detail; `POST /admin/applications/{publicId}/results`.
- Action: upload satu `PRIMARY_RESULT`; boleh upload type result tambahan pada run yang sama/terpisah.
- Expected: file tersimpan private, UI menampilkan hasil pending, application menjadi `RESULT_UPLOADED`.
- Expected state: `result_documents` menyimpan type, metadata, checksum, `scan_status=PASSED`, `verification_status=PENDING`, admin uploader, private storage path; audit upload dan history tercatat.

### A-09 — Result review dan verification

- Prasyarat: application `RESULT_UPLOADED` dengan primary result.
- Account/role: Super Admin.
- Route/page: admin detail; `POST /admin/applications/{publicId}/results/review`, `POST /admin/results/{resultId}/verify`.
- Action: mulai review; tolak hasil tanpa reason sebagai negative; tolak dengan reason; pada run final verifikasi primary result.
- Expected: application `RESULT_REVIEW`; penolakan tanpa reason ditolak; hasil valid menjadi `VERIFIED`.
- Expected state: result `verification_status` berubah, verifier/timestamp/reason tersimpan, audit `result.verification_changed`; client hanya melihat result verified.

### A-10 — Completion dan archive

- Prasyarat: application `RESULT_REVIEW`; minimal satu `PRIMARY_RESULT` verified.
- Account/role: Super Admin.
- Route/page: admin detail; `POST /admin/applications/{publicId}/complete`, lalu archive.
- Action: coba complete sebelum primary result verified pada run terpisah; complete setelah gate terpenuhi; archive application completed.
- Expected: completion sebelum gate ditolak; setelah gate status `COMPLETED`; archive hanya sesudah completed.
- Expected state: `completed_at`, status histories, audit completion; kemudian status `ARCHIVED`; result tetap tunduk pada policy/retention.

### A-11 — Admin chat

- Prasyarat: A-02 application memiliki thread.
- Account/role: Super Admin.
- Route/page: link `Chat client` dari `/admin/applications/{publicId}` menuju `/app/chat/{threadPublicId}`.
- Action: buka thread, baca pesan, balas, dan pastikan link kembali menuju detail admin.
- Expected: admin dapat chat pada thread yang diotorisasi; tidak dapat mengakses thread client lain dengan public ID yang tidak dimiliki bila policy membatasi.
- Expected state: message/read/notification/audit sama seperti C-15; tidak ada upload dokumen resmi melalui chat.

## Security dan negative cases

### N-01 — Guest/client unauthorized routes

- Prasyarat: logout; siapkan URL admin/client dari route list.
- Account/role: guest, lalu Client A.
- Route/page: `/app/dashboard`, `/admin/dashboard`, route view/download document/result.
- Action: buka URL langsung tanpa session; sebagai Client A buka route admin dan coba POST action admin.
- Expected: guest diarahkan login/ditolak; client tidak dapat route atau action admin; tidak ada data sensitif pada response.
- Expected state: tidak ada domain state change; security/audit event hanya bila memang dicatat untuk event tersebut.

### N-02 — Client A mengakses Client B (IDOR)

- Prasyarat: Client A dan B; masing-masing memiliki application/document/thread/result bila diperlukan.
- Account/role: Client A.
- Route/page: semua route memakai public ID milik Client B.
- Action: ganti public ID pada URL GET dan kirim POST/DELETE ke application, document, chat, payment, atau result B.
- Expected: `403`/`404` sesuai boundary tanpa membocorkan keberadaan/data resource; data B tidak tampil/berubah.
- Expected state: tidak ada update pada application/document/payment B; audit security bila endpoint mencatatnya.

### N-03 — Invalid document

- Prasyarat: application masih membuka upload.
- Account/role: Client A.
- Route/page: upload document requirement.
- Action: coba `.php`, `.js`, `.html`, `.svg`, `.zip`, file extension JPG dengan magic bytes bukan gambar, PDF tanpa `%PDF-`/`%%EOF`, PDF dengan active content, dan file melebihi batas requirement.
- Expected: setiap file ditolak dengan pesan sederhana; tidak ada file private/quarantine tertinggal setelah gagal.
- Expected state: tidak ada row document aktif baru; checksum/metadata tidak tersimpan untuk file gagal; audit tidak membocorkan isi dokumen.

### N-04 — Locked document modification

- Prasyarat: application `PAYMENT_CONFIRMED` atau dokumen sudah accepted/locked.
- Account/role: Client A.
- Route/page: upload/delete document route.
- Action: coba replace dan DELETE melalui UI maupun request langsung.
- Expected: action ditolak; UI tidak menampilkan kontrol edit untuk dokumen locked; hanya revision target yang dapat dibuka kembali oleh admin.
- Expected state: active version, review status, checksum, dan storage path tidak berubah.

### N-05 — Invalid state transition/status tampering

- Prasyarat: application pada state awal/menengah.
- Account/role: Client A atau Super Admin sesuai endpoint.
- Route/page: application action routes; tidak ada endpoint arbitrary status.
- Action: kirim field `status` yang bukan transition legal, panggil payment/complete/archive pada state salah, atau ulangi POST action yang sudah selesai.
- Expected: request ditolak; tidak ada lompatan status atau duplikasi history/payment yang tidak sah.
- Expected state: status tetap; transition service hanya membuat history/audit untuk transition legal.

### N-06 — Invalid Xendit webhook

- Prasyarat: payment PENDING lokal; webhook token lokal.
- Account/role: local operator.
- Route/page: `POST /webhooks/xendit`.
- Action: kirim tanpa token/salah token, reference salah, amount/currency/email salah, event ID kosong, status unsupported, lalu event valid yang sama dua kali.
- Expected: invalid request `401`/`422` atau ditolak; payment/application tidak menjadi paid; event valid kedua idempotent.
- Expected state: invalid event tidak mengubah payment; ledger menandai event rejected bila sudah tercatat; duplicate tidak menggandakan history/notification; browser return bukan authority.

### N-07 — Direct public file access

- Prasyarat: minimal satu private document/result tersimpan.
- Account/role: guest atau Client B.
- Route/page: coba `/storage/...`, path storage dari metadata, dan route view/download milik Client A.
- Action: buka URL langsung dan route private tanpa authorization.
- Expected: tidak ada file sensitif yang dapat diambil dari web root; route private mensyaratkan authentication + policy; temporary/permanent public object URL tidak diekspos.
- Expected state: akses yang sah mencatat `VIEW`/`DOWNLOAD`; akses tidak sah tidak membuka file.

### N-08 — XSS pada chat

- Prasyarat: thread chat dapat digunakan.
- Account/role: Client A dan Super Admin.
- Route/page: `/app/chat/{threadPublicId}`.
- Action: kirim teks sintetis seperti `<script>alert(1)</script>` sebagai pesan.
- Expected: teks tampil escaped sebagai teks biasa; tidak ada script berjalan.
- Expected state: body tersimpan sebagai data pesan biasa; tidak ada HTML aktif atau perubahan state lain.

## Teardown dan hasil

- Catat case yang `PASS`, `FAIL`, atau `BLOCKED` beserta public ID dan evidence.
- Hapus test data hanya pada database lokal yang terisolasi. Jangan menjalankan purge terhadap file/data production.
- Kembalikan `XENDIT_DRIVER`, `MALWARE_SCAN_DRIVER`, dan mail configuration ke nilai environment yang benar; jalankan `php artisan optimize:clear`.
- Jangan mengklaim live Xendit, SMTP production, ClamAV production, atau browser acceptance sebagai terverifikasi tanpa konfigurasi dan eksekusi nyata.
