<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveInterTenantLetterTransferRequest extends FormRequest
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
            'mappings.places' => ['nullable', 'array'],
            'mappings.keywords' => ['nullable', 'array'],
            'mappings.locations' => ['nullable', 'array'],
        ];
    }
}
