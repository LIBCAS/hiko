<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocalKeywordMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-metadata') ?? false;
    }

    public function rules(): array
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;
        $keywordsTable = $tenantPrefix . '__keywords';
        $categoriesTable = $tenantPrefix . '__keyword_categories';

        return [
            'target_id' => ['required', 'integer', "exists:{$keywordsTable},id"],
            'source_ids' => ['required', 'array', 'min:1'],
            'source_ids.*' => ['integer', "exists:{$keywordsTable},id", 'different:target_id'],
            'attributes' => ['required', 'array'],
            'attributes.cs' => ['nullable', 'string', 'max:255', 'required_without:attributes.en'],
            'attributes.en' => ['nullable', 'string', 'max:255', 'required_without:attributes.cs'],
            'attributes.keyword_category_id' => ['nullable', 'integer', "exists:{$categoriesTable},id"],
        ];
    }
}
