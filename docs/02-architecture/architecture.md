# Architecture

Bantu Daftarin menggunakan modular monolith Laravel: satu codebase dan deployment, dengan boundary Client, Admin, Webhook, Domain/Service, Database, Private Storage, dan Queue.

- `routes/client.php`, `routes/admin.php`, dan `routes/webhooks.php` memisahkan ingress.
- Controller tipis; Form Request untuk input; Policy untuk authorization; Action/Service untuk workflow; Eloquent untuk persistence; Jobs/Notifications untuk pekerjaan async.
- Database queue digunakan sebagai baseline tanpa menjadikan Redis/WebSocket dependency MVP.
- Database aplikasi dan test menggunakan MariaDB XAMPP 10.4; Laravel connection name `mysql` hanya merujuk pada PDO driver yang dipakai untuk MariaDB.
- Public URL memakai UUID, tetapi semua resource sensitif tetap policy-protected.
- Provider eksternal dibungkus service contract; implementasi Xendit server-side tidak dipakai untuk mengubah state hanya dari browser return.
