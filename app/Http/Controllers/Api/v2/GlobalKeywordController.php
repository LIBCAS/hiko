<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Resources\KeywordResource;
use App\Models\GlobalKeyword;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Global Keywords",
    description: "Management of global keywords"
)]
class GlobalKeywordController extends Controller
{
    #[OA\Get(
        path: "/global-keywords",
        summary: "List global keywords",
        tags: ["Global Keywords"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of global keywords",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/GlobalKeyword")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $keywords = GlobalKeyword::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );

        return KeywordResource::collection($keywords);
    }

    #[OA\Get(
        path: "/global-keyword/{id}",
        summary: "Get global keyword by ID",
        tags: ["Global Keywords"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global keyword details",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalKeyword")
            ),
            new OA\Response(response: 404, description: "Global Keyword not found")
        ]
    )]
    public function show($id)
    {
        $keyword = GlobalKeyword::findOrFail($id);
        return new KeywordResource($keyword);
    }

    #[OA\Post(
        path: "/global-keywords",
        summary: "Create new global keyword",
        tags: ["Global Keywords"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/GlobalKeyword")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Global keyword created",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalKeyword")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|json',
            'keyword_category_id' => 'nullable|exists:global_keyword_categories,id',
        ]);

        $keyword = GlobalKeyword::create($validated);

        return (new KeywordResource($keyword))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-keyword/{id}",
        summary: "Update global keyword",
        tags: ["Global Keywords"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/GlobalKeyword")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Global keyword updated",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalKeyword")
            ),
            new OA\Response(response: 404, description: "Global Keyword not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, $id)
    {
        $keyword = GlobalKeyword::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|json',
            'keyword_category_id' => 'nullable|exists:global_keyword_categories,id',
        ]);

        $keyword->update($validated);
        return new KeywordResource($keyword);
    }

    #[OA\Delete(
        path: "/global-keyword/{id}",
        summary: "Delete global keyword",
        tags: ["Global Keywords"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global keyword deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Global Keyword not found")
        ]
    )]
    public function destroy($id)
    {
        $keyword = GlobalKeyword::findOrFail($id);
        $keyword->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
