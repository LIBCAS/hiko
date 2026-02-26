<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeywordRequest;
use App\Http\Resources\KeywordResource;
use App\Models\Keyword;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Keywords",
    description: "Management of keywords"
)]
class KeywordController extends Controller
{
    #[OA\Get(
        path: "/keywords",
        summary: "List keywords",
        tags: ["Keywords"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of keywords",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Keyword")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $keywords = Keyword::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );

        return KeywordResource::collection($keywords);
    }

    #[OA\Get(
        path: "/keyword/{id}",
        summary: "Get keyword by ID",
        tags: ["Keywords"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Keyword details",
                content: new OA\JsonContent(ref: "#/components/schemas/Keyword")
            ),
            new OA\Response(response: 404, description: "Keyword not found")
        ]
    )]
    public function show($id)
    {
        $keyword = Keyword::findOrFail($id);
        return new KeywordResource($keyword);
    }

    #[OA\Post(
        path: "/keywords",
        summary: "Create new keyword",
        tags: ["Keywords"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/Keyword")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Keyword created",
                content: new OA\JsonContent(ref: "#/components/schemas/Keyword")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 409, description: "Entity already exists"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(KeywordRequest $request)
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return response()->json(['message' => 'Such entity already exists.'], 409);
        }

        $keyword = Keyword::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'keyword_category_id' => $validated['keyword_category_id'] ?? null,
        ]);

        return (new KeywordResource($keyword))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/keyword/{id}",
        summary: "Update keyword",
        tags: ["Keywords"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/Keyword")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Keyword updated",
                content: new OA\JsonContent(ref: "#/components/schemas/Keyword")
            ),
            new OA\Response(response: 404, description: "Keyword not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(KeywordRequest $request, $id)
    {
        $keyword = Keyword::findOrFail($id);
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($keyword->id)) {
            return response()->json(['message' => 'Such entity already exists.'], 422);
        }

        $keyword->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'keyword_category_id' => $validated['keyword_category_id'] ?? null,
        ]);

        return new KeywordResource($keyword);
    }

    #[OA\Delete(
        path: "/keyword/{id}",
        summary: "Delete keyword",
        tags: ["Keywords"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Keyword deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Keyword not found")
        ]
    )]
    public function destroy($id)
    {
        $keyword = Keyword::findOrFail($id);
        $keyword->delete();

        return response()->json(['message' => 'Entity deleted successfully']);
    }
}
