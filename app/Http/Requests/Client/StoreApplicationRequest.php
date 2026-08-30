<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isClient();
    }

    public function rules(): array
    {
        return [
            'service_public_id' => ['required', 'uuid'],
            'kind' => ['required', 'in:NPWP_PERSONAL,NPWP_BUSINESS'],
            'consent' => ['accepted'],
            'name' => ['required_if:kind,NPWP_PERSONAL', 'nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'marital_status' => ['nullable', 'string', 'max:64'],
            'family_status' => ['nullable', 'string', 'max:64'],
            'gender' => ['nullable', 'string', 'max:32'],
            'business_name' => ['required_if:kind,NPWP_BUSINESS', 'nullable', 'string', 'max:190'],
            'business_type' => ['nullable', 'string', 'max:128'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'representative.name' => ['required_if:kind,NPWP_BUSINESS', 'nullable', 'string', 'max:120'],
            'representative.relationship' => ['required_if:kind,NPWP_BUSINESS', 'nullable', 'in:OWNER,DIRECTOR,MANAGEMENT,EMPLOYEE,AUTHORIZED_REPRESENTATIVE,OTHER'],
            'representative.email' => ['nullable', 'email:rfc', 'max:190'],
            'additional_representative.name' => ['nullable', 'required_with:additional_representative.relationship,additional_representative.email', 'string', 'max:120'],
            'additional_representative.relationship' => ['nullable', 'required_with:additional_representative.name,additional_representative.email', 'in:OWNER,DIRECTOR,MANAGEMENT,EMPLOYEE,AUTHORIZED_REPRESENTATIVE,OTHER'],
            'additional_representative.email' => ['nullable', 'email:rfc', 'max:190'],
        ];
    }
}
