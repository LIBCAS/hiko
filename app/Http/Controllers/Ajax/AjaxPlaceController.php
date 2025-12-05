<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\GlobalPlace;
use Symfony\Component\HttpFoundation\Response;

class AjaxPlaceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $searchTerm = mb_strtolower($request->query('search'));

        if (empty($searchTerm)) {
            return response()->json([], Response::HTTP_OK);
        }

        try {
            // Query tenant-specific (local) places
            $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : 'global';

            $localPlaces = DB::table("{$tenantPrefix}__places")
                ->where(function($query) use ($searchTerm) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                          ->orWhereRaw('LOWER(alternative_names) LIKE ?', ['%' . $searchTerm . '%']);
                })
                ->get(['id', 'name', 'country', 'division']);

            $localResults = $localPlaces->map(function ($place) {
                $labelParts = [$place->name];

                if (!empty($place->division)) {
                    $labelParts[] = $place->division;
                }

                if (!empty($place->country)) {
                    $labelParts[] = $place->country;
                }

                $label = implode(', ', array_filter($labelParts));

                return [
                    'id' => 'local-' . $place->id,
                    'value' => 'local-' . $place->id,
                    'label' => $label . ' (' . __('hiko.local') . ')',
                    'type' => __('hiko.local')
                ];
            });

            // Query global places
            $globalPlaces = DB::table('global_places')
                ->where(function($query) use ($searchTerm) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                          ->orWhereRaw('LOWER(alternative_names) LIKE ?', ['%' . $searchTerm . '%']);
                })
                ->get(['id', 'name', 'country', 'division']);

            $globalResults = $globalPlaces->map(function ($place) {
                $labelParts = [$place->name];

                if (!empty($place->division)) {
                    $labelParts[] = $place->division;
                }

                if (!empty($place->country)) {
                    $labelParts[] = $place->country;
                }

                $label = implode(', ', array_filter($labelParts));

                return [
                    'id' => 'global-' . $place->id,
                    'value' => 'global-' . $place->id,
                    'label' => $label . ' (' . __('hiko.global') . ')',
                    'type' => __('hiko.global')
                ];
            });

            // Merge and sort by label
            $allResults = $localResults
                ->merge($globalResults)
                ->sortBy('label')
                ->values();

            return response()->json($allResults->toArray(), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
