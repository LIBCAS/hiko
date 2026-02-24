<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfessionCategoryResource;
use App\Models\GlobalProfessionCategory;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Global Profession Categories",
    description: "Management of global profession categories"
)]
class GlobalProfessionCategoryController extends Controller
{
    #[OA\Get(
        path: "/global-profession-categories",
        summary: "List global profession categories",
        tags: ["Global Profession Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of global profession categories",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/GlobalProfessionCategory")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $categories = GlobalProfessionCategory::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );

        return ProfessionCategoryResource::collection($categories);
    }

    #[OA\Get(
        path: "/global-profession-category/{id}",
        summary: "Get global profession category by ID",
        tags: ["Global Profession Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global profession category details",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalProfessionCategory")
            ),
            new OA\Response(response: 404, description: "Global Profession Category not found")
        ]
    )]
    public function show($id)
    {
        $category = GlobalProfessionCategory::findOrFail($id);
        return new ProfessionCategoryResource($category);
    }

    #[OA\Post(
        path: "/global-profession-categories",
        summary: "Create new global profession category",
        tags: ["Global Profession Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/GlobalProfessionCategory")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Global profession category created",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalProfessionCategory")
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

        $category = GlobalProfessionCategory::create($validated);
        return new ProfessionCategoryResource($category);
    }

    #[OA\Put(
        path: "/global-profession-category/{id}",
        summary: "Update global profession category",
        tags: ["Global Profession Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/GlobalProfessionCategory")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Global profession category updated",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalProfessionCategory")
            ),
            new OA\Response(response: 404, description: "Global Profession Category not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, $id)
    {
        $category = GlobalProfessionCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|json',
        ]);

        $category->update($validated);
        return new ProfessionCategoryResource($category);
    }

    #[OA\Delete(
        path: "/global-profession-category/{id}",
        summary: "Delete global profession category",
        tags: ["Global Profession Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global profession category deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Global Profession Category not found")
        ]
    )]
    public function destroy($id)
    {
        $category = GlobalProfessionCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
