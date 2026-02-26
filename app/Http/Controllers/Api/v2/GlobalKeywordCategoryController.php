<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Resources\KeywordCategoryResource;
use App\Models\GlobalKeywordCategory;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Global Keyword Categories",
    description: "Management of global keyword categories"
)]
class GlobalKeywordCategoryController extends Controller
{
    #[OA\Get(
        path: "/global-keyword-categories",
        summary: "List global keyword categories",
        tags: ["Global Keyword Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of global keyword categories",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/GlobalKeywordCategory")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $categories = GlobalKeywordCategory::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );

        return KeywordCategoryResource::collection($categories);
    }

    #[OA\Get(
        path: "/global-keyword-category/{id}",
        summary: "Get global keyword category by ID",
        tags: ["Global Keyword Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global keyword category details",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalKeywordCategory")
            ),
            new OA\Response(response: 404, description: "Global Keyword Category not found")
        ]
    )]
    public function show($id)
    {
        $category = GlobalKeywordCategory::findOrFail($id);
        return new KeywordCategoryResource($category);
    }

    #[OA\Post(
        path: "/global-keyword-categories",
        summary: "Create new global keyword category",
        tags: ["Global Keyword Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/GlobalKeywordCategory")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Global keyword category created",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalKeywordCategory")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|json',
        ]);

        $category = GlobalKeywordCategory::create($validated);

        return (new KeywordCategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-keyword-category/{id}",
        summary: "Update global keyword category",
        tags: ["Global Keyword Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/GlobalKeywordCategory")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Global keyword category updated",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalKeywordCategory")
            ),
            new OA\Response(response: 404, description: "Global Keyword Category not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, $id)
    {
        $category = GlobalKeywordCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|json',
        ]);

        $category->update($validated);
        return new KeywordCategoryResource($category);
    }

    #[OA\Delete(
        path: "/global-keyword-category/{id}",
        summary: "Delete global keyword category",
        tags: ["Global Keyword Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global keyword category deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Global Keyword Category not found")
        ]
    )]
    public function destroy($id)
    {
        $category = GlobalKeywordCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
