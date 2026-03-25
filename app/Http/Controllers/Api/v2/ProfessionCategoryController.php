<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfessionCategoryRequest;
use App\Http\Resources\ProfessionCategoryResource;
use App\Models\ProfessionCategory;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Profession Categories",
    description: "Management of profession categories"
)]
class ProfessionCategoryController extends Controller
{
    #[OA\Get(
        path: "/profession-categories",
        summary: "List profession categories",
        tags: ["Profession Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of profession categories",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/ProfessionCategory")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $categories = ProfessionCategory::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );

        return ProfessionCategoryResource::collection($categories);
    }

    #[OA\Get(
        path: "/profession-category/{id}",
        summary: "Get profession category by ID",
        tags: ["Profession Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Profession category details",
                content: new OA\JsonContent(ref: "#/components/schemas/ProfessionCategory")
            ),
            new OA\Response(response: 404, description: "Profession Category not found")
        ]
    )]
    public function show($id)
    {
        $category = ProfessionCategory::findOrFail($id);
        return new ProfessionCategoryResource($category);
    }

    #[OA\Post(
        path: "/profession-categories",
        summary: "Create new profession category",
        tags: ["Profession Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "cs", type: "string", nullable: true, example: "Mistni profesni kategorie"),
                    new OA\Property(property: "en", type: "string", nullable: true, example: "Local profession category"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "profession-category-82"]),
                ],
                example: [
                    "cs" => "Mistni profesni kategorie",
                    "en" => "Local profession category",
                    "client_meta" => ["external_id" => "profession-category-82"],
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Profession category created",
                content: new OA\JsonContent(ref: "#/components/schemas/ProfessionCategory")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 409, description: "Entity already exists"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(ProfessionCategoryRequest $request)
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return response()->json(['message' => __('hiko.entity_already_exists')], 409);
        }

        $category = ProfessionCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ]
        ]);

        return (new ProfessionCategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/profession-category/{id}",
        summary: "Update profession category",
        description: "Partial update semantics. Omitted fields remain unchanged, null clears nullable translated fields, and client-specific extra data belongs in client_meta.",
        tags: ["Profession Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "cs", type: "string", nullable: true, example: "Updated local profession category"),
                    new OA\Property(property: "en", type: "string", nullable: true, example: "Updated local profession category"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "profession-category-82"]),
                ],
                example: [
                    "en" => "Updated local profession category",
                    "client_meta" => ["external_id" => "profession-category-82"],
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Profession category updated",
                content: new OA\JsonContent(ref: "#/components/schemas/ProfessionCategory")
            ),
            new OA\Response(response: 404, description: "Profession Category not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(ProfessionCategoryRequest $request, $id)
    {
        $category = ProfessionCategory::findOrFail($id);
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($category->id)) {
            return response()->json(['message' => __('hiko.entity_already_exists')], 422);
        }

        $category->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ]
        ]);

        return new ProfessionCategoryResource($category);
    }

    #[OA\Delete(
        path: "/profession-category/{id}",
        summary: "Delete profession category",
        tags: ["Profession Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Profession category deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Profession Category not found")
        ]
    )]
    public function destroy($id)
    {
        $category = ProfessionCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => __('hiko.removed')]);
    }
}
