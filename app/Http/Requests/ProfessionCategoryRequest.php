<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithApiV2;
use App\Http\Requests\Concerns\RequiresBilingualName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Models\ProfessionCategory;

class ProfessionCategoryRequest extends FormRequest
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
        $this->prepareBilingualName(ProfessionCategory::class);
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

        $query = ProfessionCategory::query()->where('name', json_encode($jsonName));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
