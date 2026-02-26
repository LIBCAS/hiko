<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
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
        tags: ["Global Professions"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/GlobalProfession")
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'profession_category_id' => 'nullable|exists:global_profession_categories,id',
        ]);

        $profession = GlobalProfession::create($validated);

        return (new ProfessionResource($profession))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/global-profession/{id}",
        summary: "Update global profession",
        tags: ["Global Professions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/GlobalProfession")
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
    public function update(Request $request, $id)
    {
        $profession = GlobalProfession::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'profession_category_id' => 'nullable|exists:global_profession_categories,id',
        ]);

        $profession->update($validated);
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
