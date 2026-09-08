# Testing Plan

Test wajib mencakup authentication/OTP, ownership policies/IDOR, upload signature/MIME/size/path traversal, document version/review/lock, payment webhook validation/idempotency, transition invalidity, estimate/completion gates, result verification, chat ownership, CSRF/XSS/mass assignment, dan audit/access logs.

Database behavior yang menyentuh migration, foreign key, transaction, locking, constraint, atau database-specific behavior harus diverifikasi pada PostgreSQL yang menjadi database aplikasi; SQLite dan MariaDB/MySQL bukan database runtime aplikasi.

Setiap laporan harus memisahkan test otomatis/headless yang benar-benar dijalankan dari pemeriksaan manual yang `NOT VERIFIED`.

Sebelum menjalankan PHPUnit, jalankan `php artisan optimize:clear`. Configuration cache aplikasi dapat mencegah override environment PHPUnit dan membuat `RefreshDatabase` mencoba mereset database aplikasi; test harus tetap memakai `bantu_daftarin_mvp_test` pada PostgreSQL.
