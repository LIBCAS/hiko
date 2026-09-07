<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\v2\Concerns\ValidatesApiV2Writes;
use App\Http\Controllers\Controller;
use App\Http\Resources\KeywordResource;
use App\Models\GlobalKeyword;
use Illuminate\Http\Request;
use App\Http\Requests\GlobalKeywordRequest;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Global Keywords",
    description: "Management of global keywords"
)]
class GlobalKeywordController extends Controller
{
    use ValidatesApiV2Writes;

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
        description: "Both Czech and English translations are required, nonblank strings of at most 255 characters. Global endpoints also accept a name object or JSON-encoded object with cs/en keys; top-level translations take precedence. Plain name strings are rejected.",
        tags: ["Global Keywords"],
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
                    new OA\Property(property: "cs", type: "string", maxLength: 255, minLength: 1, example: "Global keyword"),
                    new OA\Property(property: "en", type: "string", maxLength: 255, minLength: 1, example: "Global keyword"),
                    new OA\Property(property: "category_id", type: "integer", nullable: true, example: 31),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-keyword-10442"]),
                ]
            )
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
    public function store(GlobalKeywordRequest $request)
    {
        $validated = $request->validated();
        unset($validated['client_meta']);

        $name = ['cs' => $validated['cs'], 'en' => $validated['en']];

        $keyword = GlobalKeyword::create([
            'name' => $name,
            'keyword_category_id' => $validated['category_id'] ?? $validated['keyword_category_id'] ?? null,
        ]);

        return (new KeywordResource($keyword))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-keyword/{id}",
        summary: "Update global keyword",
        description: "Partial update: omitted fields remain unchanged. The resulting record must contain nonblank Czech and English names (maximum 255 characters each). Missing existing translations must be supplied; null or blank names return 422. Custom data belongs in client_meta.",
        tags: ["Global Keywords"],
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
                    new OA\Property(property: "cs", type: "string", maxLength: 255, minLength: 1, example: "Global keyword"),
                    new OA\Property(property: "en", type: "string", maxLength: 255, minLength: 1, example: "Global keyword"),
                    new OA\Property(property: "category_id", type: "integer", nullable: true, example: 31),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-keyword-10442"]),
                ]
            )
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
    public function update(GlobalKeywordRequest $request, $id)
    {
        $keyword = GlobalKeyword::findOrFail($id);

        $validated = $request->validated();
        unset($validated['client_meta']);

        $name = ['cs' => $validated['cs'], 'en' => $validated['en']];

        $keyword->update([
            'name' => $name,
            'keyword_category_id' => $validated['category_id'] ?? $validated['keyword_category_id'] ?? $keyword->keyword_category_id,
        ]);
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
