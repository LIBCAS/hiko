<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\IdentityRequest;
use App\Http\Resources\IdentityResource;
use App\Models\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Identities",
    description: "Management of historical identities (persons, institutions)"
)]
class IdentityController extends Controller
{
    public static int $maxPerPage = 100;
    public static int $defaultPerPage = 20;

    #[OA\Get(
        path: "/identities",
        summary: "List identities",
        tags: ["Identities"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of identities",
                content: new OA\JsonContent(
                    example: [
                        "data" => [
                            [
                                "id" => 1335,
                                "name" => "Tester, Local",
                                "surname" => "Tester",
                                "forename" => "Local",
                                "type" => "person",
                                "global_identity" => [
                                    "id" => 1,
                                    "scope" => "global",
                                    "reference" => "global-1",
                                    "name" => "Tester, Global",
                                    "type" => "person",
                                    "birth_year" => null,
                                    "death_year" => null,
                                ],
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
            )
        ]
    )]
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', self::$defaultPerPage);
        $page = (int) $request->query('page', 1);

        $perPage = max(1, min(self::$maxPerPage, $perPage));
        $page = max(1, $page);

        $identities = Identity::with([
                'localProfessions',
                'globalProfessions',
                'globalIdentity',
                'religions',
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        return IdentityResource::collection($identities);
    }

    #[OA\Get(
        path: "/identity/{id}",
        summary: "Get identity by ID",
        tags: ["Identities"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Identity details",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Identity",
                    example: [
                        "id" => 1335,
                        "name" => "Tester, Local",
                        "surname" => "Tester",
                        "forename" => "Local",
                        "type" => "person",
                        "nationality" => "czech",
                        "global_identity" => [
                            "id" => 1,
                            "scope" => "global",
                            "reference" => "global-1",
                            "name" => "Tester, Global",
                            "type" => "person",
                            "birth_year" => null,
                            "death_year" => null,
                        ],
                        "created_at" => "2026-02-18T10:00:00.000000Z",
                        "updated_at" => "2026-02-18T10:00:00.000000Z",
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Identity not found")
        ]
    )]
    public function show($id)
    {
        $identity = Identity::with([
            'localProfessions',
            'globalProfessions',
            'globalIdentity',
            'religions',
        ])->findOrFail($id);
        return new IdentityResource($identity->load(['globalIdentity', 'religions']));
    }

    #[OA\Post(
        path: "/identities",
        summary: "Create new identity",
        tags: ["Identities"],
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
                            new OA\Property(property: "forename", type: "string", example: "Local"),
                            new OA\Property(property: "general_name_modifier", type: "string", nullable: true, example: null),
                            new OA\Property(property: "alternative_names", type: "array", items: new OA\Items(type: "string"), example: ["Alias"]),
                            new OA\Property(property: "related_names", type: "array", items: new OA\Items(type: "object"), example: [["surname" => "Tester", "forename" => "Variant"]]),
                            new OA\Property(property: "nationality", type: "string", nullable: true, example: "czech"),
                            new OA\Property(property: "gender", type: "string", nullable: true, example: "M"),
                            new OA\Property(property: "birth_year", type: "string", nullable: true, example: "1900"),
                            new OA\Property(property: "death_year", type: "string", nullable: true, example: "1980"),
                            new OA\Property(property: "related_identity_resources", type: "array", items: new OA\Items(type: "object"), example: [["title" => "Resource", "url" => "https://example.org"]]),
                            new OA\Property(property: "viaf_id", type: "string", nullable: true, example: "123456"),
                            new OA\Property(property: "note", type: "string", nullable: true, example: "Local person"),
                            new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "client-identity-2457"]),
                            new OA\Property(
                                property: "professions",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 22),
                                        new OA\Property(property: "scope", type: "string", enum: ["local", "global"], example: "local"),
                                        new OA\Property(property: "reference", type: "string", readOnly: true, example: "local-22", description: "Read-only response field."),
                                    ]
                                )
                            ),
                            new OA\Property(
                                property: "global_identity",
                                type: "object",
                                nullable: true,
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "scope", type: "string", enum: ["global"], example: "global"),
                                    new OA\Property(property: "reference", type: "string", readOnly: true, example: "global-1", description: "Read-only response field."),
                                ]
                            ),
                        ]
                    ),
                    new OA\Schema(
                        title: "Institution payload",
                        required: ["type", "name"],
                        properties: [
                            new OA\Property(property: "type", type: "string", enum: ["institution"], example: "institution"),
                            new OA\Property(property: "name", type: "string", example: "The British Library"),
                            new OA\Property(property: "related_identity_resources", type: "array", items: new OA\Items(type: "object"), example: [["title" => "Catalog page", "url" => "https://example.org/catalog"]]),
                            new OA\Property(property: "note", type: "string", nullable: true, example: "Institution note"),
                            new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "client-identity-1335"]),
                            new OA\Property(
                                property: "global_identity",
                                type: "object",
                                nullable: true,
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "scope", type: "string", enum: ["global"], example: "global"),
                                    new OA\Property(property: "reference", type: "string", readOnly: true, example: "global-1", description: "Read-only response field."),
                                ]
                            ),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Identity created",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Identity",
                    example: [
                        "id" => 2457,
                        "name" => "Tester, Local",
                        "surname" => "Tester",
                        "forename" => "Local",
                        "type" => "person",
                        "global_identity" => [
                            "id" => 1,
                            "scope" => "global",
                            "reference" => "global-1",
                            "name" => "Tester, Global",
                            "type" => "person",
                        ],
                        "created_at" => "2026-02-18T10:00:00.000000Z",
                        "updated_at" => "2026-02-18T10:00:00.000000Z",
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(IdentityRequest $request)
    {
        $validated = $request->validated();
        unset($validated['client_meta']);

        // Ensure defaults are arrays if null, so Model casts handle them correctly
        $validated['related_names'] = $validated['related_names'] ?? [];
        $validated['related_identity_resources'] = $validated['related_identity_resources'] ?? [];

        unset($validated['category'], $validated['profession']);

        if ($validated['type'] !== 'person') {
            unset($validated['surname'], $validated['forename'], $validated['general_name_modifier']);
        }

        Log::info('API V2: Creating Identity', ['data' => $validated]);

        $identity = Identity::create($validated);

        $this->syncRelations($identity, $request->validated());

        return (new IdentityResource($identity->load(['globalIdentity', 'religions'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/identity/{id}",
        summary: "Update identity",
        description: "Partial update semantics. Omitted fields remain unchanged, null clears nullable scalar fields, [] clears relation/list fields, and client-specific extra data belongs in client_meta.",
        tags: ["Identities"],
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
                        properties: [
                            new OA\Property(property: "type", type: "string", enum: ["person"], example: "person"),
                            new OA\Property(property: "surname", type: "string", example: "Tester"),
                            new OA\Property(property: "forename", type: "string", example: "Updated"),
                            new OA\Property(property: "general_name_modifier", type: "string", nullable: true, example: null),
                            new OA\Property(property: "nationality", type: "string", nullable: true, example: "czech"),
                            new OA\Property(property: "gender", type: "string", nullable: true, example: "M"),
                            new OA\Property(property: "birth_year", type: "string", nullable: true, example: "1900"),
                            new OA\Property(property: "death_year", type: "string", nullable: true, example: "1981"),
                            new OA\Property(property: "note", type: "string", nullable: true, example: "Updated local person"),
                            new OA\Property(
                                property: "professions",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 22),
                                        new OA\Property(property: "scope", type: "string", enum: ["local", "global"], example: "local"),
                                        new OA\Property(property: "reference", type: "string", readOnly: true, example: "local-22", description: "Read-only response field."),
                                    ]
                                )
                            ),
                            new OA\Property(
                                property: "global_identity",
                                type: "object",
                                nullable: true,
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "scope", type: "string", enum: ["global"], example: "global"),
                                    new OA\Property(property: "reference", type: "string", readOnly: true, example: "global-1", description: "Read-only response field."),
                                ]
                            ),
                            new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "client-identity-1335"]),
                        ]
                    ),
                    new OA\Schema(
                        title: "Institution payload",
                        properties: [
                            new OA\Property(property: "type", type: "string", enum: ["institution"], example: "institution"),
                            new OA\Property(property: "name", type: "string", example: "The British Library (Updated)"),
                            new OA\Property(property: "note", type: "string", nullable: true, example: "Updated institution"),
                            new OA\Property(
                                property: "global_identity",
                                type: "object",
                                nullable: true,
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "scope", type: "string", enum: ["global"], example: "global"),
                                    new OA\Property(property: "reference", type: "string", readOnly: true, example: "global-1", description: "Read-only response field."),
                                ]
                            ),
                            new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "client-identity-2457"]),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Identity updated",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Identity",
                    example: [
                        "id" => 2457,
                        "name" => "Tester, Updated",
                        "surname" => "Tester",
                        "forename" => "Updated",
                        "type" => "person",
                        "global_identity" => [
                            "id" => 1,
                            "scope" => "global",
                            "reference" => "global-1",
                            "name" => "Tester, Global",
                            "type" => "person",
                        ],
                        "updated_at" => "2026-02-18T11:00:00.000000Z",
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Identity not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(IdentityRequest $request, $id)
    {
        $identity = Identity::findOrFail($id);
        $validated = $request->validated();
        unset($validated['client_meta']);

        unset($validated['category'], $validated['profession']);

        $effectiveType = $validated['type'] ?? $identity->type;

        if ($effectiveType !== 'person') {
            unset($validated['surname'], $validated['forename'], $validated['general_name_modifier']);
        }

        Log::info('API V2: Updating Identity', ['id' => $identity->id, 'data' => $validated]);

        $identity->update($validated);

        $this->syncRelations($identity, $request->validated());

        return new IdentityResource($identity->load(['globalIdentity', 'religions']));
    }

    #[OA\Delete(
        path: "/identity/{id}",
        summary: "Delete identity",
        tags: ["Identities"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Identity deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Identity not found")
        ]
    )]
    public function destroy($id)
    {
        $identity = Identity::findOrFail($id);
        $identity->delete();

        return response()->json(['message' => 'Entity deleted successfully.']);
    }

    /**
     * Syncs the relations for the given identity based on the validated data.
     *
     * @param Identity $identity
     * @param array $validated
     */
    protected function syncRelations(Identity $identity, array $validated): void
    {
        $localIds = collect();
        $globalIds = collect();
        $religionIds = collect($validated['religions'] ?? [])->map(fn($id) => (int) $id);
        $hasProfessionInput = array_key_exists('professions', $validated)
            || array_key_exists('local_professions', $validated)
            || array_key_exists('global_professions', $validated)
            || array_key_exists('profession', $validated);
        $hasReligionInput = array_key_exists('religions', $validated);
        $hasCategoryInput = array_key_exists('category', $validated);

        if (array_key_exists('professions', $validated) && !empty($validated['professions'])) {
            foreach ($validated['professions'] as $profession) {
                $professionId = $profession['id'] ?? null;
                if (!is_string($professionId)) {
                    continue;
                }

                $isGlobal = str_starts_with($professionId, 'global-');
                $cleanId = (int) str_replace(['global-', 'local-'], '', $professionId);

                if ($isGlobal) {
                    $globalIds->push($cleanId);
                } else {
                    $localIds->push($cleanId);
                }
            }
        } elseif ((array_key_exists('local_professions', $validated) && !empty($validated['local_professions'])) || (array_key_exists('global_professions', $validated) && !empty($validated['global_professions']))) {
            $localIds = collect($validated['local_professions'] ?? [])->map(fn($id) => (int) $id);
            $globalIds = collect($validated['global_professions'] ?? [])->map(fn($id) => (int) $id);
        } elseif (array_key_exists('profession', $validated) && !empty($validated['profession'])) {
            foreach ($validated['profession'] as $professionId) {
                $isGlobal = str_starts_with($professionId, 'global-');
                $cleanId = (int) str_replace(['global-', 'local-'], '', $professionId);

                if ($isGlobal) {
                    $globalIds->push($cleanId);
                } else {
                    $localIds->push($cleanId);
                }
            }
        }

        $tenantPivotTable = tenancy()->tenant->table_prefix . '__identity_profession';

        DB::transaction(function () use ($identity, $localIds, $globalIds, $tenantPivotTable, $religionIds, $validated, $hasProfessionInput, $hasReligionInput, $hasCategoryInput) {
            if ($hasProfessionInput) {
                DB::table($tenantPivotTable)->where('identity_id', $identity->id)->delete();

                if ($localIds->isNotEmpty()) {
                    $localData = $localIds->mapWithKeys(fn($id) => [$id => [
                        'profession_id' => $id,
                        'global_profession_id' => null,
                        'position' => null,
                    ]])->toArray();
                    $identity->professions()->attach($localData);
                }

                if ($globalIds->isNotEmpty()) {
                    $globalData = $globalIds->map(fn($id) => [
                        'identity_id' => $identity->id,
                        'profession_id' => null,
                        'global_profession_id' => $id,
                        'position' => null,
                    ])->toArray();
                    DB::table($tenantPivotTable)->insert($globalData);
                }
            }

            if ($hasCategoryInput) {
                $identity->profession_categories()->sync($validated['category']);
            }

            if (($validated['type'] ?? $identity->type) !== 'person') {
                $identity->syncReligions([]);
            } elseif ($hasReligionInput) {
                $identity->syncReligions($religionIds->isNotEmpty() ? $religionIds->all() : []);
            }
        });
    }
}
