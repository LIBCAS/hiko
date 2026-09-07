<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\v2\Concerns\ValidatesApiV2Writes;
use App\Http\Controllers\Controller;
use App\Http\Resources\KeywordCategoryResource;
use App\Models\GlobalKeywordCategory;
use Illuminate\Http\Request;
use App\Http\Requests\GlobalKeywordCategoryRequest;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Global Keyword Categories",
    description: "Management of global keyword categories"
)]
class GlobalKeywordCategoryController extends Controller
{
    use ValidatesApiV2Writes;

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
        description: "Both Czech and English translations are required, nonblank strings of at most 255 characters. Global endpoints also accept a name object or JSON-encoded object with cs/en keys; top-level translations take precedence. Plain name strings are rejected.",
        tags: ["Global Keyword Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                anyOf: [
                    new OA\Schema(required: ["cs", "en"]),
                    new OA\Schema(required: ["name"]),
                ],
                properties: [
                    new OA\Property(property: "name", description: "Alternative translated name object or JSON-encoded object. Top-level cs/en take precedence. Omitted translations on update retain stored values; both resulting translations must be valid.", oneOf: [
                        new OA\Schema(type: "object", properties: [
                            new OA\Property(property: "cs", type: "string", maxLength: 255, minLength: 1),
                            new OA\Property(property: "en", type: "string", maxLength: 255, minLength: 1),
                        ], additionalProperties: false),
                        new OA\Schema(type: "string", description: "JSON-encoded translation object; not a plain name", example: '{"cs":"Ukázka","en":"Example"}'),
                    ]),
                    new OA\Property(property: "cs", type: "string", maxLength: 255, minLength: 1, example: "Global keyword category"),
                    new OA\Property(property: "en", type: "string", maxLength: 255, minLength: 1, example: "Global keyword category"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-keyword-category-31"]),
                ],
                example: [
                    "cs" => "Global keyword category",
                    "en" => "Global keyword category",
                    "client_meta" => ["external_id" => "global-keyword-category-31"],
                ]
            )
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
    public function store(GlobalKeywordCategoryRequest $request)
    {
        $validated = $request->validated();
        unset($validated['client_meta']);

        $name = ['cs' => $validated['cs'], 'en' => $validated['en']];

        $category = GlobalKeywordCategory::create(['name' => $name]);

        return (new KeywordCategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-keyword-category/{id}",
        summary: "Update global keyword category",
        description: "Partial update: omitted fields remain unchanged. The resulting record must contain nonblank Czech and English names (maximum 255 characters each). Missing existing translations must be supplied; null or blank names return 422. Custom data belongs in client_meta.",
        tags: ["Global Keyword Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", description: "Alternative translated name object or JSON-encoded object. Top-level cs/en take precedence. Omitted translations on update retain stored values; both resulting translations must be valid.", oneOf: [
                        new OA\Schema(type: "object", properties: [
                            new OA\Property(property: "cs", type: "string", maxLength: 255, minLength: 1),
                            new OA\Property(property: "en", type: "string", maxLength: 255, minLength: 1),
                        ], additionalProperties: false),
                        new OA\Schema(type: "string", description: "JSON-encoded translation object; not a plain name", example: '{"cs":"Ukázka","en":"Example"}'),
                    ]),
                    new OA\Property(property: "cs", type: "string", maxLength: 255, minLength: 1, example: "Updated global keyword category"),
                    new OA\Property(property: "en", type: "string", maxLength: 255, minLength: 1, example: "Updated global keyword category"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-keyword-category-31"]),
                ],
                example: [
                    "en" => "Updated global keyword category",
                    "client_meta" => ["external_id" => "global-keyword-category-31"],
                ]
            )
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
    public function update(GlobalKeywordCategoryRequest $request, $id)
    {
        $category = GlobalKeywordCategory::findOrFail($id);

        $validated = $request->validated();
        unset($validated['client_meta']);

        $name = ['cs' => $validated['cs'], 'en' => $validated['en']];

        $category->update(['name' => $name]);
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
