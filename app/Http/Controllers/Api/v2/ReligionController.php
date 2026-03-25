<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReligionResource;
use App\Models\Religion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Religions",
    description: "Read-only listing of religions for identity assignment"
)]
class ReligionController extends Controller
{
    public static int $maxPerPage = 100;
    public static int $defaultPerPage = 20;

    #[OA\Get(
        path: "/religions",
        summary: "List religions",
        tags: ["Religions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "locale", in: "query", description: "Preferred locale (cs|en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"])),
            new OA\Parameter(name: "active", in: "query", description: "Filter by active state (1/0/true/false)", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Paginated list of religions",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Religion")),
                        new OA\Property(property: "meta", type: "object"),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', self::$defaultPerPage), 1), self::$maxPerPage);
        $locale = in_array((string) $request->query('locale', app()->getLocale()), ['cs', 'en'], true)
            ? (string) $request->query('locale', app()->getLocale())
            : 'cs';

        $query = $this->baseLocalizedQuery($locale);

        $active = $this->parseBooleanQuery($request->query('active'));
        if ($active !== null) {
            $query->where('religions.is_active', $active ? 1 : 0);
        }

        $religions = $query->paginate($perPage);

        return ReligionResource::collection($religions);
    }

    #[OA\Get(
        path: "/religion/{id}",
        summary: "Get religion by ID",
        tags: ["Religions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "locale", in: "query", description: "Preferred locale (cs|en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Religion details",
                content: new OA\JsonContent(ref: "#/components/schemas/Religion")
            ),
            new OA\Response(response: 404, description: "Religion not found"),
        ]
    )]
    public function show(Request $request, int $id)
    {
        $locale = in_array((string) $request->query('locale', app()->getLocale()), ['cs', 'en'], true)
            ? (string) $request->query('locale', app()->getLocale())
            : 'cs';

        $religion = $this->baseLocalizedQuery($locale)
            ->where('religions.id', $id)
            ->firstOrFail();

        return new ReligionResource($religion);
    }

    private function baseLocalizedQuery(string $locale)
    {
        return Religion::query()
            ->select('religions.*')
            ->leftJoin('religion_translations as rt_loc', function ($join) use ($locale) {
                $join->on('rt_loc.religion_id', '=', 'religions.id')
                    ->where('rt_loc.locale', '=', $locale);
            })
            ->leftJoin('religion_translations as rt_cs', function ($join) {
                $join->on('rt_cs.religion_id', '=', 'religions.id')
                    ->where('rt_cs.locale', '=', 'cs');
            })
            ->addSelect(DB::raw('COALESCE(rt_loc.name, rt_cs.name, religions.name) as translated_name'))
            ->addSelect(DB::raw('COALESCE(rt_loc.path_text, rt_cs.path_text, religions.path_text) as translated_path_text'))
            ->orderBy('religions.sort_order')
            ->orderBy('translated_path_text');
    }

    private function parseBooleanQuery(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($value, [1, '1', true, 'true'], true)) {
            return true;
        }

        if (in_array($value, [0, '0', false, 'false'], true)) {
            return false;
        }

        return null;
    }
}
