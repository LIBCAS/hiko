<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalPlaceMergeRequest;
use App\Services\GlobalPlaceMergeService;
use App\Services\PageLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalPlaceMergeController extends Controller
{
    public function __construct(
        protected GlobalPlaceMergeService $mergeService
    ) {}

    /**
     * Show the merge configuration and preview page.
     */
    public function index(Request $request): View
    {
        // Get criteria from query params, default from config
        $criteria = $request->input('criteria', config('global_place_merge.default_criteria'));

        // Ensure criteria is an array
        if (!is_array($criteria)) {
            $criteria = [$criteria];
        }

        return view('pages.places.global-merge', [
            'title' => __('hiko.global_place_merging'),
            'criteria' => $criteria,
            'nameSimilarityThreshold' => $request->input('name_similarity_threshold', config('global_place_merge.name_similarity_threshold')),
            'latitudeTolerance' => $request->input('latitude_tolerance', config('global_place_merge.latitude_tolerance')),
            'longitudeTolerance' => $request->input('longitude_tolerance', config('global_place_merge.longitude_tolerance')),
            'countryAndNameThreshold' => $request->input('country_and_name_threshold', config('global_place_merge.country_and_name_threshold')),
        ]);
    }

    /**
     * Execute the merge based on form submission.
     */
    public function execute(GlobalPlaceMergeRequest $request): RedirectResponse
    {
        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'global',
            'resource_type' => 'place_global_merge',
        ], $request->user());

        if (!$lock['ok']) {
            return redirect()
                ->route('places')
                ->with('success', __('hiko.page_lock_not_owned'))
                ->with('success_sticky', true);
        }

        try {
            $result = $this->mergeService->executeMerge(
                $request->validated('selected_places'),
                $request->validated('criteria'),
                [
                    'name_similarity_threshold' => !is_null($request->validated('name_similarity_threshold'))
                        ? (int)$request->validated('name_similarity_threshold')
                        : config('global_place_merge.name_similarity_threshold'),

                    'latitude_tolerance' => !is_null($request->validated('latitude_tolerance'))
                        ? (float)$request->validated('latitude_tolerance')
                        : config('global_place_merge.latitude_tolerance'),

                    'longitude_tolerance' => !is_null($request->validated('longitude_tolerance'))
                        ? (float)$request->validated('longitude_tolerance')
                        : config('global_place_merge.longitude_tolerance'),

                    'country_and_name_threshold' => !is_null($request->validated('country_and_name_threshold'))
                        ? (int)$request->validated('country_and_name_threshold')
                        : config('global_place_merge.country_and_name_threshold'),
                ],
                $request->input('merge_attrs', [])
            );

            if ($result['success']) {
                $message = __('hiko.places_merge_success', [
                    'merged' => $result['merged'],
                    'created' => $result['created'],
                ]);

                if ($result['skipped'] > 0) {
                    $message .= ' ' . __('hiko.places_merge_skipped', ['skipped' => $result['skipped']]);
                }

                return redirect()
                    ->route('places')
                    ->with('success', $message)
                    ->with('success_sticky', true);
            }

            return redirect()
                ->route('places.global-merge')
                ->with('error', __('hiko.places_merge_error'));

        } catch (\Exception $e) {
            \Log::error('Global place merge error: ' . $e->getMessage(), [
                'exception' => $e,
                'selected_places' => $request->validated('selected_places'),
            ]);

            return redirect()
                ->route('places.global-merge')
                ->with('error', __('hiko.places_merge_error') . ': ' . $e->getMessage());
        }
    }
}
