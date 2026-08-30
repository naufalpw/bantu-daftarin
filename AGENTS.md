# Bantu Daftarin Engineering Guide

## Source of truth

Implementasi mengikuti urutan: master prompt proyek, file ini, aturan bisnis/state machine di `docs/`, lalu implementasi. Konflik atau ambiguitas bisnis tidak boleh diselesaikan dengan asumsi yang memperluas scope; tandai sebagai `[BUSINESS CONFIRMATION REQUIRED]`.

## Scope lock

- MVP hanya `NPWP Perseorangan` dan `NPWP Badan Usaha`.
- `Lapor Pajak` tetap `COMING_SOON` dan harus ditolak backend.
- Tidak ada integrasi DJP/Coretax, OCR, AI, biometric, mobile app, API frontend terpisah, atau WebSocket wajib.
- Role MVP hanya `CLIENT` dan `SUPER_ADMIN`.
- Dokumen sensitif selalu private; jangan menaruh upload di web root atau membuat URL permanen publik.

## Implementation rules

- MariaDB 10.4 bawaan XAMPP/InnoDB/utf8mb4 adalah database aplikasi yang ditetapkan untuk development dan application database lokal. Laravel menggunakan koneksi/PDO bernama `mysql` untuk MariaDB tersebut. Perubahan schema hanya melalui Laravel migration.
- Gunakan Blade + Livewire + Alpine bila perlu, Eloquent, Form Request, Policy, Action/Service, Job/Notification.
- Entity yang tampil pada URL menggunakan `public_id` UUID/ULID, tetapi tetap wajib policy authorization.
- Semua state transition harus melalui rule/action, mencatat actor, timestamp, from/to, reason, history, dan audit jika sensitif.
- Jangan log password, OTP, secret, API key, atau isi dokumen.
- Gunakan synthetic data saja.

## Verification

Dari root aplikasi:

```powershell
php artisan optimize:clear
php artisan test
php artisan migrate:fresh --seed # local/test only; never production
```

`optimize:clear` diperlukan sebelum test agar override environment PHPUnit memilih database test MariaDB terisolasi, bukan configuration cache database aplikasi.

Saat MariaDB XAMPP tidak tersedia, laporkan dengan jelas dan jangan menyamarkan SQLite atau DBMS lain sebagai database aplikasi. Periksa `.env`/secret leakage, private storage, route authorization, state transitions, webhook idempotency, migration, dan seeder.

## Change discipline

Jaga patch tetap terarah. Jangan melakukan reset/checkout destruktif. Perubahan yang belum dapat diverifikasi diberi status `NOT VERIFIED` atau `[BUSINESS CONFIRMATION REQUIRED]` dalam dokumentasi dan laporan.
