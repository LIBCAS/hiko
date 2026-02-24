<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Models\ProfessionCategory;

class ProfessionCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cs' => ['nullable', 'string', 'max:255', 'required_without:en'],
            'en' => ['nullable', 'string', 'max:255', 'required_without:cs'],
        ];
    }

    public function authorize(): bool
    {
        return auth()->user()->hasAbility('manage-metadata');
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'cs' => $this->filled('cs') ? trim($this->input('cs')) : null,
            'en' => $this->filled('en') ? trim($this->input('en')) : null,
        ]);
    }

    public function failsDuplicateCheck(?int $excludeId = null): bool
    {
        $jsonName = [
            'cs' => $this->input('cs'),
            'en' => $this->input('en'),
        ];

        $query = ProfessionCategory::query()->where('name', json_encode($jsonName));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
