<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantApplicationNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-users') ?? false;
    }

    public function rules(): array
    {
        return [
            'application_name_cs' => ['required', 'string', 'max:255'],
            'application_name_en' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'application_name_cs' => $this->filled('application_name_cs') ? trim($this->input('application_name_cs')) : null,
            'application_name_en' => $this->filled('application_name_en') ? trim($this->input('application_name_en')) : null,
        ]);
    }
}
