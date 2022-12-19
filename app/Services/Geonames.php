<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;

class Geonames
{
    /**
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

        $url = 'https://secure.geonames.org/searchJSON?maxRows=10&username=' . config('hiko.geonames_username') . '&q=' . urlencode($query);

        try {
            $result = json_decode(file_get_contents($url));
        } catch (Exception $e) {
            throw new Exception(__('hiko.geonames_unavailable'), 503);
        }

        if ($result->totalResultsCount === 0) {
            throw new Exception(__('hiko.items_not_found'), 404);
        }

        return collect($result->geonames)->map(function ($place) {
            return [
                'adminName' => $place->adminName1,
                'country' => $place->countryName ?? '',
                'latitude' => $place->lat,
                'longitude' => $place->lng,
                'name' => $place->name,
                'id' => $place->geonameId,
            ];
        });
    }
}
