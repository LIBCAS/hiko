<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Country;
use Illuminate\Validation\Rule;

class LocalPlaceMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAbility('manage-metadata');
    }

    public function rules(): array
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;
        $table = $tenantPrefix . '__places';

        return [
            // The ID of the place that will stay (survivor)
            'target_id' => ['required', 'integer', "exists:{$table},id"],

            // The IDs of places to delete
            'source_ids' => ['required', 'array', 'min:1'],
            'source_ids.*' => ['integer', "exists:{$table},id", 'different:target_id'],

            // The final attributes for the survivor
            'attributes' => ['required', 'array'],
            'attributes.name' => ['required', 'string', 'max:255'],
            'attributes.country' => ['required', 'string', Rule::in(Country::names())],
            'attributes.division' => ['nullable', 'string', 'max:255'],
            'attributes.latitude' => ['nullable', 'numeric'],
            'attributes.longitude' => ['nullable', 'numeric'],
            'attributes.geoname_id' => ['nullable', 'integer'],
            'attributes.note' => ['nullable', 'string'],
            'attributes.additional_name' => ['nullable', 'string', 'max:255'],

            // Alternative names: user selects ONE array from the sources/target
            'attributes.alternative_names' => ['nullable', 'array'],
        ];
    }
}
