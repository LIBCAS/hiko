<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalPlaceMergeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-users') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : '';
        $placesTable = $tenantPrefix ? "{$tenantPrefix}__places" : 'places';

        return [
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*' => ['in:geoname_id,alternative_names,name_similarity,coordinates,country_and_name'],
            'name_similarity_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'country_and_name_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'latitude_tolerance' => ['nullable', 'numeric', 'min:0'],
            'longitude_tolerance' => ['nullable', 'numeric', 'min:0'],
            'selected_places' => ['required', 'array', 'min:1'],
            'selected_places.*' => ['integer', "exists:{$placesTable},id"],
            'merge_attrs' => ['nullable', 'array'],
            'merge_attrs.*' => ['array'],
            'merge_attrs.*.name' => ['nullable', 'in:local,global'],
            'merge_attrs.*.country' => ['nullable', 'in:local,global'],
            'merge_attrs.*.division' => ['nullable', 'in:local,global'],
            'merge_attrs.*.latitude' => ['nullable', 'in:local,global'],
            'merge_attrs.*.longitude' => ['nullable', 'in:local,global'],
            'merge_attrs.*.geoname_id' => ['nullable', 'in:local,global'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'criteria.required' => __('hiko.at_least_one_criterion_required'),
            'criteria.min' => __('hiko.at_least_one_criterion_required'),
            'selected_places.required' => __('hiko.at_least_one_place_required'),
            'selected_places.min' => __('hiko.at_least_one_place_required'),
        ];
    }
}
