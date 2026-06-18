<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveInterTenantLetterTransferMappingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-users') ?? false;
    }

    public function rules(): array
    {
        return [
            'mappings' => ['nullable', 'array'],
            'mappings.identities' => ['nullable', 'array'],
            'mappings.identities.*' => ['nullable', 'string', 'max:64'],
            'mappings.places' => ['nullable', 'array'],
            'mappings.places.*' => ['nullable', 'string', 'max:64'],
            'mappings.keywords' => ['nullable', 'array'],
            'mappings.keywords.*' => ['nullable', 'string', 'max:64'],
            'mappings.locations' => ['nullable', 'array'],
            'mappings.locations.*' => ['nullable', 'string', 'max:64'],
        ];
    }
}
