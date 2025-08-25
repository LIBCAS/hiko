<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v2\LetterRequest;
use App\Jobs\LetterSaved;
use App\Jobs\RegenerateNames;
use App\Models\Letter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Symfony\Component\HttpFoundation\Response;

class LetterController extends Controller
{
    public static $maxPerPage = 500;    // Maximum number of items per page
    public static $defaultPerPage = 100; // Default number of items per page

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', self::$defaultPerPage);
        $page = (int) $request->query('page', 1);

        $perPage = max(1, min(self::$maxPerPage, $perPage));
        $page = max(1, $page);

        $includes = $request->query('include') ? explode(',', $request->query('include')) : [];

        Log::info('API V2: Fetching letters', [
            'per_page' => $perPage,
            'page' => $page,
            'includes' => $includes,
            'filters' => $request->query('filter', []),
        ]);

        $letters = QueryBuilder::for(Letter::class)
            ->allowedIncludes([
                'identities',
                'identities.localProfessions',
                'identities.localProfessions.profession_category',
                'identities.globalProfessions',
                'identities.globalProfessions.profession_category',
                'places',
                'keywords',
                'globalKeywords',
                'media',
                'users',
            ])
            ->tap(function ($query) use ($request) {
                $query->filter($request->query('filter', []));  // Use the existing `filter()` logic in LetterBuilder
            })
            ->defaultSort('-created_at')
            ->allowedSorts(['created_at', 'updated_at', 'date_computed', 'date_year', 'status'])
            ->with($includes)
            ->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->query());

        return response()->json($letters);
    }

    public function show($id)
    {
        $letter = Letter::with([
                'identities',
                'identities.localProfessions',
                'identities.localProfessions.profession_category',
                'identities.globalProfessions',
                'identities.globalProfessions.profession_category',
                'places',
                'keywords',
                'globalKeywords',
                'media',
                'users',
            ])
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($letter);
    }

    public function store(LetterRequest $request)
    {
        $letter = Letter::create($request->validated());

        $this->attachRelated($request, $letter);

        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        Log::info('API V2: Letter created', ['letter_id' => $letter->id]);

        return response()->json($letter, Response::HTTP_CREATED);
    }

    public function update(LetterRequest $request, $id)
    {
        $letter = Letter::findOrFail($id);

        $letter->update($request->validated());

        $this->attachRelated($request, $letter);

        LetterSaved::dispatch($letter);
        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        Log::info('API V2: Letter updated', ['letter_id' => $letter->id]);

        return response()->json($letter);
    }

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
        $letter->places()->detach();

        $letter->identities()->attach($this->prepareAttachmentData($request->authors, 'author'));
        $letter->identities()->attach($this->prepareAttachmentData($request->recipients, 'recipient', ['salutation']));
        $letter->places()->attach($this->prepareAttachmentData($request->origins, 'origin'));
        $letter->places()->attach($this->prepareAttachmentData($request->destinations, 'destination'));

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
}
