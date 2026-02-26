<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Locations",
    description: "Management of locations (repositories, archives, collections)"
)]
class LocationController extends Controller
{
    public static int $maxPerPage = 100;
    public static int $defaultPerPage = 20;

    #[OA\Get(
        path: "/locations",
        summary: "List locations",
        tags: ["Locations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of locations",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Location")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', self::$defaultPerPage), 1), self::$maxPerPage);
        $locations = Location::paginate($perPage);

        return LocationResource::collection($locations);
    }

    #[OA\Get(
        path: "/locations/{id}",
        summary: "Get location by ID",
        tags: ["Locations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Location details",
                content: new OA\JsonContent(ref: "#/components/schemas/Location")
            ),
            new OA\Response(response: 404, description: "Location not found")
        ]
    )]
    public function show($id)
    {
        $location = Location::findOrFail($id);
        return new LocationResource($location);
    }

    #[OA\Post(
        path: "/locations",
        summary: "Create new location",
        tags: ["Locations"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/Location")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Location created",
                content: new OA\JsonContent(ref: "#/components/schemas/Location")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 409, description: "Entity already exists"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
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
                'message' => __('hiko.entity_already_exists'),
            ], 409);
        }

        $location = Location::create($validated);

        return (new LocationResource($location))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/locations/{id}",
        summary: "Update location",
        tags: ["Locations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/Location")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Location updated",
                content: new OA\JsonContent(ref: "#/components/schemas/Location")
            ),
            new OA\Response(response: 404, description: "Location not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
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
                'message' => __('hiko.entity_already_exists'),
            ], 422);
        }

        $location->update($validated);
        return new LocationResource($location);
    }

    #[OA\Delete(
        path: "/locations/{id}",
        summary: "Delete location",
        tags: ["Locations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Location deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Location not found")
        ]
    )]
    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        $location->delete();

        return response()->json(['message' => __('hiko.removed')]);
    }
}
