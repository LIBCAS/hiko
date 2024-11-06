<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Profession;
use App\Models\GlobalProfession;
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
            'surname' => ['required', 'string', 'max:255'],
            'forename' => ['nullable', 'string', 'max:255'],
            'general_name_modifier' => ['nullable', 'string', 'max:255'],
            'birth_year' => ['nullable', 'string', 'max:255'],
            'death_year' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'related_identity_resources' => ['nullable', 'array'],
            'related_names' => ['nullable', 'array'],
            'type' => ['required', 'string', 'max:255'],
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
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->input('type') === 'person') {
            $name = $this->input('surname');
            $name .= $this->input('forename') ? ", {$this->input('forename')}" : '';

            $this->merge([
                'category' => empty($this->input('category')) ? null : array_filter($this->input('category')),
                'profession' => empty($this->input('profession')) ? null : array_filter($this->input('profession')),
                'name' => $name,
            ]);
        }
    }
}
