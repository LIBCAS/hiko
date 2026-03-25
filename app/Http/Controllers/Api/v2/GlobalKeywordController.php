<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\v2\Concerns\ValidatesApiV2Writes;
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
        tags: ["Global Keywords"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "cs", type: "string", nullable: true, example: "Global keyword"),
                    new OA\Property(property: "en", type: "string", nullable: true, example: "Global keyword"),
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
    public function store(Request $request)
    {
        if ($response = $this->rejectUnknownFields($request, ['name', 'cs', 'en', 'category_id', 'keyword_category_id', 'client_meta'])) {
            return $response;
        }

        $validated = $request->validate([
            'name' => 'nullable',
            'cs' => 'nullable|string|max:255|required_without_all:en,name',
            'en' => 'nullable|string|max:255|required_without_all:cs,name',
            'category_id' => 'nullable|exists:global_keyword_categories,id',
            'keyword_category_id' => 'nullable|exists:global_keyword_categories,id',
            'client_meta' => 'nullable|array',
        ]);
        unset($validated['client_meta']);

        $name = $this->normalizeTranslatedName($request);
        if (($name['cs'] ?? null) === null && ($name['en'] ?? null) === null) {
            return response()->json(['message' => 'The name field is required.'], 422);
        }

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
        description: "Partial update semantics. Omitted fields remain unchanged, null clears nullable translated fields, and client-specific extra data belongs in client_meta.",
        tags: ["Global Keywords"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "cs", type: "string", nullable: true, example: "Global keyword"),
                    new OA\Property(property: "en", type: "string", nullable: true, example: "Global keyword"),
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
    public function update(Request $request, $id)
    {
        $keyword = GlobalKeyword::findOrFail($id);

        if ($response = $this->rejectUnknownFields($request, ['name', 'cs', 'en', 'category_id', 'keyword_category_id', 'client_meta'])) {
            return $response;
        }

        $validated = $request->validate([
            'name' => 'nullable',
            'cs' => 'sometimes|nullable|string|max:255',
            'en' => 'sometimes|nullable|string|max:255',
            'category_id' => 'sometimes|nullable|exists:global_keyword_categories,id',
            'keyword_category_id' => 'sometimes|nullable|exists:global_keyword_categories,id',
            'client_meta' => 'nullable|array',
        ]);
        unset($validated['client_meta']);

        $currentName = $keyword->getTranslations('name');
        $name = $this->normalizeTranslatedName($request);
        $name = [
            'cs' => array_key_exists('cs', $validated) ? ($name['cs'] ?? null) : ($currentName['cs'] ?? null),
            'en' => array_key_exists('en', $validated) ? ($name['en'] ?? null) : ($currentName['en'] ?? null),
        ];

        $keyword->update([
            'name' => $name,
            'keyword_category_id' => $validated['category_id'] ?? $validated['keyword_category_id'] ?? $keyword->keyword_category_id,
        ]);
        return new KeywordResource($keyword);
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
