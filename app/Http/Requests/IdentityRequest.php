<?php

namespace App\Http\Requests;

use App\Enums\IdentityType;
use App\Models\GlobalProfession;
use App\Models\Identity;
use App\Models\Profession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Stancl\Tenancy\Facades\Tenancy;

class IdentityRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->can('manage-metadata');
    }

    public function rules()
    {
        $isTenancyInitialized = tenancy()->initialized;

        return [
            'name' => ['required', 'string', 'max:255'],
            'surname' => $this->input('type') === IdentityType::Person->value ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'forename' => ['nullable', 'string', 'max:255'],
            'general_name_modifier' => ['nullable', 'string', 'max:255'],
            'birth_year' => ['nullable', 'string', 'max:255'],
            'death_year' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'related_identity_resources' => ['nullable', 'array'],
            'related_names' => ['nullable', 'array'],
            'type' => ['required', 'string', 'max:255', Rule::in(Identity::types())],
            'category' => ['nullable', 'exists:profession_categories,id'],
            'profession' => [
                'nullable', 'array',
                function ($attribute, $value, $fail) use ($isTenancyInitialized) {
                    foreach ($value as $professionId) {
                        $isGlobal = str_starts_with($professionId, 'global-');
                        $cleanProfessionId = str_replace(['global-', 'local-'], '', $professionId);

                        if ($isGlobal) {
                            // Validate against the global table
                            Tenancy::central(function () use ($cleanProfessionId, $fail) {
                                if (!GlobalProfession::find($cleanProfessionId)) {
                                    $fail(__('The selected profession is not valid (Global).'));
                                }
                            });
                        } else {
                            // Validate against the tenant-specific table if tenancy is initialized
                            if ($isTenancyInitialized && !Profession::find($cleanProfessionId)) {
                                $fail(__('The selected profession is not valid (Local).'));
                            }
                        }
                    }
                },
            ],
            'local_professions' => ['sometimes', 'array'],
            'local_professions.*' => ['integer', 'exists:' . tenancy()->tenant->table_prefix . '__professions,id'],
            'global_professions' => ['sometimes', 'array'],
            'global_professions.*' => ['integer', 'exists:global_professions,id'],
        ];
    }

    protected function prepareForValidation()
    {
        // If the type is person, adjust the name field
        if ($this->input('type') === IdentityType::Person->value) {
            $name = $this->input('surname');
            $name .= $this->input('forename') ? ", {$this->input('forename')}" : '';

            $this->merge(['name' => $name]);
        }

        // Handle category and profession fields to remove empty values or set to null if empty
        $this->merge([
            'category' => empty($this->input('category')) ? null : array_filter($this->input('category')),
            'profession' => empty($this->input('profession')) ? null : array_filter($this->input('profession')),
        ]);

        // Ensure related_names and related_identity_resources are arrays
        $relatedNames = $this->input('related_names');
        $relatedResources = $this->input('related_identity_resources');

        $this->merge([
            'related_names' => is_array($relatedNames) ? $relatedNames : json_decode($relatedNames, true) ?? [],
            'related_identity_resources' => is_array($relatedResources) ? $relatedResources : json_decode($relatedResources, true) ?? [],
        ]);
    }
}
