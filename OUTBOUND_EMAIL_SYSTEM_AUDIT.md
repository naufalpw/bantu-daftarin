# BantuDaftarin Outbound Email System Audit

**Status:** Read-only audit complete with runtime/configuration uncertainties  
**Date:** 2026-09-07  
**Scope:** Discovery, content inventory, trigger tracing, queue/security review.  
**Changes made during audit:** None. No email was sent.

## 1. Audit scope

Inspected authentication, OTP, verification, password reset, chat, application workflow, payment/webhook, result workflow, document workflow, scheduler, mail/queue configuration, notifications, tests, and email entry points.

Search covered `app/Notifications`, `app/Mail`, `app/Jobs`, `app/Events`, `app/Listeners`, `app/Services`, `app/Actions`, `app/Livewire`, controllers, routes, config, resources, database, and tests.

## 2. Total active email flows

- 6 active notification implementations.
- 9 logical active flow variants.
- 8 unique subject strings.

The nine logical flows are:

1. Admin login OTP
2. Client login OTP
3. Client email verification
4. Password reset for client/admin
5. Unread chat notification
6. Application status update
7. Estimate update
8. Payment confirmed
9. Result available

## 3. Email inventory matrix

| ID | Flow | Active | Recipient | Trigger | Queue | Template | Priority |
|---|---|---:|---|---|---|---|---|
| EMAIL-01 | Admin login OTP | Yes | Admin | Successful password step, OTP issued | `ShouldQueue` | Default Laravel `MailMessage` | P1 |
| EMAIL-02 | Client login OTP | Yes | Client | Successful password step, OTP issued | `ShouldQueue` | Default Laravel `MailMessage` | P1 |
| EMAIL-03 | Email verification | Yes | New/unverified client | Registration or resend | `ShouldQueue` | Default Laravel `MailMessage` | P2 |
| EMAIL-04 | Password reset | Yes | Client/admin | Password reset request | `ShouldQueue` | Default Laravel `MailMessage` | P2 |
| EMAIL-05 | Unread chat | Yes | Client or admin | New chat message | `ShouldQueue` | Default Laravel `MailMessage` | P1 |
| EMAIL-06 | Application status | Yes | Client | Application transition | `ShouldQueue` | Default Laravel `MailMessage` | P1 |
| EMAIL-07 | Estimate update | Yes | Client | Admin changes estimate | `ShouldQueue` | Default Laravel `MailMessage` | P2 |
| EMAIL-08 | Payment confirmed | Yes | Client | Validated payment webhook | `ShouldQueue` | Default Laravel `MailMessage` | P1 |
| EMAIL-09 | Result available | Yes | Client | Primary result verified | `ShouldQueue` | Default Laravel `MailMessage` | P1 |

## 4. Framework-default email mechanisms

No Laravel default email body is active directly.

The application uses Laravel mechanisms with custom notifications:

- `MustVerifyEmail` with `VerifyEmailNotification`.
- Laravel password broker with `PasswordResetNotification`.
- Custom OTP flow with `LoginOtpNotification`.
- No active reference to Laravel's default `ResetPassword` notification was found.

## 5. Admin login OTP

Implementation: `app/Notifications/LoginOtpNotification.php`.

- Trigger: successful admin password step and OTP resend.
- Recipient: authenticated admin user.
- Subject: `Kode OTP Bantu Daftarin`.
- Body: greeting, six-digit OTP, one-use/limited-use notice, and warning for unrecognized login.
- Expiry: 10 minutes.
- Resend cooldown: 60 seconds.
- Maximum attempts: 5.
- Lockout: 15 minutes.
- Previous active challenge is invalidated.
- Code is hashed in `auth_challenges`; queued payload uses encrypted code.
- No CTA/deep link.
- No OTP logging was found.
- The notification is queued but does not explicitly call `afterCommit()`.

## 6. Client email verification

Implementation: `VerifyEmailNotification`.

- Status: active.
- Trigger: registration and resend for an unverified account.
- Recipient: client user.
- Subject: `Verifikasi email Bantu Daftarin`.
- CTA: `Verifikasi email`.
- URL: temporary signed route with `public_id` and email hash.
- Expiry: 60 minutes.
- No private application or document data is included.

## 7. Password reset

Implementation: `PasswordResetNotification`.

- Status: active for client and admin users.
- Trigger: Laravel password broker reset request.
- Subject: `Reset password Bantu Daftarin`.
- CTA: `Reset password`.
- Token expiry: 60 minutes.
- Broker throttle: 60 seconds.
- Token is encrypted in queued notification payload.
- No application, NIK, KK, or document data is included.
- The email does not explicitly state the 60-minute expiry.

## 8. Chat/incoming-message email

Implementation: `ChatUnreadNotification`.

- Trigger: `ChatThread::send()` after a message is persisted.
- Recipient: assigned admin for client messages, or client owner for admin messages.
- Contexts: `APPLICATION` and `GENERAL_SUPPORT`.
- Subject: `Pesan baru di Bantu Daftarin`.
- Body: generic unread-message notice and instruction to open the application.
- No conversation deep link.
- No application/thread context in the body.
- `threadId` is accepted by the notification but is not used in the email content.

Risks:

- One queued email per message.
- No coalescing per conversation.
- No deduplication.
- No read-at-execution suppression.
- No suppression when the recipient is already viewing the thread.
- If no admin is assigned to an application thread, no email recipient is resolved.

## 9. Application lifecycle email flows

Implementation: `ApplicationUpdateNotification`.

### Status update

Subject: `Status aplikasi diperbarui`  
Body: `Status aplikasi Anda sekarang: {status label}.`

Observed transition targets include:

- `AWAITING_DOCUMENTS`
- `DOCUMENTS_READY_FOR_PAYMENT`
- `AWAITING_PAYMENT`
- `PAYMENT_CONFIRMED`
- `DOCUMENTS_SUBMITTED`
- `UNDER_REVIEW`
- `DOCUMENTS_ACCEPTED`
- `REVISION_REQUIRED`
- `REVISION_SUBMITTED`
- `ESTIMATE_PENDING`
- `IN_PROGRESS`
- `WAITING_EXTERNAL_PROCESS`
- `RESULT_UPLOADED`
- `RESULT_REVIEW`
- `COMPLETED`
- `ARCHIVED`

Draft creation does not send this email. Cancellation explicitly uses `notify: false`, so cancellation does not send an email.

### Estimate update

Subject: `Estimasi aplikasi diperbarui`  
Trigger: admin changes the completion estimate through `AdminWorkflowService::setEstimate`.

### Payment confirmation

Subject: `Pembayaran terkonfirmasi`  
Trigger: validated paid webhook and application payment confirmation.

All three variants are sent to the client application owner and have no application-specific CTA.

## 10. Document-review emails

No dedicated emails were found for:

- document upload
- document rejection
- document acceptance
- revision request
- incomplete document set

Document workflow changes may indirectly produce a generic application status email through an application transition.

## 11. Payment emails

No BantuDaftarin email was found for:

- payment request/invoice creation
- QRIS creation
- payment failure
- payment expiry

`XenditInvoiceGateway` sets `should_send_email => false`; provider-generated communication is therefore not attributed to the application.

The application sends an internal email only when payment is confirmed by a validated webhook.

## 12. Result email

Implementation: `ResultAvailableNotification`.

The email is sent only when:

- the result is the primary result;
- verification status is `VERIFIED`;
- scan/security checks pass;
- the application is in `RESULT_REVIEW`;
- the result is not deleted.

Subject: `Hasil layanan tersedia`  
CTA: `Lihat hasil layanan`

The CTA opens the authorized client application workspace, not a public result file URL.

Idempotency is implemented with result locking and a custom database notification check using user, application, and result identifiers. Duplicate result notifications are prevented.

## 13. Cancellation email

No cancellation email exists.

`ApplicationCancellationService` transitions with `notify: false`. Cancellation is recorded in history/audit only.

## 14. Scheduled/reminder email flows

No scheduled email reminders were found for unread messages, incomplete applications, payments, results, or admin summaries.

The scheduler only performs daily file cleanup.

## 15. Current subjects

- `Kode OTP Bantu Daftarin`
- `Verifikasi email Bantu Daftarin`
- `Reset password Bantu Daftarin`
- `Pesan baru di Bantu Daftarin`
- `Status aplikasi diperbarui`
- `Estimasi aplikasi diperbarui`
- `Pembayaran terkonfirmasi`
- `Hasil layanan tersedia`

## 16. Current template architecture

All notifications use:

- `Illuminate\Notifications\Messages\MailMessage`
- Laravel Markdown notification rendering
- Laravel's default notification email layout/components

No custom project email Blade templates, shared BantuDaftarin shell, custom footer, or attachments were found.

## 17. Branding consistency

Text branding is mostly consistent because subjects mention Bantu Daftarin. Visual branding is not yet project-specific:

- default Laravel notification presentation is used;
- no verified BantuDaftarin logo/wordmark in email templates;
- no dedicated email color/type system;
- no custom footer/contact block;
- `.env.example` uses placeholder sender `hello@example.com`.

## 18. Terminology and copy

Observed inconsistencies:

- `Reset password` remains mixed English/Indonesian.
- Email copy uses “aplikasi” while the UI commonly uses “pengajuan”.
- Chat messages are generic and do not name the relevant conversation.
- Status emails do not identify the affected service or application.
- There is no standardized distinction between Bantuan and Dukungan in email copy.

## 19. CTA and deep-link audit

Existing CTAs:

- `Verifikasi email`: signed temporary URL, 60-minute expiry.
- `Reset password`: Laravel broker token URL.
- `Lihat hasil layanan`: authorized application workspace.

Missing contextual CTAs:

- unread chat
- application status
- estimate update
- payment confirmation

No permanent public URL to private documents or result files was found.

## 20. Privacy and security findings

No active email was found to include:

- full NIK;
- full KK;
- private document URLs;
- storage paths;
- provider secrets/tokens;
- confidential webhook payloads;
- internal database identifiers in normal content.

The result email points to the authorized workspace. OTP is sent only through the OTP email.

Potential risk: `.env.example` uses the `log` mailer. If used unchanged, OTP content could appear in application logs.

## 21. Queue behavior

All six notification classes implement `ShouldQueue`.

Explicit `afterCommit()` is present on:

- `PasswordResetNotification`
- `ChatUnreadNotification`
- `ApplicationUpdateNotification`
- `ResultAvailableNotification`

It is not explicit on:

- `LoginOtpNotification`
- `VerifyEmailNotification`

PHPUnit overrides the queue to `sync` and mailer to `array`; tests do not send real external mail.

## 22. Retry behavior

No per-notification configuration was found for:

- `tries`
- `backoff`
- `timeout`
- `retryUntil`
- `failed()`

Retry behavior depends on queue worker/framework configuration. The development queue command uses `--tries=1`.

## 23. Idempotency findings

Strong idempotency:

- result available email

Partial lifecycle protection:

- OTP cooldown and invalidation of older challenges
- password broker token/throttle

No dedicated idempotency/coalescing was found for:

- chat unread email
- generic application status
- estimate update
- payment confirmation
- verification resend beyond route throttling

## 24. Transaction and after-commit findings

Application status, payment, result, and chat flows have after-commit protection or are dispatched after their message transaction.

OTP and verification do not explicitly call `afterCommit()`. No harmful surrounding transaction was proven for those current call sites, but the policy is inconsistent.

## 25. Mail configuration summary

`config/mail.php` reads the mailer and sender identity from environment variables.

Local configuration uses a local SMTP endpoint and a local BantuDaftarin sender identity. The example environment uses a log mailer and placeholder sender identity. PHPUnit overrides mail delivery with the array mailer.

No password or secret is included in this document.

## 26. Test coverage

| Flow | Coverage |
|---|---|
| Admin/client OTP | Partial-good |
| Email verification | Partial |
| Password reset | Partial |
| Chat notification | Partial |
| Application status | None/partial |
| Estimate email | None |
| Payment confirmation email | None/partial |
| Result available | Good |
| Document-specific email | Not applicable |
| Cancellation email | Not applicable |
| Mail rendering snapshots | None |
| Queue retry integration | None |
| Spam/deduplication | None |

Existing tests mostly cover notification dispatch, recipients, OTP lifecycle, reset token protection, result idempotency, and result authorization. Exact email body/subject, chat suppression, retry, and full queue integration are mostly untested.

## 27. Dead or unused email code

No custom notification class was proven unused. All six notification classes have call sites.

No stale custom email templates or unused Mailables were found.

`threadId` in `ChatUnreadNotification` is currently not reflected in the email content.

## 28. Duplicated implementations

No duplicate Mailable/Notification implementation for the same purpose was found.

`NotificationService` and database notifications are complementary: one records in-app notifications and the other sends mail.

## 29. Priority findings

### P0

No confirmed P0 privacy/security leak was found.

### P1

- Chat email sends one queued email per message without dedupe/coalescing.
- No read-at-execution suppression for chat email.
- Chat email has no conversation deep link.
- Application status email does not identify the affected application.
- SMTP/worker delivery was not runtime-verified in this audit.
- The example log mailer can expose OTP content in logs if used unchanged.
- Application threads without an assigned admin do not resolve an email recipient.

### P2

- All email visual output uses default Laravel branding.
- Terminology is not fully standardized.
- Password reset email does not state its expiry.
- Documentation describes some email flows more broadly than the actual implementation.
- Most notification content and after-commit behavior lack direct tests.

### P3

- Chat email copy is generic.
- Application update messages lack context summary.
- No standard footer, contact information, or Reply-To policy.
- No separate custom plain-text email templates.

## 30. Recommended next-phase direction

The next implementation phase should consider:

1. One shared BantuDaftarin email shell.
2. Standard Indonesian terminology.
3. Safe application/service summary fields.
4. Authorized CTAs for status, payment, result, and chat.
5. Dedicated OTP layout with clear expiry/security notice.
6. Chat dedupe, coalescing, and unread-at-execution checks.
7. Consistent `afterCommit()` policy.
8. Idempotency keys for relevant lifecycle emails.
9. Tests for subjects, recipients, links, queueing, dedupe, and sensitive-data omission.
10. Documentation aligned with actual email behavior.

No redesign or implementation was performed in this audit.

## 31. Likely files for a future implementation phase

- `app/Notifications/*.php`
- `app/Services/NotificationService.php`
- `app/Livewire/ChatThread.php`
- `app/Services/ApplicationTransitionService.php`
- `app/Services/AdminWorkflowService.php`
- email view/layout files under `resources/views`
- `config/mail.php`
- `config/queue.php`
- authentication, chat, and admin workflow tests
- email/integration documentation

## Explicit answers

- Active outbound email flows: **9 logical flows, 6 implementations**.
- Client-facing: verification, password reset, chat, application status, estimate, payment confirmation, result, and client OTP.
- Admin-facing: admin OTP and chat notifications from clients.
- Admin OTP: custom, not Laravel default.
- Client verification: present, custom signed URL.
- Password reset: present for client/admin.
- Incoming chat email: present, without spam suppression or conversation coalescing.
- Application-state email: status transition, estimate update, payment confirmation, result available.
- Payment-created/failed/expired email: not present.
- Result email: sent after verified primary result and protected by database idempotency.
- Queued email: all six custom notification classes.
- Synchronous application-level email: none found.
- Confirmed sensitive NIK/KK/document URL exposure: none.
- Generic Laravel email layout: yes.
- Proven unused notification class: none.
- Shared BantuDaftarin email layout: not present.

## Runtime uncertainty

No SMTP delivery or queue worker was exercised to avoid sending real emails. A read-only Tinker aggregate probe could not run because PsySH was blocked while writing its history file before executing the query. No database change occurred.

## Audit conclusion

**OUTBOUND EMAIL SYSTEM AUDIT COMPLETE WITH RUNTIME/CONFIGURATION UNCERTAINTIES**
