# Bantu Daftarin

Bantu Daftarin adalah modular monolith Laravel untuk layanan administratif pembuatan NPWP perseorangan dan badan usaha. Aplikasi mengelola pengumpulan data/dokumen, pembayaran, review admin, komunikasi, dan distribusi hasil secara private.

## Status implementasi

Project ini dibangun sesuai `docs/` dan master prompt. Ledger bukti implementasi dan validasi terakhir ada di [`docs/IMPLEMENTATION_STATUS.md`](docs/IMPLEMENTATION_STATUS.md); jangan menganggap fitur selesai hanya dari keberadaan route atau view.

## Batasan MVP

Tidak ada integrasi DJP/Coretax, pendaftaran otomatis, OCR, AI verification, biometric, atau layanan `Lapor Pajak`. Proses eksternal NPWP dilakukan manual oleh admin di luar aplikasi.

## Quick start lokal

Persyaratan utama: PHP 8.2+, Composer, dan Node/npm bila asset tooling diperlukan. MariaDB 10.4 bawaan XAMPP adalah database development dan application database lokal yang ditetapkan. Laravel tetap memakai koneksi/PDO bernama `mysql` untuk terhubung ke MariaDB.

```powershell
copy .env.example .env
composer install
php artisan key:generate
# isi DB_*, ADMIN_SEED_PASSWORD, XENDIT_*, dan SMTP dengan konfigurasi secret lokal. Gunakan database terisolasi, misalnya bantu_daftarin_mvp:
php artisan migrate --seed
php artisan db:seed --force
php artisan serve
```

Provisioning database/user MariaDB ada di `docs/08-operations/mysql-provisioning.md`. Seeder menolak password admin kosong atau pendek.

Queue lokal dapat dijalankan dengan `php artisan queue:work database`; scheduler dengan `php artisan schedule:work`. Detail konfigurasi, hardening, dan verifikasi ada di `docs/08-operations/` dan `SECURITY.md`.

## Dokumentasi

- `docs/07-testing/manual-acceptance-checklist.md` — checklist acceptance browser MVP, data development aman, startup lokal, dan negative cases.

- `AGENTS.md` — guardrail implementasi.
- `SECURITY.md` — kontrol keamanan dan batasan data.
- `docs/` — product, domain, architecture, database, integration, UX, testing, operations, decisions, dan open questions.
