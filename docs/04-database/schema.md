# Database Design

PostgreSQL adalah database development, runtime lokal, dan testing pada `127.0.0.1:5432`, menggunakan schema `public`. Laravel memakai connection name `pgsql`; database runtime bernama `bantu_daftarin_mvp` dan database test bernama `bantu_daftarin_mvp_test`. Semua schema change tetap wajib melalui Laravel migration. Migration PostgreSQL menghindari atribut MySQL-only seperti table charset, collation `utf8mb4_unicode_ci`, dan urutan kolom `after()`.

Domain tables: authentication (`users`, `admins`, `auth_challenges`, `sessions`), catalog (`services`, `service_requirements`), applications/details/requirements, documents/reviews/access logs, payments/webhooks, workflow histories/consents, chat/notifications, audit, dan queue tables.

Harga dan requirement disnapshot pada application. Foreign-key delete behavior harus eksplisit; riwayat transaksi/audit tidak dihapus karena user dinonaktifkan. Index mengikuti query aktual, termasuk public IDs, ownership/status, reference IDs, thread chronology, dan audit/access chronology.
