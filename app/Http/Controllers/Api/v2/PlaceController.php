<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Places",
    description: "Management of places"
)]
class PlaceController extends Controller
{
    public static int $maxPerPage = 100;
    public static int $defaultPerPage = 20;

    protected PlaceService $placeService;

    public function __construct(PlaceService $placeService)
    {
        $this->placeService = $placeService;
    }

    #[OA\Get(
        path: "/places",
        summary: "List places",
        tags: ["Places"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of places",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Place")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', self::$defaultPerPage), 1), self::$maxPerPage);
        $places = Place::paginate($perPage);

        return PlaceResource::collection($places);
    }

    #[OA\Get(
        path: "/place/{id}",
        summary: "Get place by ID",
        tags: ["Places"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Place details",
                content: new OA\JsonContent(ref: "#/components/schemas/Place")
            ),
            new OA\Response(response: 404, description: "Place not found")
        ]
    )]
    public function show($id)
    {
        $place = Place::findOrFail($id);
        return new PlaceResource($place);
    }

    #[OA\Post(
        path: "/places",
        summary: "Create new place",
        tags: ["Places"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "country"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Local place"),
                    new OA\Property(property: "country", type: "string", example: "Czech Republic"),
                    new OA\Property(property: "division", type: "string", nullable: true, example: "Moravia"),
                    new OA\Property(property: "note", type: "string", nullable: true, example: "Created from external app"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "place-181"]),
                ],
                example: [
                    "name" => "Local place",
                    "country" => "Czech Republic",
                    "division" => "Moravia",
                    "note" => "Created from external app",
                    "client_meta" => ["external_id" => "place-181"],
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Place created",
                content: new OA\JsonContent(ref: "#/components/schemas/Place")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 409, description: "Entity already exists"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(PlaceRequest $request)
    {
        $validated = $request->validated();
        unset($validated['client_meta']);

        if ($request->failsDuplicateCheck()) {
            return response()->json(['message' => __('hiko.entity_already_exists')], 409);
        }

        $place = $this->placeService->create($validated);

        return (new PlaceResource($place))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/place/{id}",
        summary: "Update place",
        description: "Partial update semantics. Omitted fields remain unchanged, null clears nullable scalar fields, and client-specific extra data belongs in client_meta.",
        tags: ["Places"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Updated local place"),
                    new OA\Property(property: "country", type: "string", example: "Czech Republic"),
                    new OA\Property(property: "division", type: "string", nullable: true, example: null),
                    new OA\Property(property: "note", type: "string", nullable: true, example: "Updated note"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "place-181"]),
                ],
                example: [
                    "note" => null,
                    "client_meta" => ["external_id" => "place-181"],
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Place updated",
                content: new OA\JsonContent(ref: "#/components/schemas/Place")
            ),
            new OA\Response(response: 404, description: "Place not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(PlaceRequest $request, $id)
    {
        $place = Place::findOrFail($id);
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
            return response()->json(['message' => __('hiko.entity_already_exists')], 422);
        }

        $updated = $this->placeService->update($place, $validated);
        return new PlaceResource($updated);
    }

    #[OA\Delete(
        path: "/place/{id}",
        summary: "Delete place",
        tags: ["Places"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Place deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Place not found")
        ]
    )]
    public function destroy($id)
    {
        $place = Place::findOrFail($id);
        $place->delete();

        return response()->json(['message' => __('hiko.removed')]);
    }
}
