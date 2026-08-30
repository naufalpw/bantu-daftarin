# Operations Runbook

Development memakai Apache dan MariaDB yang dibundel XAMPP. Laravel connection name `mysql` digunakan untuk MariaDB tersebut. Production membutuhkan HTTPS, `APP_DEBUG=false`, secure cookies, private storage, least-privilege DB, queue worker, scheduler, encrypted backup, monitoring, dan error handling tanpa stack trace.

Perintah production migration: `php artisan migrate --force`. Jangan memakai `migrate:fresh`, `db:wipe`, atau `migrate:reset` di production. Retention purge menghapus physical file setelah `retention_until`; durasi final menunggu konfirmasi bisnis/legal.
