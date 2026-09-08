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

- PostgreSQL pada `127.0.0.1:5432` adalah database aplikasi lokal dan testing. Laravel menggunakan koneksi/PDO bernama `pgsql`, schema `public`, database `bantu_daftarin_mvp`, dan database test `bantu_daftarin_mvp_test`. XAMPP tetap boleh dipakai untuk Apache/PHP, tetapi MariaDB/MySQL bukan runtime database aplikasi. Perubahan schema hanya melalui Laravel migration.
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
php artisan migrate:fresh --seed --env=testing # test database only; never runtime/production
```

`optimize:clear` diperlukan sebelum test agar override environment PHPUnit memilih database test PostgreSQL terisolasi, bukan configuration cache database aplikasi.

Saat PostgreSQL lokal tidak tersedia, laporkan dengan jelas dan jangan menyamarkan SQLite atau DBMS lain sebagai database aplikasi. Periksa `.env`/secret leakage, private storage, route authorization, state transitions, webhook idempotency, migration, dan seeder.

## Change discipline

Jaga patch tetap terarah. Jangan melakukan reset/checkout destruktif. Perubahan yang belum dapat diverifikasi diberi status `NOT VERIFIED` atau `[BUSINESS CONFIRMATION REQUIRED]` dalam dokumentasi dan laporan.

<!-- antislop:start -->
## UI/UX task routing

For every UI/UX, visual design, copy-in-interface, responsive, or Figma-to-code task, read these files in this order before planning or changing anything:

1. `DESIGN.md` first. It is the product-specific source of design direction and controls intentional Bantu Daftarin UX choices.
2. `ANTISLOP.md` second. It is a quality filter for generic, dishonest, inaccessible, or unfinished output. It is not a style guide and must not replace `DESIGN.md`.

When visual reference is needed, use Figma MCP to inspect the verified Bantu Daftarin file and the relevant route/node. Use Figma for visual language, components, measurements, and approved assets. Do not let Figma invent routes, information architecture, product capabilities, lifecycle transitions, or security behavior. Follow the product scope and `docs/` when visual reference conflicts with them.
<!-- antislop:end -->
