<?php

namespace App\Services;

use Exception;

class Geonames
{
    public function search($query)
    {
        if (empty($query)) {
            throw new Exception('Neplatný formát', 400);
        }

        $url = 'https://secure.geonames.org/searchJSON?maxRows=10&username=' . config('hiko.geonames_username') . '&q=' . urlencode($query);

        try {
            $result = json_decode(file_get_contents($url));
        } catch (Exception $e) {
            throw new Exception('Geonames není dostupné', 500);
        }

        if ($result->totalResultsCount === 0) {
            throw new Exception('Źádné výsledky', 404);
        }

        return collect($result->geonames)->map(function ($place) {
            return [
                'adminName' => $place->adminName1,
                'country' => isset($place->countryName) ? $place->countryName : '',
                'latitude' => $place->lat,
                'longitude' => $place->lng,
                'name' => $place->name,
                'id' => $place->geonameId,
            ];
        });
    }
}
