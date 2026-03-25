<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithApiV2;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Models\ProfessionCategory;

class ProfessionCategoryRequest extends FormRequest
{
    use InteractsWithApiV2;

    public function rules(): array
    {
        $csRules = $this->isApiV2UpdateRequest()
            ? ['sometimes', 'nullable', 'string', 'max:255']
            : ['nullable', 'string', 'max:255', 'required_without:en'];
        $enRules = $this->isApiV2UpdateRequest()
            ? ['sometimes', 'nullable', 'string', 'max:255']
            : ['nullable', 'string', 'max:255', 'required_without:cs'];

        return [
            'cs' => $csRules,
            'en' => $enRules,
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

        $this->merge($payload);
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
