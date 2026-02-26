<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeywordCategoryRequest;
use App\Http\Resources\KeywordCategoryResource;
use App\Models\KeywordCategory;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Keyword Categories",
    description: "Management of keyword categories"
)]
class KeywordCategoryController extends Controller
{
    #[OA\Get(
        path: "/keyword-categories",
        summary: "List keyword categories",
        tags: ["Keyword Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of keyword categories",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/KeywordCategory")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $categories = KeywordCategory::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );

        return KeywordCategoryResource::collection($categories);
    }

    #[OA\Get(
        path: "/keyword-category/{id}",
        summary: "Get keyword category by ID",
        tags: ["Keyword Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Keyword category details",
                content: new OA\JsonContent(ref: "#/components/schemas/KeywordCategory")
            ),
            new OA\Response(response: 404, description: "Keyword Category not found")
        ]
    )]
    public function show($id)
    {
        $category = KeywordCategory::findOrFail($id);
        return new KeywordCategoryResource($category);
    }

    #[OA\Post(
        path: "/keyword-categories",
        summary: "Create new keyword category",
        tags: ["Keyword Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/KeywordCategory")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Keyword category created",
                content: new OA\JsonContent(ref: "#/components/schemas/KeywordCategory")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 409, description: "Entity already exists"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(KeywordCategoryRequest $request)
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return response()->json(['message' => 'Such entity already exists.'], 409);
        }

        $category = KeywordCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ]
        ]);

        return (new KeywordCategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/keyword-category/{id}",
        summary: "Update keyword category",
        tags: ["Keyword Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/KeywordCategory")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Keyword category updated",
                content: new OA\JsonContent(ref: "#/components/schemas/KeywordCategory")
            ),
            new OA\Response(response: 404, description: "Keyword Category not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(KeywordCategoryRequest $request, $id)
    {
        $category = KeywordCategory::findOrFail($id);
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($category->id)) {
            return response()->json(['message' => 'Such entity already exists.'], 422);
        }

        $category->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ]
        ]);

        return new KeywordCategoryResource($category);
    }

    #[OA\Delete(
        path: "/keyword-category/{id}",
        summary: "Delete keyword category",
        tags: ["Keyword Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Keyword category deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Keyword Category not found")
        ]
    )]
    public function destroy($id)
    {
        $category = KeywordCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
