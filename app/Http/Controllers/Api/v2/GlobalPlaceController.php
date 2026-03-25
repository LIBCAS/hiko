<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalPlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\GlobalPlace;
use App\Services\GlobalPlaceService;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Global Places",
    description: "Management of global places"
)]
class GlobalPlaceController extends Controller
{
    public static int $maxPerPage = 100;
    public static int $defaultPerPage = 20;

    protected GlobalPlaceService $placeService;

    public function __construct(GlobalPlaceService $placeService)
    {
        $this->placeService = $placeService;
    }

    #[OA\Get(
        path: "/global-places",
        summary: "List global places",
        tags: ["Global Places"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of global places",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/GlobalPlace")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', self::$defaultPerPage), 1), self::$maxPerPage);
        $places = GlobalPlace::paginate($perPage);

        return PlaceResource::collection($places);
    }

    #[OA\Get(
        path: "/global-place/{id}",
        summary: "Get global place by ID",
        tags: ["Global Places"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global place details",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalPlace")
            ),
            new OA\Response(response: 404, description: "Global Place not found")
        ]
    )]
    public function show($id)
    {
        $place = GlobalPlace::findOrFail($id);
        return new PlaceResource($place);
    }

    #[OA\Post(
        path: "/global-places",
        summary: "Create new global place",
        tags: ["Global Places"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "country"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Global place"),
                    new OA\Property(property: "country", type: "string", example: "Czech Republic"),
                    new OA\Property(property: "division", type: "string", nullable: true, example: "Bohemia"),
                    new OA\Property(property: "note", type: "string", nullable: true, example: "Created from external app"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-place-238"]),
                ],
                example: [
                    "name" => "Global place",
                    "country" => "Czech Republic",
                    "division" => "Bohemia",
                    "note" => "Created from external app",
                    "client_meta" => ["external_id" => "global-place-238"],
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Global place created",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalPlace")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 409, description: "Entity already exists"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(GlobalPlaceRequest $request)
    {
        $validated = $request->validated();
        unset($validated['client_meta']);

        if ($request->failsDuplicateCheck()) {
            return response()->json(['message' => 'Such entity already exists.'], 409);
        }

        $place = $this->placeService->create($validated);

        return (new PlaceResource($place))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-place/{id}",
        summary: "Update global place",
        description: "Partial update semantics. Omitted fields remain unchanged, null clears nullable scalar fields, and client-specific extra data belongs in client_meta.",
        tags: ["Global Places"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Updated global place"),
                    new OA\Property(property: "country", type: "string", example: "Czech Republic"),
                    new OA\Property(property: "division", type: "string", nullable: true, example: null),
                    new OA\Property(property: "note", type: "string", nullable: true, example: "Updated note"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-place-238"]),
                ],
                example: [
                    "note" => null,
                    "client_meta" => ["external_id" => "global-place-238"],
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Global place updated",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalPlace")
            ),
            new OA\Response(response: 404, description: "Global Place not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(GlobalPlaceRequest $request, $id)
    {
        $place = GlobalPlace::findOrFail($id);
        $validated = $request->validated();
        unset($validated['client_meta']);

        if ($request->failsDuplicateCheck($place->id, $place->only([
            'name',
            'country',
            'division',
            'latitude',
            'longitude',
            'geoname_id',
        ]))) {
            return response()->json(['message' => 'Such entity already exists.'], 409);
        }

        $updated = $this->placeService->update($place, $validated);
        return new PlaceResource($updated);
    }

    #[OA\Delete(
        path: "/global-place/{id}",
        summary: "Delete global place",
        tags: ["Global Places"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global place deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Global Place not found")
        ]
    )]
    public function destroy($id)
    {
        $place = GlobalPlace::findOrFail($id);
        $place->delete();

        return response()->json(['message' => 'Entity deleted successfully.']);
    }
}
