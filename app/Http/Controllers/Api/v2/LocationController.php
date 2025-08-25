<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public static int $maxPerPage = 100;
    public static int $defaultPerPage = 20;

    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', self::$defaultPerPage), 1), self::$maxPerPage);
        $locations = Location::paginate($perPage);

        return response()->json($locations);
    }

    public function show($id)
    {
        $location = Location::findOrFail($id);
        return response()->json($location);
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => trim($request->input('name')),
            'type' => trim($request->input('type')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(Location::types())],
        ]);

        $exists = Location::whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
                          ->where('type', $validated['type'])
                          ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Such entity already exists.',
            ], 409);
        }

        $location = Location::create($validated);
        return response()->json($location, 201);
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'name' => trim($request->input('name')),
            'type' => trim($request->input('type')),
        ]);

        $location = Location::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', Rule::in(Location::types())],
        ]);

        $name = $validated['name'] ?? $location->name;
        $type = $validated['type'] ?? $location->type;

        $exists = Location::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                          ->where('type', $type)
                          ->where('id', '!=', $location->id)
                          ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Such entity already exists.',
            ], 422);
        }

        $location->update($validated);
        return response()->json($location);
    }

    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        $location->delete();

        return response()->json(['message' => 'Entity deleted successfully.']);
    }
}
