# Security Baseline

Data aplikasi mencakup identitas, dokumen, pembayaran, komunikasi, dan hasil layanan. Kontrol keamanan adalah bagian dari acceptance criteria.

- Authentication memakai Laravel hashing, email verification, dan email OTP single-use yang disimpan ter-hash; payload queue OTP hanya menyimpan ciphertext untuk delivery email.
- Authorization memakai policy pada application, document, payment, result document, dan chat thread; public ID tidak menggantikan policy.
- Upload hanya JPG/JPEG/PNG/PDF dengan validasi extension, MIME, signature, ukuran, checksum, parser, dan abstraction malware scan. File berada di private/quarantine disk.
- Akses dokumen melalui route terotorisasi, dicatat pada access log, dan memakai response stream/temporary access; tidak ada permanent public URL.
- Webhook pembayaran memverifikasi callback token, reference, nominal, currency, idempotency ledger, dan transaksi database.
- Password, OTP, secret, isi file, dan confidential webhook data tidak dicatat ke log/email.
- `APP_DEBUG=false`, cookie secure di production, HTTPS, least-privilege database account, backup encrypted, queue/scheduler, dan monitoring wajib untuk production.
- Database lokal memakai PostgreSQL dengan koneksi Laravel `pgsql`, schema `public`, dan least-privilege user aplikasi. Private storage tetap wajib dipertahankan; MariaDB/MySQL tidak digunakan sebagai runtime database aplikasi.

Retensi final dokumen: `[BUSINESS AND LEGAL CONFIRMATION REQUIRED]`. Infrastruktur `retention_until`, `deletion_scheduled_at`, `deleted_at`, dan purge fisik disiapkan tanpa menetapkan durasi legal secara sepihak.
