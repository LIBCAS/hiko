<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalIdentityRequest;
use App\Http\Resources\IdentityResource;
use App\Models\GlobalIdentity;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Global Identities",
    description: "Management of global identities"
)]
class GlobalIdentityController extends Controller
{
    public static int $maxPerPage = 100;
    public static int $defaultPerPage = 20;

    #[OA\Get(
        path: "/global-identities",
        summary: "List global identities",
        tags: ["Global Identities"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(
                name: "include",
                in: "query",
                description: "Optional includes. Allowed: linked_local_identities",
                schema: new OA\Schema(type: "string", example: "linked_local_identities")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of global identities",
                content: new OA\JsonContent(
                    example: [
                        "data" => [
                            [
                                "id" => 1,
                                "name" => "Tester, Global",
                                "surname" => "Tester",
                                "forename" => "Global",
                                "type" => "person",
                                "linked_local_identities_count" => 3,
                            ],
                        ],
                        "meta" => [
                            "current_page" => 1,
                            "per_page" => 20,
                            "total" => 1,
                        ],
                    ],
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/GlobalIdentity")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $includes = $this->parseIncludes($request);
        $perPage = min(max((int)$request->query('per_page', self::$defaultPerPage), 1), self::$maxPerPage);
        $query = GlobalIdentity::with(['professions', 'religions'])
            ->withCount(['localIdentities as linked_local_identities_count']);

        if (in_array('linked_local_identities', $includes, true)) {
            $query->with(['localIdentities' => fn($q) => $q->orderBy('id')]);
        }

        $identities = $query->paginate($perPage);

        return IdentityResource::collection($identities);
    }

    #[OA\Get(
        path: "/global-identity/{id}",
        summary: "Get global identity by ID",
        tags: ["Global Identities"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(
                name: "include",
                in: "query",
                description: "Optional includes. Allowed: linked_local_identities",
                schema: new OA\Schema(type: "string", example: "linked_local_identities")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global identity details",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/GlobalIdentity",
                    example: [
                        "id" => 1,
                        "name" => "Tester, Global",
                        "surname" => "Tester",
                        "forename" => "Global",
                        "type" => "person",
                        "nationality" => "czech",
                        "linked_local_identities_count" => 3,
                        "linked_local_identities" => [
                            [
                                "id" => 1335,
                                "name" => "Tester, Local",
                                "type" => "person",
                                "global_identity_id" => 1,
                            ],
                        ],
                        "created_at" => "2026-02-18T10:00:00.000000Z",
                        "updated_at" => "2026-02-18T10:00:00.000000Z",
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Global identity not found")
        ]
    )]
    public function show(Request $request, $id)
    {
        $includes = $this->parseIncludes($request);

        $query = GlobalIdentity::with(['professions', 'religions'])
            ->withCount(['localIdentities as linked_local_identities_count']);

        if (in_array('linked_local_identities', $includes, true)) {
            $query->with(['localIdentities' => fn($q) => $q->orderBy('id')]);
        }

        $identity = $query->findOrFail($id);
        return new IdentityResource($identity);
    }

    #[OA\Get(
        path: "/global-identity/{id}/linked-identities",
        summary: "List local identities linked to a global identity",
        tags: ["Global Identities"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Global identity ID", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "search", in: "query", description: "Search local identity name", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "type", in: "query", description: "Filter by identity type", schema: new OA\Schema(type: "string", enum: ["person", "institution"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Paginated linked local identities",
                content: new OA\JsonContent(
                    example: [
                        "data" => [
                            [
                                "id" => 1335,
                                "name" => "Tester, Local",
                                "surname" => "Tester",
                                "forename" => "Local",
                                "type" => "person",
                                "global_identity_id" => 1,
                            ],
                        ],
                        "meta" => [
                            "current_page" => 1,
                            "per_page" => 20,
                            "total" => 1,
                        ],
                    ],
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Identity")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Global identity not found")
        ]
    )]
    public function linkedIdentities(Request $request, $id)
    {
        $perPage = min(max((int)$request->query('per_page', self::$defaultPerPage), 1), self::$maxPerPage);
        $globalIdentity = GlobalIdentity::findOrFail($id);

        $query = $globalIdentity->localIdentities()->orderBy('id');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        if (in_array($request->query('type'), ['person', 'institution'], true)) {
            $query->where('type', $request->query('type'));
        }

        return IdentityResource::collection($query->paginate($perPage));
    }

    #[OA\Post(
        path: "/global-identities",
        summary: "Create new global identity",
        tags: ["Global Identities"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                oneOf: [
                    new OA\Schema(
                        title: "Person payload",
                        required: ["type", "surname"],
                        properties: [
                            new OA\Property(property: "type", type: "string", enum: ["person"], example: "person"),
                            new OA\Property(property: "surname", type: "string", example: "Tester"),
                            new OA\Property(property: "forename", type: "string", example: "Testovaci"),
                            new OA\Property(property: "general_name_modifier", type: "string", nullable: true, example: null),
                            new OA\Property(property: "alternative_names", type: "array", items: new OA\Items(type: "string"), example: ["Test Name"]),
                            new OA\Property(property: "related_names", type: "array", items: new OA\Items(type: "object"), example: [["surname" => "Tester", "forename" => "Alias"]]),
                            new OA\Property(property: "nationality", type: "string", nullable: true, example: "czech"),
                            new OA\Property(property: "gender", type: "string", nullable: true, example: "F"),
                            new OA\Property(property: "birth_year", type: "string", nullable: true, example: "1900"),
                            new OA\Property(property: "death_year", type: "string", nullable: true, example: "1980"),
                            new OA\Property(property: "related_identity_resources", type: "array", items: new OA\Items(type: "object"), example: [["title" => "Resource 1", "url" => "https://example.org/resource-1"]]),
                            new OA\Property(property: "viaf_id", type: "string", nullable: true, example: "123456"),
                            new OA\Property(property: "note", type: "string", nullable: true, example: "Global person identity"),
                            new OA\Property(property: "professions", type: "array", items: new OA\Items(type: "integer"), example: [394, 481]),
                            new OA\Property(property: "religions", type: "array", items: new OA\Items(type: "integer"), example: [18, 31]),
                        ]
                    ),
                    new OA\Schema(
                        title: "Institution payload",
                        required: ["type", "name"],
                        properties: [
                            new OA\Property(property: "type", type: "string", enum: ["institution"], example: "institution"),
                            new OA\Property(property: "name", type: "string", example: "The British Library"),
                            new OA\Property(property: "related_identity_resources", type: "array", items: new OA\Items(type: "object"), example: [["title" => "Catalog page", "url" => "https://example.org/catalog"]]),
                            new OA\Property(property: "note", type: "string", nullable: true, example: "London institution"),
                            new OA\Property(property: "professions", type: "array", items: new OA\Items(type: "integer"), example: []),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Global identity created",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/GlobalIdentity",
                    example: [
                        "id" => 1,
                        "name" => "Tester, Testovaci",
                        "surname" => "Tester",
                        "forename" => "Testovaci",
                        "type" => "person",
                        "created_at" => "2026-02-18T10:00:00.000000Z",
                        "updated_at" => "2026-02-18T10:00:00.000000Z",
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(GlobalIdentityRequest $request)
    {
        $validated = $request->validated();
        $identityData = collect($validated)->except(['professions', 'religions'])->toArray();

        $identity = GlobalIdentity::create($identityData);
        $identity->professions()->sync($validated['professions'] ?? []);

        if (($validated['type'] ?? null) === 'person') {
            $identity->syncReligions($request->input('religions', null));
        }

        return (new IdentityResource($identity->load(['professions', 'religions'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-identity/{id}",
        summary: "Update global identity",
        tags: ["Global Identities"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                oneOf: [
                    new OA\Schema(
                        title: "Person payload",
                        required: ["type", "surname"],
                        properties: [
                            new OA\Property(property: "type", type: "string", enum: ["person"], example: "person"),
                            new OA\Property(property: "surname", type: "string", example: "Tester"),
                            new OA\Property(property: "forename", type: "string", example: "Updated"),
                            new OA\Property(property: "general_name_modifier", type: "string", nullable: true, example: null),
                            new OA\Property(property: "alternative_names", type: "array", items: new OA\Items(type: "string"), example: ["Updated alias"]),
                            new OA\Property(property: "related_names", type: "array", items: new OA\Items(type: "object"), example: [["surname" => "Tester", "forename" => "Variant"]]),
                            new OA\Property(property: "nationality", type: "string", nullable: true, example: "czech"),
                            new OA\Property(property: "gender", type: "string", nullable: true, example: "F"),
                            new OA\Property(property: "birth_year", type: "string", nullable: true, example: "1900"),
                            new OA\Property(property: "death_year", type: "string", nullable: true, example: "1981"),
                            new OA\Property(property: "related_identity_resources", type: "array", items: new OA\Items(type: "object"), example: [["title" => "Updated resource", "url" => "https://example.org/resource-2"]]),
                            new OA\Property(property: "viaf_id", type: "string", nullable: true, example: "654321"),
                            new OA\Property(property: "note", type: "string", nullable: true, example: "Updated global person identity"),
                            new OA\Property(property: "professions", type: "array", items: new OA\Items(type: "integer"), example: [394]),
                            new OA\Property(property: "religions", type: "array", items: new OA\Items(type: "integer"), example: [18]),
                        ]
                    ),
                    new OA\Schema(
                        title: "Institution payload",
                        required: ["type", "name"],
                        properties: [
                            new OA\Property(property: "type", type: "string", enum: ["institution"], example: "institution"),
                            new OA\Property(property: "name", type: "string", example: "The British Library (Updated)"),
                            new OA\Property(property: "related_identity_resources", type: "array", items: new OA\Items(type: "object"), example: [["title" => "Updated catalog", "url" => "https://example.org/catalog-updated"]]),
                            new OA\Property(property: "note", type: "string", nullable: true, example: "Updated institution note"),
                            new OA\Property(property: "professions", type: "array", items: new OA\Items(type: "integer"), example: []),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Global identity updated",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/GlobalIdentity",
                    example: [
                        "id" => 1,
                        "name" => "Tester, Updated",
                        "surname" => "Tester",
                        "forename" => "Updated",
                        "type" => "person",
                        "updated_at" => "2026-02-18T11:00:00.000000Z",
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Global identity not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(GlobalIdentityRequest $request, $id)
    {
        $identity = GlobalIdentity::findOrFail($id);
        $validated = $request->validated();
        $identityData = collect($validated)->except(['professions', 'religions'])->toArray();

        $identity->update($identityData);
        $identity->professions()->sync($validated['professions'] ?? []);

        if (($validated['type'] ?? null) === 'person') {
            $identity->syncReligions($request->input('religions', null));
        } else {
            $identity->syncReligions([]);
        }

        return new IdentityResource($identity->load(['professions', 'religions']));
    }

    #[OA\Delete(
        path: "/global-identity/{id}",
        summary: "Delete global identity",
        tags: ["Global Identities"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global identity deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Global identity not found")
        ]
    )]
    public function destroy($id)
    {
        $identity = GlobalIdentity::findOrFail($id);
        $identity->delete();

        return response()->json(['message' => 'Entity deleted successfully.']);
    }

    protected function parseIncludes(Request $request): array
    {
        return collect(explode(',', (string) $request->query('include', '')))
            ->map(fn($include) => trim($include))
            ->filter()
            ->values()
            ->all();
    }
}
