<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'max:15360'], 'type' => ['required', 'in:PRIMARY_RESULT,SUPPORTING_DOCUMENT,RECEIPT,REPORT,OTHER']];
    }
}
