<?php

namespace App\Http\Requests;

use App\Enums\IdentityType;
use App\Models\GlobalIdentity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GlobalIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAbility('manage-users');
    }

    public function rules(): array
    {
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
            'viaf_id' => ['nullable', 'string', 'max:255'],
            'related_identity_resources' => ['nullable', 'array'],
            'related_names' => ['nullable', 'array'],
            'type' => ['required', 'string', 'max:255', Rule::in(GlobalIdentity::types())],
            'professions' => ['nullable', 'array'],
            'professions.*' => ['integer', 'exists:global_professions,id'],
            'religions'   => ['nullable', 'array'],
            'religions.*' => [
                'integer',
                Rule::exists('religions', 'id')->where(fn($q) => $q->where('is_active', 1)),
            ],
        ];
    }

    protected function prepareForValidation()
    {
        // If the type is person, construct the display name
        if ($this->input('type') === IdentityType::Person->value) {
            $name = $this->input('surname');
            $name .= $this->input('forename') ? ", {$this->input('forename')}" : '';
            $this->merge(['name' => $name]);
        }

        $this->merge([
            'professions' => array_filter($this->input('professions', [])),
            'related_names' => $this->prepareJsonField('related_names'),
            'related_identity_resources' => $this->prepareJsonField('related_identity_resources'),
            'religions' => $this->prepareReligions($this->input('religions')),
        ]);
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

    public function withValidator($validator): void
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
