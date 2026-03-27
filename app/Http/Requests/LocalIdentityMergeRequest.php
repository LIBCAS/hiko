<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Identity;
use Illuminate\Validation\Rule;

class LocalIdentityMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAbility('manage-metadata');
    }

    public function rules(): array
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;
        $table = $tenantPrefix . '__identities';
        $mergeIds = array_values(array_filter(array_merge(
            [$this->input('target_id')],
            $this->input('source_ids', [])
        ), fn($id) => $id !== null && $id !== ''));

        $isPerson = $this->input('attributes.type') === 'person';

        return [
            'target_id' => ['required', 'integer', "exists:{$table},id"],
            'source_ids' => ['required', 'array', 'min:1'],
            'source_ids.*' => ['integer', "exists:{$table},id", 'different:target_id'],

            // Scalar attributes selected by user
            'attributes' => ['required', 'array'],
            'attributes.type' => ['required', 'string', Rule::in(Identity::types())],

            // Name is required for institutions, ignored for persons (auto-generated)
            'attributes.name' => [$isPerson ? 'nullable' : 'required', 'string', 'max:255'],

            // Surname is required for persons, nullable for institutions
            'attributes.surname' => [$isPerson ? 'required' : 'nullable', 'string', 'max:255'],

            'attributes.forename' => ['nullable', 'string', 'max:255'],
            'attributes.birth_year' => ['nullable', 'string', 'max:255'],
            'attributes.death_year' => ['nullable', 'string', 'max:255'],
            'attributes.nationality' => ['nullable', 'string', 'max:255'],
            'attributes.gender' => ['nullable', 'string', 'max:255'],
            'attributes.viaf_id' => ['nullable', 'string', 'max:255'],

            // For complex relations, send the ID of the identity whose relations we want to keep, nullable because identities of type 'institution' do not use these relations
            'attributes.selected_profession_source_id' => ['nullable', 'integer', "exists:{$table},id", Rule::in($mergeIds)],
            'attributes.selected_religion_source_id' => ['nullable', 'integer', "exists:{$table},id", Rule::in($mergeIds)],
            'attributes.selected_global_identity_source_id' => ['nullable', 'integer', "exists:{$table},id", Rule::in($mergeIds)],
        ];
    }
}
