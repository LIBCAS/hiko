<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Keyword;

class KeywordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cs' => ['nullable', 'string', 'max:255', 'required_without:en'],
            'en' => ['nullable', 'string', 'max:255', 'required_without:cs'],
            'keyword_category_id' => ['required', 'exists:' . tenancy()->tenant->table_prefix . '__keyword_categories,id'],
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
            'keyword_category_id' => $this->input('category'),
        ]);
    }

    public function failsDuplicateCheck(?int $excludeId = null): bool
    {
        $jsonName = [
            'cs' => $this->input('cs'),
            'en' => $this->input('en'),
        ];

        $query = Keyword::query()
            ->where('name', json_encode($jsonName))
            ->where('keyword_category_id', $this->input('keyword_category_id'));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
