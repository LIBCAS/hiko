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
use Illuminate\Support\Facades\DB;
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

    public function __construct(LetterService $letterService)
    {
        $this->letterService = $letterService;
    }

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
            new OA\Parameter(name: "include", in: "query", description: "Include related resources (comma-separated). Allowed: identities,globalIdentities,identities.localProfessions,identities.localProfessions.profession_category,identities.globalProfessions,identities.globalProfessions.profession_category,places,globalPlaces,keywords,globalKeywords,media,users", schema: new OA\Schema(type: "string")),
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
                    example: [
                        "data" => [
                            [
                                "id" => 4069,
                                "uuid" => "07032d70-f4e1-4c5f-b8bc-124f5d3ea5b5",
                                "dates" => [
                                    "date" => "13. 9. 1933",
                                    "computed" => "1933-09-13",
                                ],
                                "signatures" => ["SIG-api-v2-live-20260302141804-3260cd3d"],
                                "authors" => ["Local Person, Author"],
                                "recipients" => ["Global Person, Recipient"],
                                "origins" => ["Local Place"],
                                "destinations" => ["Global Place"],
                            ],
                        ],
                        "meta" => [
                            "current_page" => 1,
                            "last_page" => 3,
                            "current_page_of_total" => "1 / 3",
                            "per_page" => 100,
                            "current_page_items" => "1 - 100",
                            "total_item_count" => 235,
                            "current_item_count" => 100,
                        ],
                    ],
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
                'globalIdentities',
                'authors',
                'recipients',
                'mentioned',
                'globalAuthors',
                'globalRecipients',
                'globalMentioned',
                'origins',
                'destinations',
                'globalOrigins',
                'globalDestinations',
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
            ->with(array_merge([
                'globalIdentities',
                'authors',
                'recipients',
                'mentioned',
                'globalAuthors',
                'globalRecipients',
                'globalMentioned',
                'origins',
                'destinations',
                'globalOrigins',
                'globalDestinations',
            ], $includes))
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
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Letter",
                    example: [
                        "data" => [
                            "id" => 4069,
                            "uuid" => "07032d70-f4e1-4c5f-b8bc-124f5d3ea5b5",
                            "dates" => [
                                "date" => "13. 9. 1933",
                                "date_range" => "21. 3. 1939",
                            ],
                            "date_year" => 1933,
                            "date_month" => 9,
                            "date_day" => 13,
                            "date_marked" => "13.09.1933",
                            "date_uncertain" => 0,
                            "date_approximate" => 1,
                            "date_inferred" => 0,
                            "date_is_range" => 1,
                            "date_note" => "Live API smoke test",
                            "range_year" => 1939,
                            "range_month" => 3,
                            "range_day" => 21,
                            "authors" => [
                                [
                                    "id" => 2483,
                                    "scope" => "local",
                                    "reference" => "local-2483",
                                    "name" => "Local Person, Author",
                                    "marked" => "Author mark",
                                    "salutation" => null,
                                ],
                            ],
                            "author_inferred" => 0,
                            "author_uncertain" => 0,
                            "author_note" => "Author note",
                            "recipients" => [
                                [
                                    "id" => 18,
                                    "scope" => "global",
                                    "reference" => "global-18",
                                    "name" => "Global Person, Recipient",
                                    "marked" => "Recipient mark",
                                    "salutation" => "Dear recipient",
                                ],
                            ],
                            "recipient_inferred" => 1,
                            "recipient_uncertain" => 0,
                            "recipient_note" => "Recipient note",
                            "origins" => [
                                [
                                    "id" => 181,
                                    "scope" => "local",
                                    "reference" => "local-181",
                                    "name" => "Local origin",
                                    "marked" => "Origin mark",
                                    "salutation" => null,
                                ],
                            ],
                            "origin_inferred" => 1,
                            "origin_uncertain" => 0,
                            "origin_note" => "Origin note",
                            "destinations" => [
                                [
                                    "id" => 238,
                                    "scope" => "global",
                                    "reference" => "global-238",
                                    "name" => "Global destination",
                                    "marked" => "Destination mark",
                                    "salutation" => null,
                                ],
                            ],
                            "destination_inferred" => 0,
                            "destination_uncertain" => 1,
                            "destination_note" => "Destination note",
                            "mentioned" => [
                                [
                                    "id" => 2484,
                                    "scope" => "local",
                                    "reference" => "local-2484",
                                    "name" => "Local Person, Mentioned",
                                    "marked" => null,
                                    "salutation" => null,
                                ],
                                [
                                    "id" => 18,
                                    "scope" => "global",
                                    "reference" => "global-18",
                                    "name" => "Global Person, Mentioned",
                                    "marked" => null,
                                    "salutation" => null,
                                ],
                            ],
                            "people_mentioned_note" => "Mentioned note",
                            "keywords" => [
                                [
                                    "id" => 74,
                                    "scope" => "local",
                                    "reference" => "local-74",
                                    "name_cs" => "Mistni klicove slovo",
                                    "name_en" => "Local keyword",
                                    "type" => "L.",
                                ],
                                [
                                    "id" => 10442,
                                    "scope" => "global",
                                    "reference" => "global-10442",
                                    "name_cs" => "Global klicove slovo",
                                    "name_en" => "Global keyword",
                                    "type" => "G.",
                                ],
                            ],
                            "copies" => [
                                [
                                    "repository" => [
                                        "id" => 30,
                                        "scope" => "local",
                                        "reference" => "local-30",
                                        "value" => "local-30",
                                        "label" => "Repository (Lokální)",
                                    ],
                                    "archive" => [
                                        "id" => 12,
                                        "scope" => "global",
                                        "reference" => "global-12",
                                        "value" => "global-12",
                                        "label" => "Global Archive (Globální)",
                                    ],
                                ],
                            ],
                        ],
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Letter not found")
        ]
    )]
    public function show($id)
    {
        $letter = Letter::with([
                'identities',
                'globalIdentities',
                'authors',
                'recipients',
                'mentioned',
                'globalAuthors',
                'globalRecipients',
                'globalMentioned',
                'origins',
                'destinations',
                'globalOrigins',
                'globalDestinations',
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
            content: new OA\JsonContent(ref: "#/components/schemas/LetterUpsertRequest")
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
        unset($data['client_meta']);
        $copiesData = $data['copies'] ?? [];
        unset($data['copies']);

        $letter = Letter::create($data);

        $this->letterService->syncManifestations($letter, $copiesData);

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
        description: "Partial update semantics. Omitted fields remain unchanged, null clears nullable scalar fields, [] clears relation/list fields, and client-specific extra data belongs in client_meta.",
        tags: ["Letters"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: "#/components/schemas/LetterUpsertRequest",
                example: [
                    "origin_note" => null,
                    "recipients" => [],
                    "client_meta" => [
                        "external_id" => "client-letter-2457",
                        "sync_source" => "partner-app",
                    ],
                ]
            )
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
        unset($data['client_meta']);
        $copiesData = $data['copies'] ?? [];
        unset($data['copies']);

        $letter->update($data);

        if ($request->exists('copies')) {
            $this->letterService->syncManifestations($letter, $copiesData);
        }

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
        if ($request->exists('keywords') || $request->exists('local_keywords') || $request->exists('global_keywords')) {
            $keywordIds = $this->splitScopedIds($request->keywords ?? []);
            $letter->localKeywords()->sync($keywordIds['local']);
            $letter->globalKeywords()->sync($keywordIds['global']);
        }

        if ($request->exists('authors')) {
            $this->detachIdentityRole($letter, 'author');
            $authorData = $this->prepareIdentityAttachmentData($request->authors, 'author');
            $letter->identities()->attach($authorData['local']);
            $letter->globalIdentities()->attach($authorData['global']);
        }

        if ($request->exists('recipients')) {
            $this->detachIdentityRole($letter, 'recipient');
            $recipientData = $this->prepareIdentityAttachmentData($request->recipients, 'recipient', ['salutation']);
            $letter->identities()->attach($recipientData['local']);
            $letter->globalIdentities()->attach($recipientData['global']);
        }

        if ($request->exists('mentioned')) {
            $this->detachIdentityRole($letter, 'mentioned');
            $mentionedData = $this->prepareMentionedIdentityAttachmentData($request->mentioned);
            $letter->identities()->attach($mentionedData['local']);
            $letter->globalIdentities()->attach($mentionedData['global']);
        }

        if ($request->exists('origins') || $request->exists('local_origins') || $request->exists('global_origins')) {
            $this->detachPlaceRole($letter, 'origin');
            $this->attachScopedPlacesToLetter($letter, $request->origins, 'origin');
        }

        if ($request->exists('destinations') || $request->exists('local_destinations') || $request->exists('global_destinations')) {
            $this->detachPlaceRole($letter, 'destination');
            $this->attachScopedPlacesToLetter($letter, $request->destinations, 'destination');
        }
    }

    protected function detachIdentityRole(Letter $letter, string $role): void
    {
        $table = tenancy()->tenant->table_prefix . '__identity_letter';

        DB::table($table)
            ->where('letter_id', $letter->id)
            ->where('role', $role)
            ->delete();
    }

    protected function detachPlaceRole(Letter $letter, string $role): void
    {
        $table = tenancy()->tenant->table_prefix . '__letter_place';

        DB::table($table)
            ->where('letter_id', $letter->id)
            ->where('role', $role)
            ->delete();
    }

    /**
     * @param array<int, array<string, mixed>>|null $items
     * @return array{local: array<int, array<string, mixed>>, global: array<int, array<string, mixed>>}
     */
    protected function prepareIdentityAttachmentData(?array $items, string $role, array $pivotFields = []): array
    {
        if (!$items) {
            return ['local' => [], 'global' => []];
        }

        $results = ['local' => [], 'global' => []];

        foreach ($items as $position => $item) {
            $rawId = $item['id'] ?? null;
            $parsed = $this->parseIdentityReference($rawId);
            if (!$parsed) {
                Log::warning("Invalid pivot data for role '{$role}' at position {$position}.", ['id' => $rawId]);
                continue;
            }

            $data = [
                'position' => $position,
                'role' => $role,
                'marked' => $item['marked'] ?? null,
            ];

            foreach ($pivotFields as $field) {
                $data[$field] = $item[$field] ?? null;
            }

            $results[$parsed['scope']][$parsed['id']] = $data;
        }

        return $results;
    }

    /**
     * @param array<int, mixed>|null $items
     * @return array{local: array<int, array<string, mixed>>, global: array<int, array<string, mixed>>}
     */
    protected function prepareMentionedIdentityAttachmentData(?array $items): array
    {
        if (!$items) {
            return ['local' => [], 'global' => []];
        }

        $results = ['local' => [], 'global' => []];

        foreach ($items as $position => $item) {
            $rawId = is_array($item) ? ($item['id'] ?? null) : $item;
            $parsed = $this->parseIdentityReference($rawId);
            if (!$parsed) {
                Log::warning('Invalid mentioned ID.', ['id' => $rawId]);
                continue;
            }

            $results[$parsed['scope']][$parsed['id']] = [
                'position' => $position,
                'role' => 'mentioned',
            ];
        }

        return $results;
    }

    /**
     * @param mixed $rawId
     * @return array{scope: 'local'|'global', id: int}|null
     */
    protected function parseIdentityReference(mixed $rawId): ?array
    {
        if (is_int($rawId)) {
            return ['scope' => 'local', 'id' => $rawId];
        }

        if (is_string($rawId) && ctype_digit($rawId)) {
            return ['scope' => 'local', 'id' => (int) $rawId];
        }

        if (is_string($rawId) && preg_match('/^(local|global)-(\d+)$/', $rawId, $matches)) {
            return [
                'scope' => $matches[1],
                'id' => (int) $matches[2],
            ];
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{local: array<int>, global: array<int>}
     */
    protected function splitScopedIds(array $items): array
    {
        $results = ['local' => [], 'global' => []];

        foreach ($items as $item) {
            $rawId = is_array($item) ? ($item['id'] ?? null) : $item;

            if (!is_string($rawId) && !is_int($rawId)) {
                continue;
            }

            if (is_int($rawId) || (is_string($rawId) && ctype_digit($rawId))) {
                $results['local'][] = (int) $rawId;
                continue;
            }

            if (preg_match('/^(local|global)-(\d+)$/', (string) $rawId, $matches)) {
                $results[$matches[1]][] = (int) $matches[2];
            }
        }

        return [
            'local' => array_values(array_unique($results['local'])),
            'global' => array_values(array_unique($results['global'])),
        ];
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

    /**
     * @param array<int, array<string, mixed>>|null $places
     */
    protected function attachScopedPlacesToLetter(Letter $letter, ?array $places, string $role): void
    {
        if (!$places) {
            return;
        }

        $local = [];
        $global = [];

        foreach ($places as $position => $item) {
            $rawId = $item['id'] ?? null;

            if (!is_string($rawId) && !is_int($rawId)) {
                Log::warning("Invalid place data for role '{$role}' at position {$position}.", ['id' => $rawId]);
                continue;
            }

            if (is_int($rawId) || (is_string($rawId) && ctype_digit($rawId))) {
                $local[(int) $rawId] = [
                    'position' => $position,
                    'role' => $role,
                    'marked' => $item['marked'] ?? null,
                ];
                continue;
            }

            if (!preg_match('/^(local|global)-(\d+)$/', (string) $rawId, $matches)) {
                Log::warning("Invalid place reference for role '{$role}' at position {$position}.", ['id' => $rawId]);
                continue;
            }

            $target = $matches[1] === 'global' ? 'global' : 'local';
            ${$target}[(int) $matches[2]] = [
                'position' => $position,
                'role' => $role,
                'marked' => $item['marked'] ?? null,
            ];
        }

        if ($local !== []) {
            $letter->localPlaces()->attach($local);
        }

        if ($global !== []) {
            $letter->globalPlaces()->attach($global);
        }
    }
}
