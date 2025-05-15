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
            $lettersQuery = Letter::with($this->relationships())
                ->published()
                ->filter($request->all())
                ->orderByDate($request->input('order', 'asc'));

            $letters = $lettersQuery->paginate($this->limit($request));

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

    public function media($uuid)
    {
        $letter = Letter::where('uuid', $uuid)->published()->first();

        if (!$letter) {
            abort(404);
        }

        $mediaQuery = $letter->media();
        $media = $mediaQuery->get(['id', 'file_name', 'mime_type', 'disk', 'size']);

        return response()->json(
            $media->map(function ($item) {
                return [
                    'id' => $item->id,
                    'file_name' => $item->file_name,
                    'mime_type' => $item->mime_type,
                    'disk' => $item->disk,
                    'size' => $item->size,
                    'url' => $item->getUrl(),
                    'preview_url' => $item->getUrl('preview'),
                ];
            })->values()
        );
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
                $identityTable = tenancy()->tenant->table_prefix . '__identities';
                $query->select("{$identityTable}.id", "{$identityTable}.name")
                    ->wherePivotIn('role', ['author', 'recipient'])
                    ->orderBy('pivot_position');
            },
            'places' => function ($query) {
                $placeTable = tenancy()->tenant->table_prefix . '__places';
                $query->select("{$placeTable}.id", "{$placeTable}.name")
                    ->wherePivotIn('role', ['origin', 'destination'])
                    ->orderBy('pivot_position');
            },
            'localKeywords:id,name',
            'globalKeywords:id,name',
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
