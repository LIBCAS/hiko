<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Profession;

class ProfessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cs' => ['nullable', 'string', 'max:255', 'required_without:en'],
            'en' => ['nullable', 'string', 'max:255', 'required_without:cs'],
            'profession_category_id' => ['required', 'exists:' . tenancy()->tenant->table_prefix . '__profession_categories,id'],
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
            'profession_category_id' => $this->input('category'),
        ]);
    }

    public function failsDuplicateCheck(?int $excludeId = null): bool
    {
        $jsonName = [
            'cs' => $this->input('cs'),
            'en' => $this->input('en'),
        ];

        $query = Profession::query()
            ->where('name', json_encode($jsonName))
            ->where('profession_category_id', $this->input('profession_category_id'));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
