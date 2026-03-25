<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithApiV2;
use App\Models\Country;
use App\Models\GlobalPlace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GlobalPlaceRequest extends FormRequest
{
    use InteractsWithApiV2;

    public function rules(): array
    {
        return [
            'name' => $this->optionalOnApiV2Update(['required', 'string', 'max:255']),
            'additional_name' => ['nullable', 'string'],
            'country' => $this->optionalOnApiV2Update(['required', 'string', 'max:255', Rule::in(Country::names())]),
            'division' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'geoname_id' => ['nullable', 'integer'],
            'alternative_names' => ['nullable', 'array'],
            'client_meta' => ['nullable', 'array'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-users') ?? false;
    }

    public function prepareForValidation(): void
    {
        $payload = [];

        if ($this->exists('name')) {
            $payload['name'] = trim((string) $this->input('name'));
        }

        if ($this->exists('country')) {
            $payload['country'] = trim((string) $this->input('country'));
        }

        if ($this->exists('division')) {
            $payload['division'] = trim((string) $this->input('division'));
        }

        $this->merge($payload);
    }

    public function withValidator($validator): void
    {
        $this->validateAllowedApiV2Fields($validator, [
            'name',
            'additional_name',
            'country',
            'division',
            'note',
            'latitude',
            'longitude',
            'geoname_id',
            'alternative_names',
            'client_meta',
        ]);
    }

    public function failsDuplicateCheck(?int $excludeId = null, ?array $fallback = null): bool
    {
        $fallback = $fallback ?? [];
        $name = $this->input('name', $fallback['name'] ?? null);
        $country = $this->input('country', $fallback['country'] ?? null);
        $division = $this->input('division', $fallback['division'] ?? '');
        $latitude = $this->input('latitude', $fallback['latitude'] ?? null);
        $longitude = $this->input('longitude', $fallback['longitude'] ?? null);
        $geonameId = $this->input('geoname_id', $fallback['geoname_id'] ?? null);

        return GlobalPlace::query()
            ->where(function ($query) use ($name, $country, $division, $latitude, $longitude, $geonameId) {
                // Compare by normalized name + country + division
                $query->where(function ($q) use ($name, $country, $division) {
                    $q->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $name)])
                    ->whereRaw('LOWER(country) = ?', [mb_strtolower((string) $country)])
                    ->whereRaw('LOWER(COALESCE(division, "")) = ?', [mb_strtolower((string) $division)]);
                });

                // Compare by coordinates (if present)
                if ($latitude !== null && $longitude !== null && $latitude !== '' && $longitude !== '') {
                    $query->orWhere(function ($q) use ($latitude, $longitude) {
                        $q->where('latitude', $latitude)
                        ->where('longitude', $longitude);
                    });
                }

                // Compare by Geoname ID (if present)
                if ($geonameId !== null && $geonameId !== '') {
                    $query->orWhere('geoname_id', $geonameId);
                }
            })
            // Exclude the record being edited
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
