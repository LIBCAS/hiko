<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalProfessionMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-users') ?? false;
    }

    public function rules(): array
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : '';
        $table = $tenantPrefix ? "{$tenantPrefix}__professions" : 'professions';

        return [
            'criteria' => ['required', 'array'],
            'selected_professions' => ['required', 'array', 'min:1'],
            'selected_professions.*' => ['integer', "exists:{$table},id"],
        ];
    }
}
