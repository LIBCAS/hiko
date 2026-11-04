<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NationalityExpansionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    public function rules(): array
    {
        return [
            'source_nationality_id' => ['bail', 'required', 'integer', 'exists:nationalities,id'],
            'target_nationality_id' => ['bail', 'required', 'integer', 'exists:nationalities,id', 'different:source_nationality_id',
                Rule::unique('nationality_search_expansions', 'target_nationality_id')->where('source_nationality_id', $this->input('source_nationality_id'))],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => __('hiko.nationality_expansion_required'),
            '*.integer' => __('hiko.nationality_expansion_invalid'),
            '*.exists' => __('hiko.nationality_expansion_invalid'),
            'target_nationality_id.unique' => __('hiko.nationality_expansion_duplicate'),
            'target_nationality_id.different' => __('hiko.nationality_expansion_different'),
        ];
    }

    public function attributes(): array
    {
        return [
            'source_nationality_id' => __('hiko.nationality_expansion_source'),
            'target_nationality_id' => __('hiko.nationality_expansion_target'),
        ];
    }
}
