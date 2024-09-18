<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IdentityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'forename' => 'nullable|string|max:255',
            'global_profession_id' => 'required|exists:global_professions,id',
            'global_profession_category_id' => 'required|exists:global_profession_categories,id',
        ];
    }
}
