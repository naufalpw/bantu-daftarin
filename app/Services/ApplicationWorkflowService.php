<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\ApplicationStatus;
use App\Enums\BusinessType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Exceptions\PaymentGatewayDefinitiveException;
use App\Exceptions\PaymentGatewayException;
use App\Models\Application;
use App\Models\ApplicationConsent;
use App\Models\ApplicationStatusHistory;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplicationWorkflowService
{
    public function __construct(
        private readonly ApplicationTransitionService $transitions,
        private readonly PaymentGateway $paymentGateway,
        private readonly AuditService $audit,
        private readonly PaymentPayloadMinimizer $paymentPayloads,
    ) {}

    public function createDraft(User $user, Service $service, array $details, ?array $representative = null, ?array $additionalRepresentative = null): Application
    {
        if ($service->status !== ServiceStatus::ACTIVE || $service->price_amount === null || ! $service->isBookable()) {
            throw new \DomainException('Layanan belum dapat dipesan.');
        }

        return DB::transaction(function () use ($user, $service, $details, $representative, $additionalRepresentative): Application {
            $application = Application::create([
                'user_id' => $user->getKey(),
                'service_id' => $service->getKey(),
                'status' => ApplicationStatus::DRAFT,
                'price_amount_snapshot' => $service->price_amount,
                'currency' => $service->currency,
            ]);
            ApplicationStatusHistory::create([
                'application_id' => $application->getKey(),
                'from_status' => null,
                'to_status' => ApplicationStatus::DRAFT->value,
                'actor_type' => 'user',
                'actor_id' => $user->getKey(),
                'created_at' => now(),
            ]);

            foreach ($service->requirements()->where('active', true)->orderBy('sort_order')->get() as $template) {
                $application->requirements()->create([
                    'service_requirement_id' => $template->getKey(),
                    'code' => $template->code,
                    'name' => $template->name,
                    'is_required' => $template->is_required,
                    'allowed_extensions' => $template->allowed_extensions,
                    'allowed_mimes' => $template->allowed_mimes,
                    'max_size_bytes' => $template->max_size_bytes,
                    'condition_snapshot' => $template->condition,
                    'sort_order' => $template->sort_order,
                    'active' => true,
                    'status' => 'PENDING',
                ]);
            }

            $this->saveDetails($application, $details, $representative, $user, $additionalRepresentative);
            $application->chatThread()->create(['client_user_id' => $user->getKey()]);
            ApplicationConsent::create([
                'application_id' => $application->getKey(),
                'consent_type' => 'DATA_PROCESSING',
                'version' => '1.0',
                'accepted_by_user_id' => $user->getKey(),
                'accepted_at' => now(),
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
            $this->audit->record('application.created', $application, ['service_code' => $service->code], $user);

            return $application->load(['service', 'requirements', 'personalDetails', 'businessDetails', 'representatives']);
        });
    }

    public function saveDetails(Application $application, array $details, ?array $representative, User $actor, ?array $additionalRepresentative = null): Application
    {
        if (! in_array($application->status, [ApplicationStatus::DRAFT, ApplicationStatus::AWAITING_DOCUMENTS], true)) {
            throw new \DomainException('Data pengajuan sudah dikunci pada tahap ini.');
        }

        if ($application->service->code === 'NPWP_PERSONAL') {
            if (blank($details['name'] ?? null)) {
                throw new \DomainException('Nama lengkap wajib diisi.');
            }
            $existingPersonal = $application->personalDetails;
            $application->personalDetails()->updateOrCreate([], [
                'name' => $details['name'] ?? '',
                'nik' => array_key_exists('nik', $details) ? $details['nik'] : $existingPersonal?->nik,
                'family_card_number' => array_key_exists('family_card_number', $details) ? $details['family_card_number'] : $existingPersonal?->family_card_number,
                'email' => $details['email'] ?? $actor->email,
                'marital_status' => $details['marital_status'] ?? null,
                'family_status' => $details['family_status'] ?? null,
                'purpose' => $details['purpose'] ?? null,
                'gender' => $details['gender'] ?? null,
            ]);
        } else {
            $existingPrimary = $application->representatives()->where('is_primary', true)->first();
            $primary = $representative ?: ($existingPrimary ? ['name' => $existingPrimary->name, 'relationship' => $existingPrimary->relationship->value, 'email' => $existingPrimary->email] : null);
            if (blank($details['business_name'] ?? null) || blank($primary['name'] ?? null) || blank($primary['relationship'] ?? null)) {
                throw new \DomainException('Nama badan usaha dan penanggung jawab utama wajib diisi.');
            }
            $existingBusiness = $application->businessDetails;
            $businessType = array_key_exists('business_type', $details) ? $details['business_type'] : $existingBusiness?->business_type;
            $businessTypeOther = array_key_exists('business_type_other', $details) ? $details['business_type_other'] : $existingBusiness?->business_type_other;
            if ($businessType === BusinessType::OTHER->value && blank($businessTypeOther)) {
                throw new \DomainException('Jenis badan usaha lainnya wajib diisi.');
            }
            if ($businessType !== BusinessType::OTHER->value) {
                $businessTypeOther = null;
            }
            $application->businessDetails()->updateOrCreate([], [
                'business_name' => $details['business_name'] ?? '',
                'business_type' => $businessType,
                'business_type_other' => $businessTypeOther,
                'purpose' => $details['purpose'] ?? null,
            ]);

            if ($representative) {
                $application->representatives()->where('is_primary', true)->update(['is_primary' => false]);
                $application->representatives()->updateOrCreate(
                    ['is_primary' => true],
                    [
                        'name' => $primary['name'],
                        'relationship' => $primary['relationship'],
                        'email' => $primary['email'] ?? null,
                        'is_primary' => true,
                    ]
                );
            }
            $hasAdditionalRepresentative = filled($additionalRepresentative['name'] ?? null)
                || filled($additionalRepresentative['relationship'] ?? null)
                || filled($additionalRepresentative['email'] ?? null);
            if ($hasAdditionalRepresentative && (! filled($additionalRepresentative['name'] ?? null) || ! filled($additionalRepresentative['relationship'] ?? null))) {
                throw new \DomainException('Nama dan hubungan representative tambahan harus diisi bersama.');
            }
            if ($hasAdditionalRepresentative) {
                $application->representatives()->updateOrCreate(
                    ['is_primary' => false],
                    [
                        'name' => $additionalRepresentative['name'],
                        'relationship' => $additionalRepresentative['relationship'],
                        'email' => $additionalRepresentative['email'] ?? null,
                        'is_primary' => false,
                    ]
                );
            } elseif ($additionalRepresentative !== null) {
                $application->representatives()->where('is_primary', false)->delete();
            }
        }

        return $application->fresh(['service', 'personalDetails', 'businessDetails', 'representatives']);
    }

    public function submitForDocuments(Application $application, User $actor): Application
    {
        return DB::transaction(function () use ($application, $actor): Application {
            $application = Application::query()
                ->with(['service', 'personalDetails', 'businessDetails', 'representatives'])
                ->lockForUpdate()
                ->findOrFail($application->getKey());

            if (! in_array($application->status, [ApplicationStatus::DRAFT, ApplicationStatus::AWAITING_DOCUMENTS], true)) {
                throw new \DomainException('Pengajuan tidak dapat dikirim pada tahap ini.');
            }

            if ($application->service->code === 'NPWP_PERSONAL') {
                if (! $application->personalDetails || blank($application->personalDetails->name) || blank($application->personalDetails->nik) || blank($application->personalDetails->family_card_number)) {
                    throw new \DomainException('Nama, NIK, dan Nomor KK wajib diisi sebelum konfirmasi.');
                }
            } elseif (! $application->businessDetails || ! $application->representatives->firstWhere('is_primary', true)) {
                throw new \DomainException('Lengkapi data pengajuan terlebih dahulu.');
            }

            if ($application->service->code === 'NPWP_BUSINESS') {
                $businessType = $application->businessDetails->business_type;
                if (blank($businessType) || ($businessType === BusinessType::OTHER->value && blank($application->businessDetails->business_type_other))) {
                    throw new \DomainException('Jenis badan usaha wajib dilengkapi sebelum konfirmasi.');
                }
            }

            $application->forceFill(['submitted_at' => now()])->save();
            if ($application->status === ApplicationStatus::DRAFT) {
                $application = $this->transitions->transition($application, ApplicationStatus::AWAITING_DOCUMENTS, $actor);
            }

            if ($application->hasAllRequiredDocuments()) {
                return $this->transitions->transition(
                    $application,
                    ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT,
                    $actor,
                    'Data dan dokumen wajib telah lengkap.'
                );
            }

            return $application;
        });
    }

    public function createPayment(Application $application, User $actor, PaymentMethod|string|null $method = null): Payment
    {
        $selectedMethod = $method instanceof PaymentMethod
            ? $method
            : PaymentMethod::tryFrom(strtoupper((string) ($method ?: PaymentMethod::BCA->value))) ?? throw new \DomainException('Metode pembayaran tidak didukung.');

        if (! $selectedMethod->isAvailable()) {
            throw new PaymentGatewayDefinitiveException('PayPal belum tersedia.');
        }

        // PHASE 1 — LOCAL INTENT (DURABLE COMMIT)
        $payment = DB::transaction(function () use ($application, $actor, $selectedMethod): Payment {
            $application = Application::query()
                ->with(['service', 'user'])
                ->lockForUpdate()
                ->findOrFail($application->getKey());

            if ($application->status === ApplicationStatus::AWAITING_DOCUMENTS) {
                if (! $application->hasAllRequiredDocuments()) {
                    throw new \DomainException('Lengkapi dan pastikan seluruh dokumen wajib lolos pemeriksaan keamanan terlebih dahulu.');
                }
                $application = $this->transitions->transition($application, ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT, $actor);
            }

            if ($application->status !== ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT && $application->status !== ApplicationStatus::AWAITING_PAYMENT) {
                throw new \DomainException('Aplikasi belum siap untuk pembayaran.');
            }

            if ($application->status === ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT) {
                $application = $this->transitions->transition($application, ApplicationStatus::AWAITING_PAYMENT, $actor);
            }

            $existing = $application->payments()
                ->where('status', PaymentStatus::PENDING->value)
                ->latest('id')
                ->lockForUpdate()
                ->first();
            if ($existing?->expires_at?->isPast()) {
                $existing->forceFill(['status' => PaymentStatus::EXPIRED, 'failed_at' => now()])->save();
                $this->audit->record('payment.state_changed', $existing, ['to' => PaymentStatus::EXPIRED->value], $actor);
                $existing = null;
            }

            $existingMethod = $existing?->payment_method;
            if ($existing && $existingMethod instanceof PaymentMethod && $existingMethod !== $selectedMethod) {
                throw new \DomainException('Pembayaran yang sedang berjalan menggunakan metode lain. Selesaikan atau tunggu sampai kedaluwarsa.');
            }

            if ($existing && ($existing->checkout_url || filled($existing->external_id))) {
                if (config('services.xendit.driver') === 'fake' && str_starts_with((string) $existing->checkout_url, 'https://example.test/checkout/')) {
                    $existing->forceFill([
                        'checkout_url' => route('testing.fake-payments.checkout', $existing->public_id),
                        'payment_method' => $existingMethod?->value ?? $selectedMethod->value,
                    ])->save();
                }

                return $existing->fresh();
            }

            $payment = $existing ?: $application->payments()->create([
                'provider' => $selectedMethod->provider(),
                'payment_method' => $selectedMethod,
                'reference_id' => 'BD-'.Str::uuid(),
                'amount' => $application->price_amount_snapshot,
                'currency' => $application->currency,
                'status' => PaymentStatus::PENDING,
                'expires_at' => now()->addSeconds((int) config('services.xendit.invoice_duration')),
            ]);
            if (! $existing) {
                $this->audit->record('payment.created', $payment, ['application_id' => $application->public_id, 'amount' => (string) $payment->amount, 'currency' => $payment->currency, 'method' => $selectedMethod->value], $actor);
            }

            return $payment->fresh();
        });

        if ($payment->checkout_url || filled($payment->external_id)) {
            return $payment;
        }

        // PHASE 2 — PROVIDER CALL (OUTSIDE LOCAL TRANSACTION)
        try {
            $invoice = $this->paymentGateway->createInvoice($application->load(['user', 'service']), $payment, $selectedMethod);
        } catch (PaymentGatewayDefinitiveException $exception) {
            $this->finalizeDefinitiveCheckoutFailure($payment, $actor);
            throw $exception;
        } catch (PaymentGatewayException $exception) {
            $this->recordAmbiguousCheckoutFailure($payment, $actor);
            throw $exception;
        }

        // PHASE 3 — RECONCILE (COMMITTED LOCAL UPDATE)
        return $this->persistPaymentCheckout($payment, $application, $invoice, $selectedMethod, $actor);
    }

    public function reconcilePayment(Payment $payment, ?User $actor = null): Payment
    {
        $payment = DB::transaction(function () use ($payment): Payment {
            return Payment::query()->lockForUpdate()->findOrFail($payment->getKey())->fresh();
        });

        if ($payment->status !== PaymentStatus::PENDING || (filled($payment->external_id) && filled($payment->checkout_url))) {
            return $payment;
        }

        $application = $payment->application()->with(['user', 'service'])->firstOrFail();
        $selectedMethod = $payment->payment_method;

        try {
            $invoice = $this->paymentGateway->createInvoice($application, $payment, $selectedMethod);
        } catch (PaymentGatewayDefinitiveException $exception) {
            $this->finalizeDefinitiveCheckoutFailure($payment, $actor);
            throw $exception;
        } catch (PaymentGatewayException $exception) {
            $this->recordAmbiguousCheckoutFailure($payment, $actor);
            throw $exception;
        }

        return $this->persistPaymentCheckout($payment, $application, $invoice, $selectedMethod, $actor);
    }

    /** @param array{external_id:string, checkout_url:string|null, expires_at:\DateTimeInterface|null, payload:array} $invoice */
    private function persistPaymentCheckout(Payment $payment, Application $application, array $invoice, PaymentMethod $selectedMethod, ?User $actor): Payment
    {
        $providerPayload = $this->paymentPayloads->checkout($invoice['payload']);

        return DB::transaction(function () use ($payment, $application, $invoice, $providerPayload, $selectedMethod, $actor): Payment {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($locked->status === PaymentStatus::FAILED && $this->canSupersedeLocalDefinitiveFailure($locked, $payment, $invoice)) {
                $locked->forceFill([
                    'status' => PaymentStatus::PENDING,
                    'failed_at' => null,
                ]);
                $this->audit->record('payment.checkout_definitive_failure_superseded', $locked, [
                    'reference_id' => $locked->reference_id,
                ], $actor);
            } elseif ($locked->status !== PaymentStatus::PENDING) {
                return $locked->fresh();
            }

            if (filled($locked->external_id) && ! hash_equals((string) $locked->external_id, (string) $invoice['external_id'])) {
                throw new \DomainException('Identitas pembayaran provider tidak sesuai dengan pembayaran yang tersimpan.');
            }

            if (filled($locked->external_id) && filled($locked->checkout_url)) {
                return $locked->fresh();
            }

            $locked->forceFill([
                'external_id' => $invoice['external_id'],
                'checkout_url' => $invoice['checkout_url'],
                'expires_at' => $invoice['expires_at'] ?? $locked->expires_at,
                'provider_payload' => $providerPayload,
                'failed_at' => null,
            ])->save();
            $this->audit->record('payment.checkout_created', $locked, ['application_id' => $application->public_id, 'method' => $selectedMethod->value], $actor);

            return $locked->fresh();
        });
    }

    private function finalizeDefinitiveCheckoutFailure(Payment $payment, ?User $actor): void
    {
        DB::transaction(function () use ($payment, $actor): void {
            $locked = Payment::query()->lockForUpdate()->find($payment->getKey());
            if (! $locked
                || $locked->status !== PaymentStatus::PENDING
                || ! hash_equals((string) $locked->reference_id, (string) $payment->reference_id)
                || filled($locked->external_id)
                || filled($locked->checkout_url)
                || filled($locked->provider_payload)) {
                return;
            }

            $locked->forceFill([
                'status' => PaymentStatus::FAILED,
                'failed_at' => now(),
            ])->save();
            $this->audit->record('payment.checkout_definitive_failed', $locked, [
                'reference_id' => $locked->reference_id,
                'classification' => 'definitive',
            ], $actor);
            $this->audit->record('payment.state_changed', $locked, [
                'from' => PaymentStatus::PENDING->value,
                'to' => PaymentStatus::FAILED->value,
                'reason' => 'provider_checkout_definitive_failure',
            ], $actor);
        });
    }

    private function recordAmbiguousCheckoutFailure(Payment $payment, ?User $actor): void
    {
        DB::transaction(function () use ($payment, $actor): void {
            $locked = Payment::query()->lockForUpdate()->find($payment->getKey());
            if ($locked
                && $locked->status === PaymentStatus::PENDING
                && hash_equals((string) $locked->reference_id, (string) $payment->reference_id)
                && blank($locked->external_id)
                && blank($locked->checkout_url)) {
                $this->audit->record('payment.checkout_attempt_failed', $locked, [
                    'recoverable' => true,
                    'classification' => 'ambiguous',
                ], $actor);
            }
        });
    }

    /** @param array{external_id:string, checkout_url:string|null, expires_at:\DateTimeInterface|null, payload:array} $invoice */
    private function canSupersedeLocalDefinitiveFailure(Payment $locked, Payment $payment, array $invoice): bool
    {
        if (filled($locked->external_id)
            || filled($locked->checkout_url)
            || filled($locked->provider_payload)
            || ! hash_equals((string) $locked->reference_id, (string) $payment->reference_id)) {
            return false;
        }

        $providerReference = (string) ($invoice['payload']['reference_id'] ?? '');
        if ($providerReference !== '' && ! hash_equals((string) $locked->reference_id, $providerReference)) {
            return false;
        }

        return AuditLog::query()
            ->where('event', 'payment.checkout_definitive_failed')
            ->where('auditable_type', $locked->getMorphClass())
            ->where('auditable_id', $locked->getKey())
            ->exists();
    }

    public function submitDocuments(Application $application, User $actor): Application
    {
        if ($application->status !== ApplicationStatus::PAYMENT_CONFIRMED || ! $application->hasAllRequiredDocuments()) {
            throw new \DomainException('Dokumen belum siap dikirim untuk pemeriksaan.');
        }

        return $this->transitions->transition($application, ApplicationStatus::DOCUMENTS_SUBMITTED, $actor);
    }

    public function submitRevision(Application $application, User $actor): Application
    {
        if ($application->status !== ApplicationStatus::REVISION_REQUIRED) {
            throw new \DomainException('Aplikasi tidak sedang menunggu perbaikan.');
        }
        if ($application->requirements()->where('status', 'REVISION_REQUIRED')->exists() || ! $application->hasAllRequiredDocuments()) {
            throw new \DomainException('Unggah seluruh dokumen yang diminta untuk revisi terlebih dahulu.');
        }

        return $this->transitions->transition($application, ApplicationStatus::REVISION_SUBMITTED, $actor);
    }
}
