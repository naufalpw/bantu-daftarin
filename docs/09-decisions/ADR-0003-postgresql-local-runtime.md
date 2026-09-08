# ADR-0003: Gunakan PostgreSQL untuk Runtime Lokal dan Testing

Tanggal: 2026-09-06  
Status: Accepted

## Konteks

Runtime MariaDB lokal sebelumnya mengalami masalah privilege dan bukan lagi pilihan database aplikasi. PostgreSQL 17 tersedia secara lokal pada `127.0.0.1:5432`; PHP XAMPP memuat `pdo_pgsql` dan `pgsql`.

## Keputusan

Laravel menggunakan connection `pgsql` dengan schema `public`. Database runtime adalah `bantu_daftarin_mvp`; database test terisolasi adalah `bantu_daftarin_mvp_test`; keduanya memakai user aplikasi least-privilege `bantu_daftarin_app`.

XAMPP tetap boleh menyediakan Apache dan PHP. MariaDB/MySQL tidak digunakan oleh runtime atau automated test, tetapi database dan data MariaDB lama dipertahankan sebagai backup/rollback reference dan tidak dihapus oleh perubahan ini.

## Konsekuensi

- Semua schema tetap dikelola Laravel migration; database utama hanya menggunakan `php artisan migrate --seed` dan tidak pernah `migrate:fresh`.
- Migration baru dan historis harus menghindari atribut MySQL-only seperti charset table, collation `utf8mb4_unicode_ci`, dan `after()`.
- PHPUnit memakai PostgreSQL test database; `migrate:fresh --seed` hanya boleh diarahkan ke `bantu_daftarin_mvp_test`.
- Perilaku query yang bergantung pada pencarian case-insensitive menggunakan API Laravel yang mengompilasi tepat untuk PostgreSQL.
