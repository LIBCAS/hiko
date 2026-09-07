<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithApiV2;
use App\Http\Requests\Concerns\RequiresBilingualName;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\KeywordCategory;

class KeywordCategoryRequest extends FormRequest
{
    use InteractsWithApiV2, RequiresBilingualName;

    public function rules(): array
    {
        return [
            ...$this->bilingualNameRules(),
            'client_meta' => ['nullable', 'array'],
        ];
    }

    public function authorize(): bool
    {
        return auth()->user()->hasAbility('manage-metadata');
    }

    public function prepareForValidation(): void
    {
        $this->prepareBilingualName(KeywordCategory::class);
    }

    public function withValidator($validator): void
    {
        $this->validateAllowedApiV2Fields($validator, ['cs', 'en', 'client_meta']);
    }

    public function failsDuplicateCheck(?int $excludeId = null): bool
    {
        $jsonName = [
            'cs' => $this->input('cs'),
            'en' => $this->input('en'),
        ];

        $query = KeywordCategory::query()->where('name', json_encode($jsonName));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
