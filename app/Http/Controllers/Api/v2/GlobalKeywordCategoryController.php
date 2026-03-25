<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\v2\Concerns\ValidatesApiV2Writes;
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
        tags: ["Global Keyword Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "cs", type: "string", nullable: true, example: "Global keyword category"),
                    new OA\Property(property: "en", type: "string", nullable: true, example: "Global keyword category"),
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
    public function store(Request $request)
    {
        if ($response = $this->rejectUnknownFields($request, ['name', 'cs', 'en', 'client_meta'])) {
            return $response;
        }

        $validated = $request->validate([
            'name' => 'nullable',
            'cs' => 'nullable|string|max:255|required_without_all:en,name',
            'en' => 'nullable|string|max:255|required_without_all:cs,name',
            'client_meta' => 'nullable|array',
        ]);
        unset($validated['client_meta']);

        $name = $this->normalizeTranslatedName($request);
        if (($name['cs'] ?? null) === null && ($name['en'] ?? null) === null) {
            return response()->json(['message' => 'The name field is required.'], 422);
        }

        $category = GlobalKeywordCategory::create(['name' => $name]);

        return (new KeywordCategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-keyword-category/{id}",
        summary: "Update global keyword category",
        description: "Partial update semantics. Omitted fields remain unchanged, null clears nullable translated fields, and client-specific extra data belongs in client_meta.",
        tags: ["Global Keyword Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "cs", type: "string", nullable: true, example: "Updated global keyword category"),
                    new OA\Property(property: "en", type: "string", nullable: true, example: "Updated global keyword category"),
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
    public function update(Request $request, $id)
    {
        $category = GlobalKeywordCategory::findOrFail($id);

        if ($response = $this->rejectUnknownFields($request, ['name', 'cs', 'en', 'client_meta'])) {
            return $response;
        }

        $validated = $request->validate([
            'name' => 'nullable',
            'cs' => 'sometimes|nullable|string|max:255',
            'en' => 'sometimes|nullable|string|max:255',
            'client_meta' => 'nullable|array',
        ]);
        unset($validated['client_meta']);

        $currentName = $category->getTranslations('name');
        $name = $this->normalizeTranslatedName($request);
        $name = [
            'cs' => array_key_exists('cs', $validated) ? ($name['cs'] ?? null) : ($currentName['cs'] ?? null),
            'en' => array_key_exists('en', $validated) ? ($name['en'] ?? null) : ($currentName['en'] ?? null),
        ];

        $category->update(['name' => $name]);
        return new KeywordCategoryResource($category);
    }

    private function normalizeTranslatedName(Request $request): array
    {
        if ($request->filled('cs') || $request->filled('en')) {
            return [
                'cs' => $request->filled('cs') ? trim((string) $request->input('cs')) : null,
                'en' => $request->filled('en') ? trim((string) $request->input('en')) : null,
            ];
        }

        $rawName = $request->input('name');
        $decoded = is_string($rawName) ? json_decode($rawName, true) : (is_array($rawName) ? $rawName : null);

        if (is_array($decoded)) {
            return [
                'cs' => isset($decoded['cs']) ? trim((string) $decoded['cs']) : null,
                'en' => isset($decoded['en']) ? trim((string) $decoded['en']) : null,
            ];
        }

        if (is_string($rawName) && trim($rawName) !== '') {
            $value = trim($rawName);
            return ['cs' => $value, 'en' => $value];
        }

        return ['cs' => null, 'en' => null];
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
