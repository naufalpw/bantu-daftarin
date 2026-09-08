# Architecture

Bantu Daftarin menggunakan modular monolith Laravel: satu codebase dan deployment, dengan boundary Client, Admin, Webhook, Domain/Service, Database, Private Storage, dan Queue.

- `routes/client.php`, `routes/admin.php`, dan `routes/webhooks.php` memisahkan ingress.
- Controller tipis; Form Request untuk input; Policy untuk authorization; Action/Service untuk workflow; Eloquent untuk persistence; Jobs/Notifications untuk pekerjaan async.
- Database queue digunakan sebagai baseline tanpa menjadikan Redis/WebSocket dependency MVP.
- Database aplikasi dan test menggunakan PostgreSQL pada `127.0.0.1:5432`; Laravel connection name `pgsql`, schema `public`, database runtime `bantu_daftarin_mvp`, dan database test `bantu_daftarin_mvp_test`. XAMPP tetap dapat menyediakan Apache/PHP, bukan database runtime.
- Public URL memakai UUID, tetapi semua resource sensitif tetap policy-protected.
- Provider eksternal dibungkus service contract; implementasi Xendit server-side tidak dipakai untuk mengubah state hanya dari browser return.
