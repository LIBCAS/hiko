<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithApiV2;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Profession;

class ProfessionRequest extends FormRequest
{
    use InteractsWithApiV2;

    public function rules(): array
    {
        $nameRules = $this->isApiV2UpdateRequest()
            ? ['sometimes', 'nullable', 'string', 'max:255']
            : ['nullable', 'string', 'max:255', 'required_without:en'];
        $otherNameRules = $this->isApiV2UpdateRequest()
            ? ['sometimes', 'nullable', 'string', 'max:255']
            : ['nullable', 'string', 'max:255', 'required_without:cs'];
        $categoryRules = $this->isApiV2UpdateRequest()
            ? ['sometimes', 'nullable', 'exists:' . tenancy()->tenant->table_prefix . '__profession_categories,id']
            : ['required', 'exists:' . tenancy()->tenant->table_prefix . '__profession_categories,id'];

        return [
            'cs' => $nameRules,
            'en' => $otherNameRules,
            'profession_category_id' => $categoryRules,
            'client_meta' => ['nullable', 'array'],
        ];
    }

    public function authorize(): bool
    {
        return auth()->user()->hasAbility('manage-metadata');
    }

    public function prepareForValidation(): void
    {
        $payload = [];

        if ($this->exists('cs')) {
            $payload['cs'] = $this->filled('cs') ? trim((string) $this->input('cs')) : null;
        }

        if ($this->exists('en')) {
            $payload['en'] = $this->filled('en') ? trim((string) $this->input('en')) : null;
        }

        if ($this->exists('profession_category_id') || $this->exists('category_id') || $this->exists('category')) {
            $payload['profession_category_id'] = $this->input('profession_category_id', $this->input('category_id', $this->input('category')));
        }

        $this->merge($payload);
    }

    public function withValidator($validator): void
    {
        $this->validateAllowedApiV2Fields($validator, [
            'cs',
            'en',
            'profession_category_id',
            'category_id',
            'category',
            'client_meta',
        ]);
    }

    public function failsDuplicateCheck(?int $excludeId = null, ?array $fallback = null): bool
    {
        $fallback = $fallback ?? [];
        $jsonName = [
            'cs' => $this->input('cs', $fallback['cs'] ?? null),
            'en' => $this->input('en', $fallback['en'] ?? null),
        ];

        $categoryId = $this->input('profession_category_id', $fallback['profession_category_id'] ?? null);

        $query = Profession::query()
            ->where('name', json_encode($jsonName))
            ->where('profession_category_id', $categoryId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
