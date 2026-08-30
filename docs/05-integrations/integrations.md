# Integrations

## Xendit

Server-side official SDK/provider. Payment menjadi `PAID` hanya setelah webhook callback-token, payload, reference, amount, currency, identity, dan idempotency tervalidasi di dalam transaction. `webhook_events` adalah event ledger.

## Mail

SMTP/provider melalui Laravel Mail/Notifications. Email verification, OTP, payment/status/revision/estimate/completion/unread-chat notifications di-queue. Tidak ada dokumen sensitif atau result sebagai attachment.

## External NPWP process

Manual admin process di luar aplikasi. Tidak ada API DJP/Coretax atau credential/OTP pemerintah.
