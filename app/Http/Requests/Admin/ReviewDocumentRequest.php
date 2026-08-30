<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReviewDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:ACCEPT,REJECT,REQUEST_REVISION'],
            'reason' => ['required_unless:action,ACCEPT', 'nullable', 'string', 'max:2000'],
            'instruction' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
