# Integrations

## Xendit

Server-side official SDK/provider. Payment menjadi `PAID` hanya setelah webhook callback-token, payload, reference, amount, currency, identity, dan idempotency tervalidasi di dalam transaction. `webhook_events` adalah event ledger.

## Mail

SMTP/provider melalui Laravel Mail/Notifications. Outbound notification yang bergantung pada state domain baru menggunakan queue dengan dispatch setelah database commit. Email verification, OTP, payment/status/revision/estimate/completion/unread-chat notifications di-queue. Tidak ada dokumen sensitif atau result sebagai attachment.

Chat unread email memakai satu opportunity tertunda selama 60 detik untuk setiap pasangan penerima dan thread. Saat job berjalan, aplikasi memeriksa ulang unread incoming message yang aktif. Jika percakapan sudah dibaca, email tidak dikirim. Application support dan general support memakai deep link ke route chat yang tetap memerlukan autentikasi serta authorization. Thread aplikasi tanpa admin ter-assign tidak melakukan fallback email ke seluruh admin. Claim delivery mencegah duplicate dispatch dari retry job; delivery bersifat best-effort at-most-once per opportunity, bukan jaminan exactly-once sampai SMTP recipient.

### Presentasi email transaksional

Semua email aktif menggunakan shell Blade milik proyek dengan HTML email yang konservatif dan pasangan plain text. Shell memakai preheader eksplisit, wordmark dengan alt text, warna inline untuk bagian penting, serta CTA berbasis tabel agar tetap terbaca saat CSS atau gambar dibatasi oleh klien email. Informasi penting seperti kode OTP, status, konteks pengajuan, dan CTA selalu berupa teks, bukan isi gambar.

Preheader security email tidak boleh memuat OTP, token reset password, hash verifikasi, signed URL, NIK, KK, URL dokumen private, atau identifier internal. Subject dan copy memakai istilah produk Indonesia seperti `pengajuan`, `pembayaran`, dan `hasil`.

Email aktif: OTP login, verifikasi email, atur ulang kata sandi, pesan chat belum dibaca, status pengajuan, estimasi, pembayaran diterima, dan hasil tersedia. Email chat tidak memuat isi pesan atau transkrip; CTA memakai deep link chat yang sama dengan Phase 1 dan tetap melewati autentikasi serta policy. Reply-To tidak diatur per template sampai ada mailbox operasional yang terverifikasi.

Keterbatasan dark mode klien email berbeda-beda. Shell menyatakan dukungan light/dark dan menggunakan warna eksplisit, tetapi hasil akhir pada Gmail, Outlook, dan Apple Mail tetap memerlukan QA inbox sebelum rilis.

## External NPWP process

Manual admin process di luar aplikasi. Tidak ada API DJP/Coretax atau credential/OTP pemerintah.
