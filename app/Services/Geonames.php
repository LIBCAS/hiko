<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Geonames
{
    protected $username;

    public function __construct()
    {
        $this->username = env('GEONAMES_USERNAME', 'hiko_cz');
    }

    /**
     * Search for places by name.
     *
     * @throws Exception
     */
    public function search($query): Collection
    {
        if (empty($query)) {
            throw new Exception(
                __('validation.min.string', [
                    'attribute' => __('hiko.query'),
                    'min' => '0',
                ]),
                400
            );
        }

        $url = 'https://secure.geonames.org/searchJSON';
        $response = Http::get($url, [
            'q' => $query,
            'maxRows' => 10,
            'username' => $this->username
        ]);

        if ($response->failed()) {
            throw new Exception(__('hiko.geonames_unavailable'), 503);
        }

        $result = $response->json();

        if ($result['totalResultsCount'] === 0) {
            throw new Exception(__('hiko.items_not_found'), 404);
        }

        return collect($result['geonames'])->map(function ($place) {
            return [
                'adminName' => $place['adminName1'],
                'country' => $place['countryName'] ?? '',
                'latitude' => $place['lat'],
                'longitude' => $place['lng'],
                'name' => $place['name'],
                'id' => $place['geonameId'],
            ];
        });
    }
    public function fetchAlternativeNames($geonameId): array
    {
        if (!$geonameId) {
            return [];
        }
    
        $url = 'https://secure.geonames.org/getJSON';
        $response = Http::get($url, [
            'geonameId' => $geonameId,
            'username' => $this->username,
        ]);
    
        if ($response->failed() || !$response->json()) {
            \Log::error("Geonames API failed for geonameId: $geonameId");
            return [];
        }
    
        $data = $response->json();
    
        return isset($data['alternateNames']) && is_array($data['alternateNames'])
            ? collect($data['alternateNames'])
                ->pluck('name')
                ->filter(fn($name) => is_string($name))
                ->unique()
                ->values()
                ->toArray()
            : [];
    }
    
}
