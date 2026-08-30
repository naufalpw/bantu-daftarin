# Security Design

## Authentication and session

Client: register → email verification → login → email OTP → authenticated session. Admin selalu memerlukan password dan email OTP sebelum full session. OTP 6 digit, secure random, hashed, single-use, expiry, attempt limit, cooldown, rate limit, dan temporary lockout.

## Authorization

Policy checks wajib pada application, document, payment, result document, dan chat thread. Client hanya dapat mengakses resource miliknya. Admin action juga diaudit.

## Files

Private disk/quarantine, random stored names, metadata/checksum, server-side MIME/signature/parser checks, malware-scan abstraction, versioning, temporary audited stream, dan physical purge architecture.

## Audit

Audit login/security events, 2FA, payment/webhook, transition, review/revision, estimate, result, completion, deletion, serta document view/download/signed access. Jangan log secret, OTP, password, isi dokumen, atau raw confidential values.
