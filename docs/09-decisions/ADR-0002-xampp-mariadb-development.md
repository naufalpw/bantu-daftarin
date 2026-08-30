# ADR-0002: Gunakan MariaDB XAMPP untuk Development Lokal

Tanggal: 2026-08-26  
Status: Accepted and authoritative for local development and the local application database

## Konteks

Environment Windows yang tersedia menjalankan XAMPP dengan MariaDB 10.4.32 pada port 3306. Mengganti environment dengan mengunduh server database lain menambah beban setup lokal.

## Keputusan

Development, application database, dan automated test database menggunakan MariaDB XAMPP yang aktif. Laravel tetap memakai connection/PDO name `mysql`, InnoDB, UTF-8, dan collation `utf8mb4_unicode_ci` yang tersedia pada MariaDB 10.4.

Database aplikasi dan database test tetap dipisahkan. Seluruh perubahan schema tetap melalui migration Laravel. Credential lokal tidak boleh dipakai sebagai credential production.

Database legacy `bantu_daftarin` terdeteksi sudah berisi schema proyek lama, sehingga environment lokal memakai database baru yang terisolasi: `bantu_daftarin_mvp` dan `bantu_daftarin_mvp_test`. Schema legacy tidak dihapus atau diubah.

Keputusan ini menggantikan persyaratan database sebelumnya yang tidak sesuai dengan environment XAMPP. Jangan mengganti database authoritative ini tanpa ADR baru.

## Konsekuensi

- Migration dan test database dapat diverifikasi langsung pada environment pengguna.
- Schema menggunakan fitur Laravel/SQL yang didukung MariaDB 10.4; compatibility diverifikasi langsung pada server XAMPP aktif.
- Deployment di luar environment ini harus tetap menggunakan MariaDB yang kompatibel dengan schema dan connection contract aplikasi, bukan mengganti database secara diam-diam.
- MariaDB XAMPP bukan integrasi pemerintah dan tidak mengubah business scope aplikasi.
