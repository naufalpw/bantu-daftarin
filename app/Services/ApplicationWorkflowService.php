<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\ApplicationStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Application;
use App\Models\ApplicationConsent;
use App\Models\ApplicationStatusHistory;
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
            throw new \DomainException('Data aplikasi sudah dikunci pada tahap ini.');
        }

        if ($application->service->code === 'NPWP_PERSONAL') {
            if (blank($details['name'] ?? null)) {
                throw new \DomainException('Nama lengkap wajib diisi.');
            }
            $application->personalDetails()->updateOrCreate([], [
                'name' => $details['name'] ?? '',
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
            $application->businessDetails()->updateOrCreate([], [
                'business_name' => $details['business_name'] ?? '',
                'business_type' => $details['business_type'] ?? null,
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
        if (! $application->personalDetails && ! $application->businessDetails) {
            throw new \DomainException('Lengkapi data aplikasi terlebih dahulu.');
        }

        $application->forceFill(['submitted_at' => now()])->save();

        return $this->transitions->transition($application, ApplicationStatus::AWAITING_DOCUMENTS, $actor);
    }

    public function createPayment(Application $application, User $actor): Payment
    {
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

        $existing = $application->payments()->where('status', 'PENDING')->latest()->first();
        if ($existing?->checkout_url && $existing->expires_at?->isPast()) {
            $existing->forceFill(['status' => PaymentStatus::EXPIRED, 'failed_at' => now()])->save();
            $this->audit->record('payment.state_changed', $existing, ['to' => PaymentStatus::EXPIRED->value], $actor);
            $existing = null;
        }
        if ($existing?->checkout_url) {
            if (config('services.xendit.driver') === 'fake' && str_starts_with($existing->checkout_url, 'https://example.test/checkout/')) {
                $existing->forceFill([
                    'checkout_url' => route('testing.fake-payments.checkout', $existing->public_id),
                ])->save();
            }

            return $existing;
        }

        $payment = $existing ?: $application->payments()->create([
            'provider' => 'xendit',
            'reference_id' => 'BD-'.Str::uuid(),
            'amount' => $application->price_amount_snapshot,
            'currency' => $application->currency,
            'status' => 'PENDING',
            'expires_at' => now()->addSeconds((int) config('services.xendit.invoice_duration')),
        ]);
        if (! $existing) {
            $this->audit->record('payment.created', $payment, ['application_id' => $application->public_id, 'amount' => (string) $payment->amount, 'currency' => $payment->currency], $actor);
        }

        try {
            $invoice = $this->paymentGateway->createInvoice($application->load(['user', 'service']), $payment);
            $payment->forceFill([
                'external_id' => $invoice['external_id'],
                'checkout_url' => $invoice['checkout_url'],
                'expires_at' => $invoice['expires_at'],
                'provider_payload' => $invoice['payload'],
            ])->save();
            $this->audit->record('payment.checkout_created', $payment, ['application_id' => $application->public_id], $actor);
        } catch (PaymentGatewayException $exception) {
            $payment->forceFill(['status' => 'FAILED', 'failed_at' => now()])->save();
            $this->audit->record('payment.state_changed', $payment, ['to' => 'FAILED'], $actor);
            throw $exception;
        }

        return $payment->fresh();
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
