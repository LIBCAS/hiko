<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalProfessionRequest;
use App\Http\Resources\ProfessionResource;
use App\Models\GlobalProfession;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Global Professions",
    description: "Management of global professions"
)]
class GlobalProfessionController extends Controller
{
    #[OA\Get(
        path: "/global-professions",
        summary: "List global professions",
        tags: ["Global Professions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of global professions",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/GlobalProfession")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $professions = GlobalProfession::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );

        return ProfessionResource::collection($professions);
    }

    #[OA\Get(
        path: "/global-profession/{id}",
        summary: "Get global profession by ID",
        tags: ["Global Professions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global profession details",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalProfession")
            ),
            new OA\Response(response: 404, description: "Global Profession not found")
        ]
    )]
    public function show($id)
    {
        $profession = GlobalProfession::findOrFail($id);
        return new ProfessionResource($profession);
    }

    #[OA\Post(
        path: "/global-professions",
        summary: "Create new global profession",
        description: "Both Czech and English translations are required, nonblank strings of at most 255 characters. Global endpoints also accept a name object or JSON-encoded object with cs/en keys; top-level translations take precedence. Plain name strings are rejected.",
        tags: ["Global Professions"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["category_id"],
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
                    new OA\Property(property: "cs", type: "string", maxLength: 255, minLength: 1, example: "Global Profession"),
                    new OA\Property(property: "en", type: "string", maxLength: 255, minLength: 1, example: "Global Profession"),
                    new OA\Property(property: "category_id", type: "integer", example: 35),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-profession-637"]),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Global profession created",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalProfession")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(GlobalProfessionRequest $request)
    {
        $validated = $request->validated();
        unset($validated['client_meta']);

        $name = ['cs' => $validated['cs'], 'en' => $validated['en']];

        $profession = GlobalProfession::create([
            'name' => $name,
            'profession_category_id' => $validated['profession_category_id'],
        ]);

        return (new ProfessionResource($profession))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-profession/{id}",
        summary: "Update global profession",
        description: "Partial update: omitted fields remain unchanged. The resulting record must contain nonblank Czech and English names (maximum 255 characters each). Missing existing translations must be supplied; null or blank names return 422. Custom data belongs in client_meta.",
        tags: ["Global Professions"],
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
                    new OA\Property(property: "cs", type: "string", maxLength: 255, minLength: 1, example: "Global Profession"),
                    new OA\Property(property: "en", type: "string", maxLength: 255, minLength: 1, example: "Global Profession"),
                    new OA\Property(property: "category_id", type: "integer", example: 35),
                    new OA\Property(property: "client_meta", type: "object", additionalProperties: new OA\AdditionalProperties(type: "string"), example: ["external_id" => "global-profession-637"]),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Global profession updated",
                content: new OA\JsonContent(ref: "#/components/schemas/GlobalProfession")
            ),
            new OA\Response(response: 404, description: "Global Profession not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(GlobalProfessionRequest $request, $id)
    {
        $profession = GlobalProfession::findOrFail($id);
        $validated = $request->validated();
        unset($validated['client_meta']);

        $name = ['cs' => $validated['cs'], 'en' => $validated['en']];

        $profession->update([
            'name' => $name,
            'profession_category_id' => $validated['profession_category_id'] ?? $profession->profession_category_id,
        ]);
        return new ProfessionResource($profession);
    }

    #[OA\Delete(
        path: "/global-profession/{id}",
        summary: "Delete global profession",
        tags: ["Global Professions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Global profession deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Global Profession not found")
        ]
    )]
    public function destroy($id)
    {
        $profession = GlobalProfession::findOrFail($id);
        $profession->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
