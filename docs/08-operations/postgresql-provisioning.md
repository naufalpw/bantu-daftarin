# PostgreSQL Provisioning

PostgreSQL adalah runtime database lokal Bantu Daftarin. XAMPP tetap boleh dipakai untuk Apache/PHP, bukan MariaDB runtime.

| Setting | Nilai |
| --- | --- |
| Host | `127.0.0.1` |
| Port | `5432` |
| Laravel driver | `pgsql` |
| Schema | `public` |
| Runtime database | `bantu_daftarin_mvp` |
| Test database | `bantu_daftarin_mvp_test` |
| Application user | `bantu_daftarin_app` |

Seorang operator PostgreSQL membuat role/database dan memberi privilege sesuai kebijakan lokal. Password hanya disimpan di `.env` atau secret manager, tidak di repository.

Setelah `.env` valid, siapkan schema utama tanpa operasi destruktif:

```powershell
php artisan optimize:clear
php artisan migrate --seed
```

Untuk database test terisolasi saja:

```powershell
copy .env .env.testing
# Ubah APP_ENV=testing dan DB_DATABASE=bantu_daftarin_mvp_test di .env.testing.
# Pertahankan password lokal hanya di file yang di-ignore Git.
php artisan migrate:fresh --seed --env=testing
php artisan test
```

`phpunit.xml` juga memaksa test suite ke `bantu_daftarin_mvp_test`; credentials tetap berasal dari konfigurasi lokal dan tidak boleh ditulis ke repository. Jangan menjalankan `migrate:fresh`, `db:wipe`, atau `migrate:reset` terhadap `bantu_daftarin_mvp`. Database MariaDB lama tetap dipertahankan dan tidak disentuh oleh provisioning PostgreSQL.
