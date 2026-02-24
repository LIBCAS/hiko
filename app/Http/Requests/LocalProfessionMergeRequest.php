<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocalProfessionMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-metadata') ?? false;
    }

    public function rules(): array
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;
        $professionsTable = $tenantPrefix . '__professions';
        $categoriesTable = $tenantPrefix . '__profession_categories';

        return [
            'target_id' => ['required', 'integer', "exists:{$professionsTable},id"],
            'source_ids' => ['required', 'array', 'min:1'],
            'source_ids.*' => ['integer', "exists:{$professionsTable},id", 'different:target_id'],
            'attributes' => ['required', 'array'],
            'attributes.cs' => ['nullable', 'string', 'max:255', 'required_without:attributes.en'],
            'attributes.en' => ['nullable', 'string', 'max:255', 'required_without:attributes.cs'],
            'attributes.profession_category_id' => ['required', 'integer', "exists:{$categoriesTable},id"],
        ];
    }
}
