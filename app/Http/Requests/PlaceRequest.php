<?php

namespace App\Http\Requests;

use App\Models\Country;
use App\Models\Place;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255', Rule::in(Country::names())],
            'division' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'geoname_id' => ['nullable', 'integer'],
            'alternative_names' => ['nullable', 'array'],
        ];
    }

    public function authorize(): bool
    {
        return auth()->user()->hasAbility('manage-metadata');
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->input('name')),
            'country' => trim($this->input('country')),
            'division' => trim($this->input('division')),
        ]);
    }

    public function failsDuplicateCheck(?int $excludeId = null): bool
    {
        $query = Place::query();

        $query->where(function ($q) {
            $q->whereRaw('LOWER(name) = ?', [mb_strtolower($this->input('name'))])
              ->whereRaw('LOWER(country) = ?', [mb_strtolower($this->input('country'))])
              ->whereRaw('LOWER(division) <=> ?', [mb_strtolower($this->input('division', ''))]);
        });

        if ($this->filled('latitude') && $this->filled('longitude')) {
            $query->orWhere(function ($q) {
                $q->where('latitude', $this->input('latitude'))
                  ->where('longitude', $this->input('longitude'));
            });
        }

        if ($this->filled('geoname_id')) {
            $query->orWhere('geoname_id', $this->input('geoname_id'));
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
