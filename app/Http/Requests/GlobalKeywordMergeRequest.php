<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalKeywordMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-users') ?? false;
    }

    public function rules(): array
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : '';
        $table = $tenantPrefix ? "{$tenantPrefix}__keywords" : 'keywords';

        return [
            'criteria' => ['required', 'array'],
            'selected_keywords' => ['required', 'array', 'min:1'],
            'selected_keywords.*' => ['integer', "exists:{$table},id"],
        ];
    }
}
