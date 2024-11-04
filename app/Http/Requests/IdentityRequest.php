<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Stancl\Tenancy\Facades\Tenancy;

class IdentityRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->hasAbility('manage-metadata');
    }

    public function rules()
    {
        // Check if the tenancy is initialized
        $isTenancyInitialized = tenancy()->initialized;

        if ($this->type === 'institution') {
            return [
                'name' => ['required', 'string', 'max:255'],
                'note' => ['nullable'],
                'viaf_id' => ['nullable', 'integer', 'numeric'],
                'type' => ['required', 'string', 'max:255'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'forename' => ['nullable', 'string', 'max:255'],
            'general_name_modifier' => ['nullable', 'string', 'max:255'],
            'birth_year' => ['nullable', 'string', 'max:255'],
            'death_year' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable'],
            'related_identity_resources' => ['nullable'],
            'related_names' => ['nullable'],
            'type' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'exists:profession_categories,id'],
            'profession' => [
                'nullable',
                $isTenancyInitialized
                    ? 'exists:' . $this->getTenantPrefix() . '__professions,id'
                    : 'exists:global_professions,id',
            ],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->type === 'person') {
            $name = $this->surname;
            $name .= $this->forename ? ", {$this->forename}" : '';

            $this->merge([
                'category' => empty($this->category) ? null : array_filter($this->category),
                'profession' => empty($this->profession) ? null : array_filter($this->profession),
                'name' => $name,
            ]);
        }
    }

    /**
     * Get the tenant prefix for table naming.
     *
     * @return string
     */
    protected function getTenantPrefix(): string
    {
        return tenancy()->tenant->table_prefix ?? '';
    }
}
