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
            'source_nationality_id' => ['required', 'integer', 'exists:nationalities,id'],
            'target_nationality_id' => ['required', 'integer', 'exists:nationalities,id', 'different:source_nationality_id',
                Rule::unique('nationality_search_expansions', 'target_nationality_id')->where('source_nationality_id', $this->input('source_nationality_id'))],
        ];
    }
}
