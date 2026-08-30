<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'provider_payload' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function actions(): array
    {
        return is_array($this->provider_payload['actions'] ?? null)
            ? $this->provider_payload['actions']
            : [];
    }

    public function action(string $descriptor): ?array
    {
        $descriptor = strtoupper($descriptor);

        foreach ($this->actions() as $action) {
            if (is_array($action) && strtoupper((string) ($action['descriptor'] ?? '')) === $descriptor) {
                return $action;
            }
        }

        return null;
    }

    public function virtualAccountNumber(): ?string
    {
        $action = $this->action('VIRTUAL_ACCOUNT_NUMBER');
        $value = $action['value'] ?? $action['virtual_account_number'] ?? null;

        return filled($value) ? (string) $value : null;
    }

    public function qrString(): ?string
    {
        $action = $this->action('QR_STRING');
        $value = $action['value'] ?? $action['qr_string'] ?? null;

        return filled($value) ? (string) $value : null;
    }

    public function gatewayStatus(): ?string
    {
        $status = $this->provider_payload['status'] ?? null;

        return filled($status) ? strtoupper((string) $status) : null;
    }
}
