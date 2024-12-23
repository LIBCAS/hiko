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
     * @param  Request  $request
     * @return LetterCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request): LetterCollection|\Illuminate\Http\JsonResponse
    {
        try {
            $letters = Letter::with($this->relationships())
                ->published()
                ->filter($request->all())
                ->orderByDate($request->input('order', 'asc'))
                ->paginate($this->limit($request));

            return new LetterCollection($letters);
        } catch (\Exception $e) {
            Log::error('Error fetching letters: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'An error occurred while fetching letters.'
            ], 500);
        }
    }

    /**
     * Display a specific published letter by UUID.
     *
     * @param  string  $uuid
     * @return LetterResource|\Illuminate\Http\JsonResponse
     */
    public function show(string $uuid): LetterResource|\Illuminate\Http\JsonResponse
    {
        try {
            $letter = Letter::where('uuid', $uuid)
                ->published()
                ->with($this->relationships())
                ->firstOrFail();

            return new LetterResource($letter);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Letter not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching letter: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'An error occurred while fetching the letter.'
            ], 500);
        }
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
            'keywords:id,name', // Select only necessary columns
        ];
    }

    /**
     * Determine the pagination limit.
     *
     * @param  Request  $request
     * @return int
     */
    protected function limit(Request $request): int
    {
        $limit = (int) $request->input('limit', 10);
        return ($limit > 0 && $limit <= 100) ? $limit : 10;
    }
}
