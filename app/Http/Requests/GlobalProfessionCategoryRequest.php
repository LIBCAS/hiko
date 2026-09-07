<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithApiV2;
use App\Http\Requests\Concerns\RequiresBilingualName;
use App\Models\GlobalProfessionCategory;
use Illuminate\Foundation\Http\FormRequest;

class GlobalProfessionCategoryRequest extends FormRequest
{
    use InteractsWithApiV2, RequiresBilingualName;

    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-users') ?? false;
    }

    public function rules(): array
    {
        return [
            ...$this->bilingualNameRules(),
            'name' => ['sometimes', 'array:cs,en'],
            'client_meta' => ['nullable', 'array'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->prepareBilingualName(GlobalProfessionCategory::class, true);
    }

    public function withValidator($validator): void
    {
        $this->validateAllowedApiV2Fields($validator, ['cs', 'en', 'name', 'client_meta']);
    }
}
