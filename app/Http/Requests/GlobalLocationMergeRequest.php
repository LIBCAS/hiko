<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalLocationMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAbility('manage-users');
    }

    public function rules(): array
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : '';
        $table = $tenantPrefix ? "{$tenantPrefix}__locations" : 'locations';

        return [
            'criteria' => ['required', 'array'],
            'selected_locations' => ['required', 'array', 'min:1'],
            'selected_locations.*' => ['integer', "exists:{$table},id"],
            'merge_attrs' => ['nullable', 'array'],
        ];
    }
}
