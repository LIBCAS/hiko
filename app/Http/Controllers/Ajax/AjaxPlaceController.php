<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class AjaxPlaceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query('search');
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : 'global';

        if (empty($query)) {
            return response()->json([], Response::HTTP_OK);
        }

        try {
            // Query tenant-specific places
            $results = DB::table("{$tenantPrefix}__places")
                ->where('name', 'like', '%' . $query . '%')
                ->orWhere('alternative_names', 'like', '%' . $query . '%')
                ->get(['id', 'name', 'country', 'division']);

            // Format results for dropdown
            $formattedResults = $results->map(function ($place) {
                return [
                    'id' => $place->id,
                    'value' => $place->id,
                    'label' => "{$place->name}, {$place->division}, {$place->country}",
                ];
            });

            return response()->json($formattedResults->toArray(), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
