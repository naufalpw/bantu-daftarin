# Database Provisioning (Authoritative XAMPP MariaDB)

Provisioning database/user dilakukan sebelum Laravel migration dan bukan bagian dari migration:

```sql
CREATE DATABASE bantu_daftarin_mvp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE bantu_daftarin_mvp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bantu_daftarin_app'@'127.0.0.1' IDENTIFIED BY 'CHANGE_THIS_OUTSIDE_REPOSITORY';
CREATE USER 'bantu_daftarin_migrator'@'127.0.0.1' IDENTIFIED BY 'CHANGE_THIS_OUTSIDE_REPOSITORY';
GRANT SELECT, INSERT, UPDATE, DELETE ON bantu_daftarin_mvp.* TO 'bantu_daftarin_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON bantu_daftarin_mvp_test.* TO 'bantu_daftarin_app'@'127.0.0.1';
GRANT ALL PRIVILEGES ON bantu_daftarin_mvp.* TO 'bantu_daftarin_migrator'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Untuk development ini gunakan MariaDB 10.4 dari XAMPP pada port 3306. Jangan menjalankan SQL schema manual; SQL di atas hanya provisioning database/user, lalu schema dibangun dengan `php artisan migrate --seed`. Production application memakai user `bantu_daftarin_app`; migration dijalankan oleh operator menggunakan credential migrator terpisah. Password database hanya disimpan di `.env`/secret manager.

Pada checkout ini database aplikasi dan test memakai `bantu_daftarin_mvp` serta `bantu_daftarin_mvp_test`. Database legacy `bantu_daftarin` tidak disentuh. Pada environment ini kedua database memakai MariaDB XAMPP yang sama, tetapi tetap terisolasi; ganti nama database sesuai environment deployment tanpa mengubah schema melalui SQL manual.
