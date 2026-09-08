# Operations Runbook

Development dapat memakai Apache/PHP dari XAMPP, tetapi memakai PostgreSQL pada `127.0.0.1:5432` sebagai database runtime. Laravel connection name `pgsql` menggunakan schema `public`, database `bantu_daftarin_mvp`, dan user least-privilege `bantu_daftarin_app`. Production membutuhkan HTTPS, `APP_DEBUG=false`, secure cookies, private storage, least-privilege DB, queue worker, scheduler, encrypted backup, monitoring, dan error handling tanpa stack trace.

Queue worker wajib memproses delayed database jobs. Chat unread email dijadwalkan per penerima dan thread dengan delay singkat, lalu memeriksa kembali unread state saat dieksekusi. Job yang menemukan percakapan sudah dibaca selesai tanpa mengirim email.

## Kesiapan email production

Sebelum mengaktifkan delivery email production, verifikasi checklist berikut. Status DNS tidak dapat diverifikasi dari environment lokal ini.

- [ ] `MAIL_FROM_ADDRESS` dan `MAIL_FROM_NAME` production sudah ditetapkan.
- [ ] Domain `MAIL_FROM_ADDRESS` selaras dengan domain pengirim SMTP/provider.
- [ ] SPF sudah dikonfigurasi.
- [ ] DKIM sudah dikonfigurasi.
- [ ] DMARC sudah dikonfigurasi.
- [ ] Kredensial SMTP/provider tervalidasi tanpa dicatat di repository atau log.
- [ ] Mailbox Reply-To yang dipantau sudah dikonfirmasi bila akan digunakan.
- [ ] Worker database queue production aktif dan memproses delayed job.
- [ ] Monitoring failed job serta prosedur retry telah ditetapkan.
- [ ] Delivery uji ke Gmail dan Outlook sudah diperiksa, termasuk folder spam.
- [ ] Tampilan mobile, mode gelap, gambar diblokir, dan preview inbox sudah diperiksa.

SPF, DKIM, DMARC, serta rendering pada klien inbox eksternal: **NOT VERIFIED LOCALLY**. Jangan menjadikan mailer `log` sebagai pengganti aman untuk OTP pada environment yang dapat diakses pihak lain, karena isi email dapat tertulis ke log.

Perintah production migration: `php artisan migrate --force`. Jangan memakai `migrate:fresh`, `db:wipe`, atau `migrate:reset` di production. Retention purge menghapus physical file setelah `retention_until`; durasi final menunggu konfirmasi bisnis/legal.
