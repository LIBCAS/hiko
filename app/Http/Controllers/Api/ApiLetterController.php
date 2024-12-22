<?php

namespace App\Http\Controllers\Api;

use App\Models\Letter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\LetterResource;
use App\Http\Resources\LetterCollection;
use Illuminate\Support\Facades\Log;

class ApiLetterController extends Controller
{
    /**
     * Display a paginated list of published letters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\LetterCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request): LetterCollection|\Illuminate\Http\JsonResponse
    {
        try {
            $lettersQuery = $this->prepareQuery($request);
            $limit = $this->limit($request);

            $letters = $lettersQuery->paginate($limit);

            return new LetterCollection($letters);
        } catch (\Exception $e) {
            Log::error('Error fetching letters: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while fetching letters.'
            ], 500);
        }
    }

    /**
     * Display a specific published letter by UUID.
     *
     * @param  string  $uuid
     * @return \App\Http\Resources\LetterResource|\Illuminate\Http\JsonResponse
     */
    public function show(string $uuid): LetterResource|\Illuminate\Http\JsonResponse
    {
        try {
            $letter = Letter::where('uuid', $uuid)
                ->where('status', 'publish')
                ->with($this->relationships())
                ->first();

            if (!$letter) {
                return response()->json([
                    'message' => 'Letter not found.'
                ], 404);
            }

            return new LetterResource($letter);
        } catch (\Exception $e) {
            Log::error('Error fetching letter: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while fetching the letter.'
            ], 500);
        }
    }

    /**
     * Prepare the query based on request parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function prepareQuery(Request $request)
    {
        $query = Letter::with($this->relationships())
            ->where('status', 'publish');

        // Apply filters based on roles and relationships
        $roles = ['author', 'recipient', 'origin', 'destination'];
        foreach ($roles as $role) {
            $type = in_array($role, ['author', 'recipient']) ? 'identities' : 'places';
            $query = $this->addScopeByRole($query, $request, $role, $type);
        }

        // Filter by keywords
        if ($request->filled('keyword')) {
            $keywordIds = array_filter(array_map('intval', explode(',', $request->input('keyword'))));
            if (!empty($keywordIds)) {
                $query->whereHas('keywords', function ($subquery) use ($keywordIds) {
                    $subquery->whereIn('keywords.id', $keywordIds);
                });
            }
        }

        // Filter by date range
        if ($request->filled('after')) {
            $query->where('date_computed', '>=', $request->input('after'));
        }

        if ($request->filled('before')) {
            $query->where('date_computed', '<=', $request->input('before'));
        }

        // Filter by content
        if ($request->filled('content')) {
            $query->where('content', 'LIKE', '%' . $request->input('content') . '%');
        }

        // Order the results
        $order = $this->order($request);
        $query->orderBy('date_computed', $order);

        return $query;
    }

    /**
     * Apply scope based on role and type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $role
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function addScopeByRole($query, Request $request, string $role, string $type)
    {
        if ($request->filled($role)) {
            $ids = array_filter(array_map('intval', explode(',', $request->input($role))));
            if (!empty($ids)) {
                $query->whereHas($type, function ($subquery) use ($ids, $role, $type) {
                    $subquery->where('role', $role)
                             ->whereIn("{$type}.id", $ids);
                });
            }
        }

        return $query;
    }

    /**
     * Define the relationships to load based on request parameters.
     *
     * @return array
     */
    protected function relationships(): array
    {
        return [
            'identities' => function ($query) {
                $query->select('identities.id', 'name', 'role')
                      ->whereIn('role', ['author', 'recipient'])
                      ->orderBy('position');
            },
            'places' => function ($query) {
                $query->select('places.id', 'name', 'role')
                      ->whereIn('role', ['origin', 'destination'])
                      ->orderBy('position');
            },
            'keywords' => function ($query) {
                $query->select('keywords.id', 'name');
            },
        ];
    }

    /**
     * Determine the pagination limit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function limit(Request $request): int
    {
        $limit = (int) $request->input('limit', 10);
        return ($limit > 0 && $limit <= 100) ? $limit : 10;
    }

    /**
     * Determine the order direction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function order(Request $request): string
    {
        $order = strtolower($request->input('order', 'asc'));
        return in_array($order, ['asc', 'desc']) ? $order : 'asc';
    }
}
