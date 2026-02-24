<?php

namespace App\Http\Requests;

use App\Enums\IdentityType;
use App\Models\GlobalProfession;
use App\Models\GlobalIdentity;
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
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($isTenancyInitialized) {
                    foreach ($value as $professionId) {
                        $isGlobal = str_starts_with($professionId, 'global-');
                        $cleanProfessionId = str_replace(['global-', 'local-'], '', $professionId);

                        if ($isGlobal) {
                            // Validate against the global table
                            Tenancy::central(function () use ($cleanProfessionId, $fail) {
                                if (!GlobalProfession::find($cleanProfessionId)) {
                                    $fail(__('hiko.profession_invalid_global'));
                                }
                            });
                        } else {
                            // Validate against the tenant-specific table if tenancy is initialized
                            if ($isTenancyInitialized && !Profession::find($cleanProfessionId)) {
                                $fail(__('hiko.profession_invalid_local'));
                            }
                        }
                    }
                },
            ],
            'local_professions' => ['sometimes', 'array'],
            'local_professions.*' => ['integer', 'exists:' . tenancy()->tenant->table_prefix . '__professions,id'],
            'global_professions' => ['sometimes', 'array'],
            'global_professions.*' => ['integer', 'exists:global_professions,id'],
            'religions'   => ['nullable', 'array'],
            'religions.*' => [
                'integer',
                Rule::exists('religions', 'id')->where(fn($q) => $q->where('is_active', 1)),
            ],
            'global_identity_id' => [
                'nullable',
                'integer',
                'exists:global_identities,id',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $globalIdentity = GlobalIdentity::find((int)$value);
                    if (!$globalIdentity) {
                        $fail(__('hiko.global_identity_invalid'));
                        return;
                    }

                    if ($this->input('type') !== $globalIdentity->type) {
                        $fail(__('hiko.global_identity_type_mismatch'));
                    }
                },
            ],
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

        // Clean religions to drop blanks and cast to ints
        $religions = $this->input('religions');
        if (is_array($religions)) {
            $religions = array_values(array_filter($religions, fn($v) => $v !== null && $v !== '' && $v !== false));
            $religions = array_map(fn($v) => (int) $v, $religions);
            $religions = empty($religions) ? null : $religions;
        } else {
            $religions = null;
        }

        $this->merge([
            'related_names' => is_array($relatedNames) ? $relatedNames : json_decode($relatedNames, true) ?? [],
            'related_identity_resources' => is_array($relatedResources) ? $relatedResources : json_decode($relatedResources, true) ?? [],
            'religions' => $religions,
            'global_identity_id' => $this->filled('global_identity_id') ? (int)$this->input('global_identity_id') : null,
        ]);
    }

    public function withValidator($validator)
    {
        $validator->sometimes('religions', ['nullable', 'array'], function () {
            return $this->input('type') === 'person';
        });

        $validator->after(function ($v) {
            if ($this->input('type') !== 'person' && $this->filled('religions')) {
                $v->errors()->add('religions', __('hiko.identities_religions_error'));
            }
        });
    }
}
