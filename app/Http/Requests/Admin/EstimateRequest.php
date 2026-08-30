<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return ['estimated_completion_at' => ['required', 'date', 'after:now'], 'reason' => ['required', 'string', 'max:2000']];
    }
}
