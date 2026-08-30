# Domain Lifecycle

## Application states

`DRAFT → AWAITING_DOCUMENTS → DOCUMENTS_READY_FOR_PAYMENT → AWAITING_PAYMENT → PAYMENT_CONFIRMED → DOCUMENTS_SUBMITTED → UNDER_REVIEW → DOCUMENTS_ACCEPTED → ESTIMATE_PENDING → IN_PROGRESS → WAITING_EXTERNAL_PROCESS → RESULT_UPLOADED → RESULT_REVIEW → COMPLETED → ARCHIVED`

Revision: `UNDER_REVIEW → REVISION_REQUIRED → REVISION_SUBMITTED → UNDER_REVIEW`.

Transition hanya boleh melalui domain action/rule, bukan arbitrary dropdown. Setiap perubahan mencatat from/to, actor, timestamp, reason bila diperlukan, status history, dan audit event.

## Payment states

`PENDING`, `PAID`, `FAILED`, `EXPIRED`, `CANCELLED`, `REFUND_REQUESTED`, `REFUNDING`, `REFUNDED`.

Application status dan payment status terpisah. Browser return dari provider tidak membuktikan pembayaran; webhook tervalidasi adalah sumber kebenaran.

## Document states

Dokumen memiliki versi. Sebelum payment client dapat upload/replace/delete. Setelah payment dokumen aktif terkunci; hanya requirement yang diminta revisi yang dapat diunggah ulang. Accepted version tetap locked.
