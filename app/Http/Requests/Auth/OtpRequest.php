<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->session()->has('pending_auth_challenge_id');
    }

    public function rules(): array
    {
        return ['code' => ['required', 'digits:6']];
    }
}
