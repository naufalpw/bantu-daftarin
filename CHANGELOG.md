# Changelog

## Unreleased - 2026-08-26

- Implemented Laravel 12 modular-monolith foundation for client/admin NPWP workflows.
- Added Laravel migrations, service/requirement snapshots, lifecycle transitions, policies, private document storage, versioning, audit/access logs, queued notifications, chat, result verification, Xendit adapter/webhook ledger, and retention purge command.
- Added synthetic factories and feature/unit coverage. Development and application-database verification follows the authoritative XAMPP MariaDB 10.4 decision.
- Verified migration/seeder on isolated `bantu_daftarin_mvp`, idempotent seeding with cached configuration, isolated database test suite (`73 passed`, `283 assertions`), InnoDB tables, Composer/npm audits, PHP lint, Pint, route/view/config cache, and frontend build.
- Fixed Laravel 12 notification queue-after-commit compatibility, MariaDB timestamp migration compatibility, cached-config-safe admin seeding, representative field validation, and application controller authorization support.
- Fixed the final required document upload flow so the application transitions to payment-ready, renders the payment action, and gives the client an accurate next-step message.
- Fixed logout/login OTP issuance so a previously consumed OTP no longer blocks a new login challenge; cooldown remains enforced for active unused challenges.
- Replaced the fake payment `example.test` placeholder with a same-origin local checkout simulation and preserved the normal webhook validation path.

## Baseline dokumentasi

- Menambahkan baseline dokumentasi produk, arsitektur, keamanan, database, operasi, testing, dan open questions.
