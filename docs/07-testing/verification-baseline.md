# Verification Baseline

## PostgreSQL runtime migration — 2026-09-06

- Runtime connection verified through Laravel as `pgsql`, database `bantu_daftarin_mvp`, user `bantu_daftarin_app`.
- PostgreSQL migration and seeder completed with all seven migrations `Ran` on `bantu_daftarin_mvp`.
- PostgreSQL test database `bantu_daftarin_mvp_test` completed `migrate:fresh --seed`; full suite passed: 231 tests and 1331 assertions.
- MariaDB verification below is historical evidence only; MariaDB/MySQL is no longer the runtime or test database.

Tanggal pemeriksaan: 2026-08-26.

## Berhasil pada checkout ini

- PHP lint untuk `app`, `database`, `config`, `routes`, dan `tests`.
- `vendor/bin/pint --test`.
- `php artisan route:list --except-vendor`.
- `php artisan route:cache`.
- `php artisan view:cache`.
- Unit tests dan smoke feature test yang tidak membutuhkan database.
- `npm run build`.
- `npm audit --audit-level=high`.
- `composer validate --no-check-publish`.

`composer audit --no-dev` berhasil dan tidak menemukan security advisory.

## Belum terverifikasi

- Rollback migration penuh pada database operasional; `down()` sudah tersedia dan destructive rollback tidak dijalankan terhadap database aplikasi berisi data.
- Webhook/payment transaction provider live; database transaction dan locking tetap memerlukan verifikasi PostgreSQL automated test terbaru.
- Upload, quarantine, ClamAV, purge fisik, dan private streaming pada environment operasional.
- SMTP, worker queue, scheduler, dan Xendit live callback.
- Browser/manual interactive acceptance test dan pemetaan frame Figma.

Catatan berikutnya adalah baseline historis MariaDB sebelum ADR-0003 dan bukan kontrak runtime saat ini.

## Hasil verifikasi database terbaru

- `php artisan migrate --seed`: berhasil pada `bantu_daftarin_mvp`.
- `php artisan migrate:fresh --seed --force`: berhasil pada database test terisolasi `bantu_daftarin_mvp_test` di MariaDB XAMPP.
- `php artisan migrate:status`: 4 migration berstatus `Ran`.
- Seeder: 1 synthetic admin, 2 service ACTIVE, dan 1 service COMING_SOON tanpa harga.
- Full test suite: `70 passed`, `265 assertions` pada database test terisolasi.
- Seeder idempotent: `php artisan config:cache` kemudian `php artisan db:seed --force` berhasil.
- Seluruh tabel aplikasi terverifikasi InnoDB dengan collation `utf8mb4_unicode_ci`; metadata menunjukkan 34 foreign-key constraints dan 0 tabel non-InnoDB/non-target-collation.
