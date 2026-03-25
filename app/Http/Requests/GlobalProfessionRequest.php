<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalProfessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-users') ?? false;
    }

    public function rules(): array
    {
        return [
            'cs' => ['required', 'string', 'max:255'],
            'en' => ['nullable', 'string', 'max:255'],
            'profession_category_id' => ['nullable', 'exists:global_profession_categories,id'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'cs' => $this->filled('cs') ? trim($this->input('cs')) : null,
            'en' => $this->filled('en') ? trim($this->input('en')) : null,
            'profession_category_id' => $this->input('profession_category_id', $this->input('category_id', $this->input('category'))),
        ]);
    }
}
