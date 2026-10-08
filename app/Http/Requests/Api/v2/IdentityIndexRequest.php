<?php

namespace App\Http\Requests\Api\v2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates query filters for local and global identity listings.
 */
class IdentityIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authentication and tenant access are enforced by the API route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.nationality' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.nationality_match' => ['sometimes', 'string', 'in:direct,expanded'],
        ];
    }

    public function filters(): array
    {
        return $this->safe()->only(['filter.nationality', 'filter.nationality_match'])['filter'] ?? [];
    }
}
