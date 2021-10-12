<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GeonamesController extends Controller
{
    public function __invoke(Request $request)
    {
        if (empty($request->query('search'))) {
            return [];
        }

        $url = 'https://secure.geonames.org/searchJSON?maxRows=10&username=' . config('hiko.geonames_username') . '&q=' . urlencode($request->query('search'));

        // TODO: handle errors
        $result = json_decode(file_get_contents($url));

        if ($result->totalResultsCount === 1) {
            return response()->json('', 404);
        }

        return collect($result->geonames)->map(function ($place) {
            return [
                'adminName' => $place->adminName1,
                'country' => $place->countryName,
                'latitude' => $place->lat,
                'longitude' => $place->lng,
                'name' => $place->name,
                'id' => $place->geonameId,
            ];
        });
    }
}
