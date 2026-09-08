<?php

namespace App\Http\Requests\Client;

use App\Enums\ApplicationCancellationReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isClient();
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::enum(ApplicationCancellationReason::class)],
            'reason_other' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf($this->string('reason')->toString() === ApplicationCancellationReason::OTHER->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Pilih alasan pembatalan.',
            'reason_other.required' => 'Jelaskan alasan pembatalan lainnya.',
            'reason_other.max' => 'Penjelasan alasan maksimal 500 karakter.',
        ];
    }
}
