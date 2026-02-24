<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfessionRequest;
use App\Http\Resources\ProfessionResource;
use App\Models\Profession;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Professions",
    description: "Management of professions"
)]
class ProfessionController extends Controller
{
    #[OA\Get(
        path: "/professions",
        summary: "List professions",
        tags: ["Professions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"]))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of professions",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Profession")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $professions = Profession::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );

        return ProfessionResource::collection($professions);
    }

    #[OA\Get(
        path: "/profession/{id}",
        summary: "Get profession by ID",
        tags: ["Professions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Profession details",
                content: new OA\JsonContent(ref: "#/components/schemas/Profession")
            ),
            new OA\Response(response: 404, description: "Profession not found")
        ]
    )]
    public function show($id)
    {
        $profession = Profession::findOrFail($id);
        return new ProfessionResource($profession);
    }

    #[OA\Post(
        path: "/professions",
        summary: "Create new profession",
        tags: ["Professions"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/Profession")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Profession created",
                content: new OA\JsonContent(ref: "#/components/schemas/Profession")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 409, description: "Entity already exists"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(ProfessionRequest $request)
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return response()->json(['message' => __('hiko.entity_already_exists')], 409);
        }

        $profession = Profession::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'profession_category_id' => $validated['profession_category_id'] ?? null,
        ]);

        return new ProfessionResource($profession);
    }

    #[OA\Put(
        path: "/profession/{id}",
        summary: "Update profession",
        tags: ["Professions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/Profession")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Profession updated",
                content: new OA\JsonContent(ref: "#/components/schemas/Profession")
            ),
            new OA\Response(response: 404, description: "Profession not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(ProfessionRequest $request, $id)
    {
        $profession = Profession::findOrFail($id);
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($profession->id)) {
            return response()->json(['message' => __('hiko.entity_already_exists')], 422);
        }

        $profession->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'profession_category_id' => $validated['profession_category_id'] ?? null,
        ]);

        return new ProfessionResource($profession);
    }

    #[OA\Delete(
        path: "/profession/{id}",
        summary: "Delete profession",
        tags: ["Professions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Profession deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Profession not found")
        ]
    )]
    public function destroy($id)
    {
        $profession = Profession::findOrFail($id);
        $profession->delete();

        return response()->json(['message' => __('hiko.removed')]);
    }
}
