<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithApiV2;
use App\Enums\IdentityType;
use App\Models\GlobalIdentity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GlobalIdentityRequest extends FormRequest
{
    use InteractsWithApiV2;

    public function authorize(): bool
    {
        return auth()->user()->hasAbility('manage-users');
    }

    public function rules(): array
    {
        $nameRule = $this->optionalOnApiV2Update(['required', 'string', 'max:255']);
        $personSurnameRule = $this->input('type') === IdentityType::Person->value
            ? $this->optionalOnApiV2Update(['required', 'string', 'max:255'])
            : ['nullable', 'string', 'max:255'];
        $typeRule = $this->optionalOnApiV2Update(['required', 'string', 'max:255', Rule::in(GlobalIdentity::types())]);

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
            'viaf_id' => ['nullable', 'string', 'max:255'],
            'related_identity_resources' => ['nullable', 'array'],
            'related_names' => ['nullable', 'array'],
            'type' => $typeRule,
            'professions' => ['nullable', 'array'],
            'professions.*' => ['integer', 'exists:global_professions,id'],
            'religions'   => ['nullable', 'array'],
            'religions.*' => [
                'integer',
                Rule::exists('religions', 'id')->where(fn($q) => $q->where('is_active', 1)),
            ],
            'client_meta' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation()
    {
        // If the type is person, construct the display name
        if ($this->input('type') === IdentityType::Person->value && ($this->isMethod('POST') || $this->exists('surname') || $this->exists('forename'))) {
            $name = $this->input('surname');
            $name .= $this->input('forename') ? ", {$this->input('forename')}" : '';
            $this->merge(['name' => $name]);
        }

        $payload = [];

        if ($this->exists('professions')) {
            $payload['professions'] = $this->prepareProfessions($this->input('professions'));
        }

        if ($this->exists('related_names')) {
            $payload['related_names'] = $this->prepareJsonField('related_names');
        }

        if ($this->exists('related_identity_resources')) {
            $payload['related_identity_resources'] = $this->prepareJsonField('related_identity_resources');
        }

        if ($this->exists('religions')) {
            $payload['religions'] = $this->prepareReligions($this->input('religions'));
        }

        $this->merge($payload);
    }

    protected function prepareJsonField($key)
    {
        $value = $this->input($key);
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return is_array($value) ? $value : [];
    }

    protected function prepareReligions($religions): ?array
    {
        if (!is_array($religions)) {
            return null;
        }

        $religions = array_values(array_filter($religions, fn($v) => $v !== null && $v !== '' && $v !== false));
        $religions = array_map(fn($v) => (int)$v, $religions);

        return empty($religions) ? null : $religions;
    }

    protected function prepareProfessions($professions): array
    {
        if (!is_array($professions)) {
            return [];
        }

        $ids = [];

        foreach ($professions as $profession) {
            if (is_array($profession)) {
                $scope = $profession['scope'] ?? null;
                $value = $profession['id'] ?? ($profession['value'] ?? null);

                if ($scope === 'local') {
                    $ids[] = '__invalid_local_scope__';
                    continue;
                }

                if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                    $ids[] = (int) $value;
                    continue;
                }

                if (is_string($value) && preg_match('/^global-(\d+)$/', $value, $matches)) {
                    $ids[] = (int) $matches[1];
                    continue;
                }

                $ids[] = '__invalid_profession__';
                continue;
            }

            if (is_int($profession) || (is_string($profession) && ctype_digit($profession))) {
                $ids[] = (int) $profession;
                continue;
            }

            if (is_string($profession) && preg_match('/^global-(\d+)$/', $profession, $matches)) {
                $ids[] = (int) $matches[1];
                continue;
            }

            if (is_string($profession) && str_starts_with($profession, 'local-')) {
                $ids[] = '__invalid_local_scope__';
                continue;
            }

            $ids[] = '__invalid_profession__';
        }

        return array_values(array_filter($ids, fn($v) => $v !== null && $v !== '' && $v !== false));
    }

    public function withValidator($validator): void
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
            'viaf_id',
            'related_identity_resources',
            'related_names',
            'type',
            'professions',
            'religions',
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
}
