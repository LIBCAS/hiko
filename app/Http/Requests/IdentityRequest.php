<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithApiV2;
use App\Enums\IdentityType;
use App\Models\GlobalProfession;
use App\Models\GlobalIdentity;
use App\Models\Identity;
use App\Models\Profession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Stancl\Tenancy\Facades\Tenancy;

class IdentityRequest extends FormRequest
{
    use InteractsWithApiV2;

    public function authorize()
    {
        return auth()->user()->can('manage-metadata');
    }

    public function rules()
    {
        $isTenancyInitialized = tenancy()->initialized;
        $nameRule = $this->optionalOnApiV2Update(['required', 'string', 'max:255']);
        $personSurnameRule = $this->input('type') === IdentityType::Person->value
            ? $this->optionalOnApiV2Update(['required', 'string', 'max:255'])
            : ['nullable', 'string', 'max:255'];
        $typeRule = $this->optionalOnApiV2Update(['required', 'string', 'max:255', Rule::in(Identity::types())]);

        return [
            'name' => $nameRule,
            'surname' => $personSurnameRule,
            'forename' => ['nullable', 'string', 'max:255'],
            'general_name_modifier' => ['nullable', 'string', 'max:255'],
            'birth_year' => ['nullable', 'string', 'max:255'],
            'death_year' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'related_identity_resources' => ['nullable', 'array'],
            'related_names' => ['nullable', 'array'],
            'type' => $typeRule,
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
            'professions' => ['nullable', 'array'],
            'professions.*.id' => ['required', $this->getProfessionValidationRule()],
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
            'client_meta' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation()
    {
        // If the type is person, adjust the name field
        if ($this->input('type') === IdentityType::Person->value && ($this->isMethod('POST') || $this->exists('surname') || $this->exists('forename'))) {
            $name = $this->input('surname');
            $name .= $this->input('forename') ? ", {$this->input('forename')}" : '';

            $this->merge(['name' => $name]);
        }

        $payload = [];

        if ($this->exists('category')) {
            $payload['category'] = empty($this->input('category')) ? null : array_filter($this->input('category'));
        }

        if ($this->exists('profession')) {
            $payload['profession'] = empty($this->input('profession')) ? null : array_filter($this->input('profession'));
        }

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

        if ($this->exists('related_names')) {
            $payload['related_names'] = is_array($relatedNames) ? $relatedNames : json_decode($relatedNames, true) ?? [];
        }

        if ($this->exists('related_identity_resources')) {
            $payload['related_identity_resources'] = is_array($relatedResources) ? $relatedResources : json_decode($relatedResources, true) ?? [];
        }

        if ($this->exists('professions') || $this->exists('local_professions') || $this->exists('global_professions') || $this->exists('profession')) {
            $payload['professions'] = $this->normalizeProfessionItems(
                $this->input('professions'),
                $this->input('local_professions'),
                $this->input('global_professions'),
                $this->input('profession')
            );
        }

        if ($this->exists('religions')) {
            $payload['religions'] = $religions;
        }

        if ($this->exists('global_identity') || $this->exists('global_identity_id')) {
            $payload['global_identity_id'] = $this->normalizeGlobalIdentityId(
                $this->input('global_identity'),
                $this->input('global_identity_id')
            );
        }

        $this->merge($payload);
    }

    public function withValidator($validator)
    {
        $this->validateAllowedApiV2Fields($validator, [
            'name',
            'surname',
            'forename',
            'general_name_modifier',
            'birth_year',
            'death_year',
            'nationality',
            'gender',
            'note',
            'related_identity_resources',
            'related_names',
            'type',
            'category',
            'profession',
            'professions',
            'local_professions',
            'global_professions',
            'religions',
            'global_identity',
            'global_identity_id',
            'client_meta',
        ]);

        $validator->sometimes('religions', ['nullable', 'array'], function () {
            return $this->input('type') === 'person';
        });

        $validator->after(function ($v) {
            if ($this->input('type') !== 'person' && $this->filled('religions')) {
                $v->errors()->add('religions', __('hiko.identities_religions_error'));
            }
        });
    }

    protected function getProfessionValidationRule()
    {
        return function ($attribute, $value, $fail) {
            if ($value === null || $value === '') {
                return;
            }

            $localTable = tenancy()->tenant->table_prefix . '__professions';

            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                if (!DB::table($localTable)->where('id', (int) $value)->exists()) {
                    $fail(__('hiko.profession_invalid_local'));
                }

                return;
            }

            if (is_string($value) && preg_match('/^(local|global)-(\d+)$/', $value, $matches)) {
                $scope = $matches[1];
                $id = (int) $matches[2];

                if ($scope === 'global') {
                    if (!DB::table('global_professions')->where('id', $id)->exists()) {
                        $fail(__('hiko.profession_invalid_global'));
                    }
                } else {
                    if (!DB::table($localTable)->where('id', $id)->exists()) {
                        $fail(__('hiko.profession_invalid_local'));
                    }
                }

                return;
            }

            $fail(__('hiko.profession_invalid_local'));
        };
    }

    protected function normalizeProfessionItems($professions, $legacyLocal = null, $legacyGlobal = null, $legacyCombined = null): ?array
    {
        $normalized = [];

        if (is_array($professions)) {
            foreach ($professions as $item) {
                $reference = $this->normalizeScopedReference(
                    is_array($item) ? ($item['id'] ?? ($item['value'] ?? null)) : $item,
                    is_array($item) ? ($item['scope'] ?? null) : null
                );

                if ($reference !== null) {
                    $normalized[] = ['id' => $reference];
                }
            }
        }

        if ($normalized !== []) {
            return $normalized;
        }

        foreach ((array) $legacyLocal as $id) {
            $reference = $this->normalizeScopedReference($id, 'local');
            if ($reference !== null) {
                $normalized[] = ['id' => $reference];
            }
        }

        foreach ((array) $legacyGlobal as $id) {
            $reference = $this->normalizeScopedReference($id, 'global');
            if ($reference !== null) {
                $normalized[] = ['id' => $reference];
            }
        }

        foreach ((array) $legacyCombined as $id) {
            $reference = $this->normalizeScopedReference($id, null);
            if ($reference !== null) {
                $normalized[] = ['id' => $reference];
            }
        }

        return $normalized === [] ? null : array_values($normalized);
    }

    protected function normalizeGlobalIdentityId($globalIdentity, $globalIdentityId): ?int
    {
        if (is_array($globalIdentity)) {
            $globalIdentity = $globalIdentity['id'] ?? ($globalIdentity['value'] ?? null);
        }

        if ($globalIdentity !== null && $globalIdentity !== '') {
            if (is_int($globalIdentity) || (is_string($globalIdentity) && ctype_digit($globalIdentity))) {
                return (int) $globalIdentity;
            }

            if (is_string($globalIdentity) && preg_match('/^global-(\d+)$/', $globalIdentity, $matches)) {
                return (int) $matches[1];
            }
        }

        return $this->filled('global_identity_id') ? (int) $globalIdentityId : null;
    }

    protected function normalizeScopedReference($value, ?string $scope): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return ($scope === 'global' ? 'global' : 'local') . '-' . (int) $value;
        }

        if (is_string($value) && preg_match('/^(local|global)-(\d+)$/', $value, $matches)) {
            return $matches[1] . '-' . (int) $matches[2];
        }

        return null;
    }
}
