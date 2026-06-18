<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterTenantLetterTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-users') ?? false;
    }

    public function rules(): array
    {
        return [
            'target_tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'letter_ids' => [
                'required',
                'array',
                'min:1',
                'max:' . config('inter_tenant_transfers.max_letters', 200),
            ],
            'letter_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
