# Operations Runbook

Development dapat memakai Apache/PHP dari XAMPP, tetapi memakai PostgreSQL pada `127.0.0.1:5432` sebagai database runtime. Laravel connection name `pgsql` menggunakan schema `public`, database `bantu_daftarin_mvp`, dan user least-privilege `bantu_daftarin_app`. Production membutuhkan HTTPS, `APP_DEBUG=false`, secure cookies, private storage, least-privilege DB, queue worker, scheduler, encrypted backup, monitoring, dan error handling tanpa stack trace.

Dokumen ini menjelaskan prosedur. Checklist atau konfigurasi repository bukan bukti bahwa kontrol staging/production sedang berjalan. Simpan hasil acceptance yang sudah disunting agar tidak berisi secret, OTP, tautan privat, isi dokumen, atau data klien.

## Production deployment gate

Jalankan dengan konfigurasi deployment efektif sebelum migrasi atau menerima traffic:

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan security:check-production
php artisan security:check-malware
php artisan migrate --force
php artisan view:cache
```

Kedua perintah `security:check-*` harus berakhir dengan exit code `0`. Hentikan deployment jika ada `FAIL`, `NOT_AVAILABLE`, atau `NOT_VERIFIABLE` pada kontrol yang harus dibuktikan oleh runtime tersebut. `security:check-production` tidak mencetak nilai secret; jangan menggantinya dengan `config:show` dalam log deployment.

Minimum effective configuration:

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, dan `APP_KEY` berasal dari secret manager.
- `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` atau `strict`, dan session driver server-side.
- `MAIL_MAILER` menggunakan transport delivery production. `log`, `array`, atau failover yang memuat salah satunya tidak boleh digunakan.
- `QUEUE_CONNECTION` asynchronous; `sync`, `null`, dan `deferred` tidak diterima.
- `FILESYSTEM_DISK=private`; private dan quarantine harus berada di luar web root, tidak memiliki public mapping, dan tidak serve file secara langsung.
- `MALWARE_SCAN_DRIVER=clamav`; testing scanner dilarang dan gagal saat di-resolve pada production.
- `DOCUMENT_RETENTION_DAYS` hanya diisi setelah periode retensi dokumen disetujui. Tanpa nilai ini, production upload memang gagal tertutup.
- `XENDIT_DRIVER=xendit`; secret key dan callback token tersedia dari secret manager.
- `DB_CONNECTION=pgsql`; active production log channel tidak menggunakan level `debug`.

Perintah production migration hanya `php artisan migrate --force`. Jangan memakai `migrate:fresh`, `db:wipe`, atau `migrate:reset` di production.

## TLS, proxy, browser headers, dan HSTS

TLS terminator/reverse proxy adalah authority untuk redirect HTTP ke HTTPS dan HSTS. Laravel tidak mengirim HSTS pada local HTTP. Konfigurasikan trusted proxy/forwarded protocol di deployment sehingga Laravel mengenali request asli sebagai HTTPS.

Verifikasi dari jaringan eksternal:

```powershell
curl.exe -sS -I http://production-host.example
curl.exe -sS -I https://production-host.example
```

Acceptance:

- HTTP mengalihkan ke URL HTTPS yang benar tanpa redirect loop.
- HTTPS memakai sertifikat valid untuk hostname dan chain dipercaya.
- Session cookie memiliki `Secure`, `HttpOnly`, dan `SameSite=Lax` atau `Strict`.
- Response memiliki CSP dengan `script-src 'self'` tanpa `unsafe-inline`, serta `frame-ancestors 'none'`, `object-src 'none'`, `base-uri 'self'`, dan `form-action 'self'`.
- Response memiliki `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, dan permissions policy yang disetujui.
- TLS layer mengirim `Strict-Transport-Security` dengan `max-age` yang disetujui operasi. Jangan menambahkan `includeSubDomains` atau `preload` sebelum seluruh subdomain, sertifikat, redirect, dan recovery procedure terbukti siap.

Uji browser pada landing, login/OTP, workspace Personal dan Business, admin, serta Livewire. Pastikan tidak ada CSP violation di console dan perilaku countdown, conditional fields, dialog, upload autosubmit, kamera, Alpine, Livewire, polling, serta toast tetap bekerja.

## ClamAV acceptance

Installasi engine/daemon dan update signature adalah tanggung jawab host, bukan Composer/npm. Setelah daemon siap:

```powershell
clamdscan --version
php artisan security:check-malware
```

`security:check-malware` menjalankan pemeriksaan terbatas: command responsiveness, umur signature, satu file bersih sintetis, dan standard synthetic antivirus test sample. Sample sementara dihapus setelah pemeriksaan. Acceptance membutuhkan seluruh check `PASS`. Konfigurasikan `CLAMAV_SIGNATURE_MAX_AGE_HOURS` sesuai SLA update signature.

Monitor daemon/socket atau command tidak tersedia, signature melewati umur maksimum, clean sample ditolak, synthetic antivirus sample tidak terdeteksi, dan antrean upload gagal karena scanner unavailable.

Jangan mengganti production scanner ke `testing` untuk memulihkan availability. Perbaiki engine atau hentikan upload; pipeline sengaja gagal tertutup.

## Queue worker

Queue worker wajib memproses delayed database jobs. Chat unread email dijadwalkan per penerima dan thread dengan delay singkat, lalu memeriksa kembali unread state saat dieksekusi. Job yang menemukan percakapan sudah dibaca selesai tanpa mengirim email.

Contoh proses worker yang harus dikelola Supervisor/systemd/service manager dan dimulai ulang saat deploy:

```powershell
php artisan queue:restart
php artisan queue:work database --sleep=3 --tries=3 --timeout=60 --max-time=3600
php artisan queue:failed
```

Acceptance membutuhkan synthetic queued notification yang benar-benar diproses, worker restart yang berhasil, queue lag/failure threshold, alert, dan prosedur retry. Jangan menjalankan worker jangka panjang dari shell interaktif sebagai konfigurasi production final.

## Scheduler

Jalankan Laravel scheduler setiap menit melalui scheduler OS:

```powershell
php artisan schedule:list
php artisan schedule:run
```

Monitor timestamp keberhasilan terakhir dan alert bila scheduler melewati ambang operasional. `files:purge` dijadwalkan harian dan menghapus physical file hanya setelah `retention_until`; jalankan dry-run dan verifikasi audit trail sebelum acceptance:

```powershell
php artisan files:purge --dry-run
```

Periode retensi dokumen final membutuhkan persetujuan bisnis/legal.

## Provider payload retention boundary

`payments:prune-payloads` tidak dijadwalkan otomatis. Durasi retensi provider/webhook masih `RETENTION PERIOD DEFERRED — BUSINESS/OPERATIONS CONFIRMATION REQUIRED`.

Setelah periode disetujui, operator wajib melakukan dry-run dengan hari eksplisit:

```powershell
php artisan payments:prune-payloads --days=<approved-days>
```

Eksekusi destruktif hanya setelah hasil dry-run, backup, dispute/tax/legal constraints, dan approval diverifikasi:

```powershell
php artisan payments:prune-payloads --days=<approved-days> --force
```

Command menolak nilai di bawah safety guard 30 hari. Guard tersebut bukan kebijakan retensi yang disetujui. Payload event transient/retryable yang masih diperlukan tidak boleh dipangkas.

## Private storage acceptance

Verifikasi sebagai user proses aplikasi dan web server:

- root private dan quarantine bukan child dari document root;
- tidak ada symlink/public alias menuju kedua root;
- web server tidak dapat menyajikan path storage secara langsung;
- hanya user proses aplikasi yang memiliki permission minimum yang diperlukan;
- upload bersih berpindah quarantine ke private;
- upload infected/unavailable tetap ditolak dan quarantine dibersihkan;
- policy-gated stream/download tetap mencatat audit akses;
- disk usage dan kegagalan I/O memiliki alert.

Jangan menyalin isi dokumen atau original filename ke acceptance report.

## PostgreSQL acceptance

Gunakan role aplikasi non-superuser yang hanya memiliki grant minimum pada database/schema aplikasi. Verifikasi tanpa mencetak password:

```sql
SELECT current_database(), current_user;
SELECT rolsuper, rolcreaterole, rolcreatedb, rolreplication
FROM pg_roles
WHERE rolname = current_user;
SHOW ssl;
```

Acceptance juga mencakup network allowlist/firewall, TLS DB bila melintasi host/network, koneksi worker, batas koneksi, slow-query/availability alerts, serta larangan akses role aplikasi ke database lain yang tidak diperlukan.

## Backup and restore drill

Backup wajib terenkripsi, akses terbatas, dipantau, dan memiliki retention yang disetujui. Simpan `APP_KEY`/secret manager backup terpisah dari database ciphertext; kehilangan key dapat membuat encrypted casts tidak dapat dipulihkan, sedangkan penyimpanan key bersama backup mengurangi perlindungan.

Contoh backup custom-format:

```powershell
pg_dump --format=custom --file=<encrypted-staging-path> <production-database>
```

Restore drill harus memakai database terisolasi yang baru, bukan production:

```powershell
createdb <isolated-restore-database>
pg_restore --exit-on-error --clean --if-exists --dbname=<isolated-restore-database> <decrypted-backup-path>
```

Catat waktu backup/restore, checksum artefak, jumlah migration, smoke-test sintetis, dan pemusnahan database drill. Jangan mencatat credential atau data klien. Restore yang belum benar-benar dijalankan berstatus `NOT_TESTED`, bukan PASS.

## Logging and monitoring

- Batasi ACL log, gunakan rotation/retention yang disetujui, dan jangan gunakan debug level pada production.
- Jangan log password, OTP, reset/verification URLs, authorization/callback headers, Xendit secret, isi dokumen, NIK/KK, atau raw provider payload.
- Setelah synthetic email acceptance, cari fixture OTP/reset/verification yang sama pada retained application/centralized logs. Hasil harus negatif. Jangan menaruh fixture asli dalam laporan permanen.
- Alert minimum: repeated login/OTP failures, webhook authentication/validation failures, queue lag/failed jobs, scheduler stale, ClamAV unavailable/stale, storage capacity/I/O failure, PostgreSQL availability, dan lonjakan error 5xx.
- Uji satu alert sintetis per channel dan record acknowledgment/escalation. Dashboard yang sekadar dikonfigurasi tanpa alert delivery test berstatus `NOT_TESTED`.

## Kesiapan email production

Sebelum mengaktifkan delivery email production, verifikasi checklist berikut. Status DNS tidak dapat diverifikasi dari environment lokal ini.

- [ ] `MAIL_FROM_ADDRESS` dan `MAIL_FROM_NAME` production sudah ditetapkan.
- [ ] Domain `MAIL_FROM_ADDRESS` selaras dengan domain pengirim SMTP/provider.
- [ ] SPF sudah dikonfigurasi.
- [ ] DKIM sudah dikonfigurasi.
- [ ] DMARC sudah dikonfigurasi.
- [ ] Kredensial SMTP/provider tervalidasi tanpa dicatat di repository atau log.
- [ ] Mailbox Reply-To yang dipantau sudah dikonfirmasi bila akan digunakan.
- [ ] Worker database queue production aktif dan memproses delayed job.
- [ ] Monitoring failed job serta prosedur retry telah ditetapkan.
- [ ] Delivery uji ke Gmail dan Outlook sudah diperiksa, termasuk folder spam.
- [ ] Tampilan mobile, mode gelap, gambar diblokir, dan preview inbox sudah diperiksa.
- [ ] Synthetic OTP/reset/verification fixture tidak muncul di application/centralized logs.

SPF, DKIM, DMARC, delivery, serta rendering pada klien inbox eksternal: **NOT VERIFIED LOCALLY**. Jangan menjadikan mailer `log` sebagai pengganti aman untuk OTP pada environment yang dapat diakses pihak lain, karena isi email dapat tertulis ke log.
