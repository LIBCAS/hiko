<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v2\LetterRequest;
use App\Http\Resources\LetterCollection;
use App\Http\Resources\LetterResource;
use App\Jobs\RegenerateNames;
use App\Models\Letter;
use App\Services\LetterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Letters",
    description: "Management of letters (correspondence)"
)]
class LetterController extends Controller
{
    protected LetterService $letterService;
    public static $maxPerPage = 500;    // Maximum number of items per page
    public static $defaultPerPage = 100; // Default number of items per page

    #[OA\Get(
        path: "/letters",
        summary: "List letters",
        tags: ["Letters"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sort", in: "query", description: "Sort by field. Prefix with '-' for descending. Allowed: created_at, updated_at, date_computed, date_year, status.", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "lang", in: "query", description: "Language (cs or en)", schema: new OA\Schema(type: "string", enum: ["cs", "en"])),
            new OA\Parameter(name: "include", in: "query", description: "Include related resources (comma-separated). Allowed: identities,identities.localProfessions,identities.localProfessions.profession_category,identities.globalProfessions,identities.globalProfessions.profession_category,places,globalPlaces,keywords,globalKeywords,media,users", schema: new OA\Schema(type: "string")),
            // Filters
            new OA\Parameter(name: "filter[fulltext]", in: "query", description: "Search in content (stripped)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[id]", in: "query", description: "Filter by ID (supports partial match)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[status]", in: "query", description: "Filter by status", schema: new OA\Schema(type: "string", enum: ["publish", "draft"])),
            new OA\Parameter(name: "filter[approval]", in: "query", description: "Filter by approval status", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[abstract]", in: "query", description: "Search in abstract (cs/en)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[content]", in: "query", description: "Search in content (stripped)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[signature]", in: "query", description: "Search in signature", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[repository]", in: "query", description: "Search in repository", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[archive]", in: "query", description: "Search in archive", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[collection]", in: "query", description: "Search in collection", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[note]", in: "query", description: "Search in all note fields", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[after]", in: "query", description: "Date computed after (YYYY-MM-DD or YYYY)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[before]", in: "query", description: "Date computed before (YYYY-MM-DD or YYYY)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[created_at_after]", in: "query", description: "Created at after", schema: new OA\Schema(type: "string", format: "date-time")),
            new OA\Parameter(name: "filter[created_at_before]", in: "query", description: "Created at before", schema: new OA\Schema(type: "string", format: "date-time")),
            new OA\Parameter(name: "filter[updated_at_after]", in: "query", description: "Updated at after", schema: new OA\Schema(type: "string", format: "date-time")),
            new OA\Parameter(name: "filter[updated_at_before]", in: "query", description: "Updated at before", schema: new OA\Schema(type: "string", format: "date-time")),
            new OA\Parameter(name: "filter[author]", in: "query", description: "Filter by author (name or ID)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[recipient]", in: "query", description: "Filter by recipient (name or ID)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[origin]", in: "query", description: "Filter by origin (name or ID)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[destination]", in: "query", description: "Filter by destination (name or ID)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[keyword]", in: "query", description: "Filter by keyword (name or ID like local-1, global-2)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[mentioned]", in: "query", description: "Filter by mentioned person (name or ID)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter[editor]", in: "query", description: "Filter by editor name (requires permission)", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of letters",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Letter")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', self::$defaultPerPage);
        $page = (int) $request->query('page', 1);

        $perPage = max(1, min(self::$maxPerPage, $perPage));
        $page = max(1, $page);

        $includes = $request->query('include') ? explode(',', $request->query('include')) : [];

        $allowedSorts = ['created_at', 'updated_at', 'date_computed', 'date_year', 'status'];
        $defaultSorts = ['date_computed', '-status'];
        $customSorts = $request->query('sort')
            ? array_filter(
                explode(',', $request->query('sort')),
                fn($s) => in_array(ltrim($s, '-'), $allowedSorts, true)
            )
            : [];
        $sorts = !empty($customSorts) ? $customSorts : $defaultSorts;

        $letters = QueryBuilder::for(Letter::class)
            ->allowedIncludes([
                'identities',
                'identities.localProfessions',
                'identities.localProfessions.profession_category',
                'identities.globalProfessions',
                'identities.globalProfessions.profession_category',
                'places',
                'globalPlaces',
                'keywords',
                'globalKeywords',
                'media',
                'users',
            ])
            ->tap(function ($query) use ($request) {
                $query->filter($request->query('filter', []));  // Use the existing `filter()` logic in LetterBuilder
            })
            ->defaultSort($sorts)
            ->with($includes)
            ->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->query());

        return new LetterCollection($letters);
    }

    #[OA\Get(
        path: "/letter/{id}",
        summary: "Get letter by ID",
        tags: ["Letters"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Letter details",
                content: new OA\JsonContent(ref: "#/components/schemas/Letter")
            ),
            new OA\Response(response: 404, description: "Letter not found")
        ]
    )]
    public function show($id)
    {
        $letter = Letter::with([
                'identities',
                'identities.localProfessions',
                'identities.localProfessions.profession_category',
                'identities.globalProfessions',
                'identities.globalProfessions.profession_category',
                'places',
                'globalPlaces',
                'keywords',
                'globalKeywords',
                'media',
                'users',
            ])
            ->where('id', $id)
            ->firstOrFail();

        return new LetterResource($letter);
    }

    #[OA\Post(
        path: "/letters",
        summary: "Create new letter",
        tags: ["Letters"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/Letter")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Letter created",
                content: new OA\JsonContent(ref: "#/components/schemas/Letter")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(LetterRequest $request)
    {
        $data = $request->validated();
        unset($data['copies']);

        $letter = Letter::create($data);

        $this->letterService->syncManifestations($letter, $request->input('copies', []));

        $this->attachRelated($request, $letter);

        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        Log::info('API V2: Letter created', ['letter_id' => $letter->id]);

        return (new LetterResource($letter))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: "/letter/{id}",
        summary: "Update letter",
        tags: ["Letters"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/Letter")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Letter updated",
                content: new OA\JsonContent(ref: "#/components/schemas/Letter")
            ),
            new OA\Response(response: 404, description: "Letter not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(LetterRequest $request, $id)
    {
        $letter = Letter::findOrFail($id);

        $data = $request->validated();
        unset($data['copies']);

        $letter->update($data);

        $this->letterService->syncManifestations($letter, $request->input('copies', []));

        $this->attachRelated($request, $letter);

        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        Log::info('API V2: Letter updated', ['letter_id' => $letter->id]);

        return new LetterResource($letter);
    }

    #[OA\Delete(
        path: "/letter/{id}",
        summary: "Delete letter",
        tags: ["Letters"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Letter deleted",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Entity deleted successfully.")]
                )
            ),
            new OA\Response(response: 404, description: "Letter not found")
        ]
    )]
    public function destroy($id)
    {
        $letter = Letter::findOrFail($id);
        $letter->delete();

        return response()->json(['message' => 'Entity deleted successfully.']);
    }

    /**
     * Convert an array of items from the request into pivot data for attach().
     * Each item includes a 'id' => numeric ID, plus optional fields like 'marked'.
     */
    protected function prepareAttachmentData(?array $items, string $role, array $pivotFields = []): array
    {
        if (!$items) {
            return [];
        }

        $results = [];
        foreach ($items as $position => $item) {
            // e.g. "local-5" => "5"
            $id = isset($item['id']) ? preg_replace('/\D/', '', $item['id']) : null;

            if ($id && is_numeric($id)) {
                $data = [
                    'position' => $position,
                    'role'     => $role,
                    'marked'   => $item['marked'] ?? null,
                ];

                // If we have extra pivot fields
                foreach ($pivotFields as $field) {
                    $data[$field] = $item[$field] ?? null;
                }

                $results[$id] = $data;
            } else {
                Log::warning("Invalid pivot data for role '{$role}' at position {$position}.", $item);
            }
        }

        return $results;
    }

    protected function attachRelated(Request $request, Letter $letter)
    {
        $localKeywords = $request->local_keywords ?? [];
        $letter->localKeywords()->sync($localKeywords);

        $globalKeywords = $request->global_keywords ?? [];
        $letter->globalKeywords()->sync($globalKeywords);

        $letter->identities()->detach();
        $letter->localPlaces()->detach();
        $letter->globalPlaces()->detach();

        $letter->identities()->attach($this->prepareAttachmentData($request->authors, 'author'));
        $letter->identities()->attach($this->prepareAttachmentData($request->recipients, 'recipient', ['salutation']));

        // Handle origins (local_origins and global_origins)
        $this->attachPlacesToLetter($letter, $request->local_origins, 'origin', false);
        $this->attachPlacesToLetter($letter, $request->global_origins, 'origin', true);

        // Handle destinations (local_destinations and global_destinations)
        $this->attachPlacesToLetter($letter, $request->local_destinations, 'destination', false);
        $this->attachPlacesToLetter($letter, $request->global_destinations, 'destination', true);

        // Ensure mentioned is an array
        $mentioned = [];
        if (is_array($request->mentioned)) {
            foreach ($request->mentioned as $key => $id) {
                // Extract numeric part from the ID (e.g., 'local-7' -> 7)
                $numericId = preg_replace('/\D/', '', $id);
                if (is_numeric($numericId)) {
                    $mentioned[$numericId] = [
                        'position' => $key,
                        'role' => 'mentioned',
                    ];
                } else {
                    Log::warning("Invalid mentioned ID: {$id}");
                }
            }
        }

        $letter->identities()->attach($mentioned);
    }

    /**
     * Attach places (local or global) to a letter with the specified role for API.
     *
     * @param Letter $letter
     * @param array|null $places
     * @param string $role
     * @param bool $isGlobal
     * @return void
     */
    protected function attachPlacesToLetter(Letter $letter, ?array $places, string $role, bool $isGlobal): void
    {
        if (!$places) {
            return;
        }

        $preparedData = $this->prepareAttachmentData($places, $role);

        if ($isGlobal) {
            $letter->globalPlaces()->attach($preparedData);
        } else {
            $letter->localPlaces()->attach($preparedData);
        }
    }
}
