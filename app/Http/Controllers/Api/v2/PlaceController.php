<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceRequest;
use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public static int $maxPerPage = 100;
    public static int $defaultPerPage = 20;

    protected PlaceService $placeService;

    public function __construct(PlaceService $placeService)
    {
        $this->placeService = $placeService;
    }

    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', self::$defaultPerPage), 1), self::$maxPerPage);
        $places = Place::paginate($perPage);

        return response()->json($places);
    }

    public function show($id)
    {
        $place = Place::findOrFail($id);
        return response()->json($place);
    }

    public function store(PlaceRequest $request)
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return response()->json(['message' => 'Such entity already exists.'], 409);
        }

        $place = $this->placeService->create($validated);
        return response()->json($place, 201);
    }

    public function update(PlaceRequest $request, $id)
    {
        $place = Place::findOrFail($id);
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($place->id)) {
            return response()->json(['message' => 'Such entity already exists.'], 422);
        }

        $updated = $this->placeService->update($place, $validated);
        return response()->json($updated);
    }

    public function destroy($id)
    {
        $place = Place::findOrFail($id);
        $place->delete();

        return response()->json(['message' => 'Entity deleted successfully.']);
    }
}
