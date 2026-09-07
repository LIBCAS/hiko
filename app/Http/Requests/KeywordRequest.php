<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithApiV2;
use App\Http\Requests\Concerns\RequiresBilingualName;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Keyword;

class KeywordRequest extends FormRequest
{
    use InteractsWithApiV2, RequiresBilingualName;

    public function rules(): array
    {
        $categoryRules = $this->isApiV2UpdateRequest()
            ? ['sometimes', 'nullable', 'exists:' . tenancy()->tenant->table_prefix . '__keyword_categories,id']
            : ['required', 'exists:' . tenancy()->tenant->table_prefix . '__keyword_categories,id'];

        return [
            ...$this->bilingualNameRules(),
            'keyword_category_id' => $categoryRules,
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

        $this->prepareBilingualName(Keyword::class);

        if ($this->exists('keyword_category_id') || $this->exists('category_id') || $this->exists('category')) {
            $payload['keyword_category_id'] = $this->input('keyword_category_id', $this->input('category_id', $this->input('category')));
        }

        $this->merge($payload);
    }

    public function withValidator($validator): void
    {
        $this->validateAllowedApiV2Fields($validator, [
            'cs',
            'en',
            'keyword_category_id',
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

        $categoryId = $this->input('keyword_category_id', $fallback['keyword_category_id'] ?? null);

        $query = Keyword::query()
            ->where('name', json_encode($jsonName))
            ->where('keyword_category_id', $categoryId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
