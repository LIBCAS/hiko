<?php

namespace App\Services;

use App\Models\Place;
use App\Services\Geonames;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PlaceService
{
    public function __construct(protected Geonames $geonames)
    {
    }

    public function create(array $data): Place
    {
        $place = Place::create($data);
        $place->alternative_names = $this->fetchAlternativeNames($data['geoname_id'] ?? null);
        $place->save();

        return $place;
    }

    public function update(Place $place, array $data): Place
    {
        $data['alternative_names'] = $this->fetchAlternativeNames($data['geoname_id'] ?? $place->geoname_id);
        $place->update($data);

        return $place;
    }

    protected function fetchAlternativeNames(?int $geonameId): array
    {
        if (!$geonameId) {
            Log::info('No geoname ID provided, skipping alternative names fetch.');
            return [];
        }

        $alternativeNames = $this->geonames->fetchAlternativeNames($geonameId);

        if (!is_array($alternativeNames)) {
            Log::error('Alternative names fetched are not an array:', ['alternative_names' => $alternativeNames]);
            return [];
        }

        Log::info('Fetched alternative names:', ['alternative_names' => $alternativeNames]);
        return $alternativeNames;
    }

    /**
     * Check if a place has potential duplicates in the database.
     * @param Place $place
     * @return \Illuminate\Database\Eloquent\Builder<Place>[]|Collection
     */
    public function findDuplicates(Place $place): Collection
    {
        return Place::query()
            ->where('id', '!=', $place->id)
            ->where(function ($query) use ($place) {
                // Name + Country + Division Match
                $query->where(function ($q) use ($place) {
                    $q->whereRaw('LOWER(name) = ?', [mb_strtolower($place->name)])
                      ->whereRaw('LOWER(country) = ?', [mb_strtolower($place->country)]);

                    if ($place->division) {
                         $q->whereRaw('LOWER(division) = ?', [mb_strtolower($place->division)]);
                    }
                });

                // Geoname ID Match
                if ($place->geoname_id) {
                    $query->orWhere('geoname_id', $place->geoname_id);
                }
            })
            ->get();
    }
}
