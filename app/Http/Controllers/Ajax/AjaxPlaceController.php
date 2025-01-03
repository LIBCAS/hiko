<?php

namespace App\Http\Controllers\Ajax;

use App\Services\Geonames;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class AjaxPlaceController extends Controller
{
    protected Geonames $geonames;

    public function __construct(Geonames $geonames)
    {
        $this->geonames = $geonames;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query('search');

        if (empty($query)) {
           return response()->json([], Response::HTTP_OK);
        }

        try {
            $results = $this->geonames->search($query);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $formattedResults = $results->map(function ($place) {
            return [
                'id' => $place['id'],
                'value' => $place['id'],
                'label' => "{$place['name']}, {$place['adminName']}, {$place['country']}",
            ];
        })->toArray();

        return response()->json($formattedResults, Response::HTTP_OK);
    }
}
