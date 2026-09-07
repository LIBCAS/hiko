<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\v2\Concerns\ValidatesApiV2Writes;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProfessionCategoryResource;
use App\Models\GlobalProfessionCategory;
use Illuminate\Http\Request;
use App\Http\Requests\GlobalProfessionCategoryRequest;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Global Profession Categories",
    description: "Management of global profession categories"
)]
class GlobalProfessionCategoryController extends Controller
{
    use ValidatesApiV2Writes;

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
        description: "Both Czech and English translations are required, nonblank strings of at most 255 characters. Global endpoints also accept a name object or JSON-encoded object with cs/en keys; top-level translations take precedence. Plain name strings are rejected.",
        tags: ["Global Profession Categories"],
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
                    new OA\Property(property: "cs", type: "string", maxLength: 255, minLength: 1, example: "Global profession category"),
                    new OA\Property(property: "en", type: "string", maxLength: 255, minLength: 1, example: "Global profession category"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-profession-category-35"]),
                ],
                example: [
                    "cs" => "Global profession category",
                    "en" => "Global profession category",
                    "client_meta" => ["external_id" => "global-profession-category-35"],
                ]
            )
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
    public function store(GlobalProfessionCategoryRequest $request)
    {
        $validated = $request->validated();
        unset($validated['client_meta']);

        $name = ['cs' => $validated['cs'], 'en' => $validated['en']];

        $category = GlobalProfessionCategory::create(['name' => $name]);

        return (new ProfessionCategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-profession-category/{id}",
        summary: "Update global profession category",
        description: "Partial update: omitted fields remain unchanged. The resulting record must contain nonblank Czech and English names (maximum 255 characters each). Missing existing translations must be supplied; null or blank names return 422. Custom data belongs in client_meta.",
        tags: ["Global Profession Categories"],
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
                    new OA\Property(property: "cs", type: "string", maxLength: 255, minLength: 1, example: "Updated global profession category"),
                    new OA\Property(property: "en", type: "string", maxLength: 255, minLength: 1, example: "Updated global profession category"),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-profession-category-35"]),
                ],
                example: [
                    "en" => "Updated global profession category",
                    "client_meta" => ["external_id" => "global-profession-category-35"],
                ]
            )
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
    public function update(GlobalProfessionCategoryRequest $request, $id)
    {
        $category = GlobalProfessionCategory::findOrFail($id);

        $validated = $request->validated();
        unset($validated['client_meta']);

        $name = ['cs' => $validated['cs'], 'en' => $validated['en']];

        $category->update(['name' => $name]);
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
            new OA\Response(response: 404, description: "Global Profession Category not found"),
            new OA\Response(response: 409, description: "Global profession category is used by professions")
        ]
    )]
    public function destroy($id)
    {
        $category = GlobalProfessionCategory::findOrFail($id);

        if ($category->professions()->exists()) {
            return response()->json([
                'message' => __('hiko.profession_category_in_use'),
            ], Response::HTTP_CONFLICT);
        }

        $category->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
