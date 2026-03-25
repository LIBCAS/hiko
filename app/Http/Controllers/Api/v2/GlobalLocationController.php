<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\v2\Concerns\ValidatesApiV2Writes;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\GlobalLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Global Locations",
    description: "Management of global locations"
)]
class GlobalLocationController extends Controller
{
    use ValidatesApiV2Writes;

    public static int $maxPerPage = 100;
    public static int $defaultPerPage = 20;

    #[OA\Get(
        path: "/global-locations",
        summary: "List global locations",
        tags: ["Global Locations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of global locations",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/GlobalLocation")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', self::$defaultPerPage), 1), self::$maxPerPage);
        $locations = GlobalLocation::paginate($perPage);

        return LocationResource::collection($locations);
    }

    #[OA\Get(
        path: "/global-location/{id}",
        summary: "Get global location by ID",
        tags: ["Global Locations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global location details",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalLocation")
            ),
            new OA\Response(response: 404, description: "Global Location not found")
        ]
    )]
    public function show($id)
    {
        $location = GlobalLocation::findOrFail($id);
        return new LocationResource($location);
    }

    #[OA\Post(
        path: "/global-locations",
        summary: "Create new global location",
        tags: ["Global Locations"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "type"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Global archive"),
                    new OA\Property(property: "type", type: "string", enum: ["repository", "archive", "collection"], example: "archive"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-location-12"]),
                ],
                example: [
                    "name" => "Global archive",
                    "type" => "archive",
                    "client_meta" => ["external_id" => "global-location-12"],
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Global location created",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalLocation")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 409, description: "Entity already exists"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        if ($response = $this->rejectUnknownFields($request, ['name', 'type', 'client_meta'])) {
            return $response;
        }

        $request->merge([
            'name' => trim($request->input('name')),
            'type' => trim($request->input('type')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(GlobalLocation::types())],
            'client_meta' => ['nullable', 'array'],
        ]);
        unset($validated['client_meta']);

        $exists = GlobalLocation::whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
                           ->where('type', $validated['type'])
                           ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Such entity already exists.',
            ], 409);
        }

        $location = GlobalLocation::create($validated);

        return (new LocationResource($location))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-location/{id}",
        summary: "Update global location",
        description: "Partial update semantics. Omitted fields remain unchanged, null clears nullable scalar fields when supported, and client-specific extra data belongs in client_meta.",
        tags: ["Global Locations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Updated global archive"),
                    new OA\Property(property: "type", type: "string", enum: ["repository", "archive", "collection"], example: "archive"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-location-12"]),
                ],
                example: [
                    "name" => "Updated global archive",
                    "client_meta" => ["external_id" => "global-location-12"],
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Global location updated",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalLocation")
            ),
            new OA\Response(response: 404, description: "Global Location not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, $id)
    {
        if ($response = $this->rejectUnknownFields($request, ['name', 'type', 'client_meta'])) {
            return $response;
        }

        $request->merge([
            'name' => trim($request->input('name')),
            'type' => trim($request->input('type')),
        ]);

        $location = GlobalLocation::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', Rule::in(GlobalLocation::types())],
            'client_meta' => ['nullable', 'array'],
        ]);
        unset($validated['client_meta']);

        $name = $validated['name'] ?? $location->name;
        $type = $validated['type'] ?? $location->type;

        $exists = GlobalLocation::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                           ->where('type', $type)
                           ->where('id', '!=', $location->id)
                           ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Such entity already exists.',
            ], 422);
        }

        $location->update($validated);
        return new LocationResource($location);
    }

    #[OA\Delete(
        path: "/global-location/{id}",
        summary: "Delete global location",
        tags: ["Global Locations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global location deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Global Location not found")
        ]
    )]
    public function destroy($id)
    {
        $location = GlobalLocation::findOrFail($id);
        $location->delete();

        return response()->json(['message' => 'Entity deleted successfully.']);
    }
}
