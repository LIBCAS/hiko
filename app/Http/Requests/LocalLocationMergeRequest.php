<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocalLocationMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAbility('manage-metadata');
    }

    public function rules(): array
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;
        $table = $tenantPrefix . '__locations';

        return [
            // The ID of the location that will survive
            'target_id' => ['required', 'integer', "exists:{$table},id"],

            // The IDs of locations to delete
            'source_ids' => ['required', 'array', 'min:1'],
            'source_ids.*' => ['integer', "exists:{$table},id", 'different:target_id'],
        ];
    }
}
