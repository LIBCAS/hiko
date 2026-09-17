<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\Nationality;
use App\Http\Requests\NationalityRequest;
use App\Http\Resources\NationalityResource;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Nationalities', description: 'Global bilingual nationality catalogue. Writes require manage-users.')]
class NationalityController extends Controller
{
    #[OA\Get(path: '/nationalities', tags: ['Nationalities'], security: [['bearerAuth' => []]], parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', description: 'Records per page (default 200, maximum 200).', schema: new OA\Schema(type: 'integer', default: 200, minimum: 1, maximum: 200)),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
    ], responses: [new OA\Response(response: 200, description: 'Paginated nationalities')])]
    public function index(Request $request)
    {
        $data = $request->validate(['per_page' => 'sometimes|integer|min:1|max:200', 'page' => 'sometimes|integer|min:1']);
        return NationalityResource::collection(Nationality::orderBy('id')->paginate($data['per_page'] ?? 200));
    }

    #[OA\Get(path: '/nationality/{nationality}', tags: ['Nationalities'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nationality', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Nationality', content: new OA\JsonContent(ref: '#/components/schemas/Nationality'))])]
    public function show(Nationality $nationality)
    {
        return new NationalityResource($nationality);
    }

    #[OA\Post(path: '/nationalities', tags: ['Nationalities'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['cs', 'en'], properties: [new OA\Property(property: 'cs', type: 'string'), new OA\Property(property: 'en', type: 'string')])), responses: [new OA\Response(response: 201, description: 'Created')])]
    public function store(NationalityRequest $request)
    {
        return (new NationalityResource(Nationality::create(['name' => $request->validated()])))->response()->setStatusCode(201);
    }

    #[OA\Put(path: '/nationality/{nationality}', tags: ['Nationalities'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nationality', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'cs', type: 'string'), new OA\Property(property: 'en', type: 'string')])), responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function update(NationalityRequest $request, Nationality $nationality)
    {
        $nationality->update(['name' => $request->validated()]);
        return new NationalityResource($nationality);
    }

    #[OA\Delete(path: '/nationality/{nationality}', tags: ['Nationalities'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nationality', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 204, description: 'Deleted'), new OA\Response(response: 409, description: 'Nationality is assigned')])]
    public function destroy(Nationality $nationality)
    {
        try {
            $nationality->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== 1451) throw $e;
            return response()->json(['message' => __('hiko.nationality_in_use')], 409);
        }
        return response()->noContent();
    }
}
