<?php

namespace App\Http\Requests\Client;

use App\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isClient();
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'nik' => ['nullable', 'digits:16'],
            'family_card_number' => ['nullable', 'digits:16'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'marital_status' => ['nullable', 'string', Rule::in(['Lajang', 'Kawin', 'Cerai Hidup', 'Cerai Mati'])],
            'family_status' => ['nullable', 'string', Rule::in(['Suami', 'Istri', 'Anak'])],
            'gender' => ['nullable', 'string', Rule::in(['Pria', 'Wanita'])],
            'business_name' => ['nullable', 'string', 'max:190'],
            'business_type' => ['nullable', 'string', Rule::in(BusinessType::values())],
            'business_type_other' => ['nullable', 'required_if:business_type,OTHER', 'prohibited_unless:business_type,OTHER', 'string', 'max:128'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'representative.name' => ['nullable', 'required_with:representative.relationship', 'string', 'max:120'],
            'representative.relationship' => ['nullable', 'required_with:representative.name', 'in:OWNER,DIRECTOR,MANAGEMENT,EMPLOYEE,AUTHORIZED_REPRESENTATIVE,OTHER'],
            'representative.email' => ['nullable', 'email:rfc', 'max:190'],
            'additional_representative.name' => ['nullable', 'required_with:additional_representative.relationship,additional_representative.email', 'string', 'max:120'],
            'additional_representative.relationship' => ['nullable', 'required_with:additional_representative.name,additional_representative.email', 'in:OWNER,DIRECTOR,MANAGEMENT,EMPLOYEE,AUTHORIZED_REPRESENTATIVE,OTHER'],
            'additional_representative.email' => ['nullable', 'email:rfc', 'max:190'],
        ];
    }
}
