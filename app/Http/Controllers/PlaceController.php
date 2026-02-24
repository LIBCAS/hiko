<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceRequest;
use App\Models\Place;
use App\Models\Country;
use App\Services\PageLockService;
use App\Services\PlaceService;
use App\Services\PlaceMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Exports\PlacesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PlaceController extends Controller
{
    protected PlaceService $placeService;

    public function __construct(PlaceService $placeService)
    {
        $this->placeService = $placeService;
    }

    public function index()
    {
        return view('pages.places.index', [
            'title' => __('hiko.places'),
        ]);
    }

    public function create()
    {
        return view('pages.places.form', [
            'title' => __('hiko.new_place'),
            'place' => new Place,
            'action' => route('places.store'),
            'label' => __('hiko.create'),
            'countries' => Country::all(),
        ]);
    }

    public function store(PlaceRequest $request): RedirectResponse
    {
        Log::info('Store method called with request data:', $request->all());

        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return redirect()
                ->back()
                ->withErrors(['name' => __('hiko.entity_already_exists')])
                ->withInput();
        }

        Log::info('Validation passed. Data:', $validated);

        $place = $this->placeService->create($validated);

        Log::info('Place created successfully with ID:', ['place_id' => $place->id]);
        Log::info('Final stored alternative names:', ['alternative_names' => $place->alternative_names]);

        return redirect()
            ->route('places.edit', $place->id)
            ->with('success', __('hiko.saved'));
    }

    public function update(PlaceRequest $request, Place $place): RedirectResponse
    {
        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'tenant',
            'resource_type' => 'place_edit',
            'resource_id' => (string) $place->id,
        ], $request->user());

        if (!$lock['ok']) {
            return redirect()
                ->route('places')
                ->with('success', __('hiko.page_lock_not_owned'))
                ->with('success_sticky', true);
        }

        Log::info('Update method called for Place ID:', ['place_id' => $place->id]);

        $validated = $request->validated();

        if ($request->failsDuplicateCheck($place->id)) {
            return redirect()
                ->back()
                ->withErrors(['name' => __('hiko.entity_already_exists')])
                ->withInput();
        }

        Log::info('Validation passed. Data:', $validated);

        $this->placeService->update($place, $validated);

        Log::info('Place updated successfully with ID:', ['place_id' => $place->id]);

        return redirect()
            ->route('places.edit', $place->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Place $place)
    {
        return view('pages.places.form', [
            'title' => __('hiko.place') . ': ' . $place->id,
            'place' => $place,
            'method' => 'PUT',
            'action' => route('places.update', $place),
            'label' => __('hiko.edit'),
            'countries' => Country::all(),
        ]);
    }

    public function destroy(Place $place): RedirectResponse
    {
        $place->delete();
        Log::info('Place deleted successfully with ID:', ['place_id' => $place->id]);

        return redirect()
            ->route('places')
            ->with('success', __('hiko.removed'));
    }

    public function export()
    {
        return Excel::download(new PlacesExport, 'places.xlsx');
    }

    public function validation()
    {
        return view('pages.places.validation', [
            'title' => __('hiko.input_control'),
        ]);
    }

    public function localMerge()
    {
        return view('pages.places.local-merge', [
            'title' => __('hiko.local_place_merging'),
        ]);
    }

    /**
     * Merge local places into global places.
     * Only accessible to users with 'manage-metadata' ability.
     *
     * @return JsonResponse
     */
    public function merge(): JsonResponse
    {
        try {
            $mergeService = app(PlaceMergeService::class);
            $result = $mergeService->mergeLocalPlacesToGlobal();

            if ($result['success']) {
                $message = __('hiko.places_merge_success', [
                    'merged' => $result['merged'],
                    'created' => $result['created'],
                ]);

                if ($result['skipped'] > 0) {
                    $message .= ' ' . __('hiko.places_merge_skipped', ['skipped' => $result['skipped']]);
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'stats' => [
                        'merged' => $result['merged'],
                        'created' => $result['created'],
                        'skipped' => $result['skipped'],
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('hiko.places_merge_error') . ': ' . $result['error'],
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('[PlaceMerge] Controller error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('hiko.places_merge_error') . ': ' . $e->getMessage(),
            ], 500);
        }
    }
}
