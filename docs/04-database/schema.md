# Database Design

MariaDB 10.4 bawaan XAMPP adalah database development dan application database lokal, menggunakan InnoDB, `utf8mb4`/`utf8mb4_unicode_ci`, dan port 3306. Laravel memakai connection name `mysql` untuk PDO MariaDB. Semua schema change tetap wajib melalui Laravel migration. Migration, constraints, indexes, defaults, JSON fields, dan locking telah diverifikasi pada MariaDB aktif.

Domain tables: authentication (`users`, `admins`, `auth_challenges`, `sessions`), catalog (`services`, `service_requirements`), applications/details/requirements, documents/reviews/access logs, payments/webhooks, workflow histories/consents, chat/notifications, audit, dan queue tables.

Harga dan requirement disnapshot pada application. Foreign-key delete behavior harus eksplisit; riwayat transaksi/audit tidak dihapus karena user dinonaktifkan. Index mengikuti query aktual, termasuk public IDs, ownership/status, reference IDs, thread chronology, dan audit/access chronology.
