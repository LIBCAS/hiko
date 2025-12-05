<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalPlaceRequest;
use App\Models\GlobalPlace;
use App\Models\Country;
use App\Services\GlobalPlaceService;
use App\Services\PlaceMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Exports\PlacesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GlobalPlaceController extends Controller
{
    protected GlobalPlaceService $placeService;

    public function __construct(GlobalPlaceService $placeService)
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
        return view('pages.global-places.form', [
            'title' => __('hiko.new_global_place'),
            'place' => new GlobalPlace,
            'action' => route('global.places.store'),
            'label' => __('hiko.create'),
            'countries' => Country::all(),
        ]);
    }

    public function store(GlobalPlaceRequest $request): RedirectResponse
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
            ->route('global.places.edit', $place->id)
            ->with('success', __('hiko.saved'));
    }

    public function update(GlobalPlaceRequest $request, GlobalPlace $place): RedirectResponse
    {
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
            ->route('global.places.edit', $place->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(GlobalPlace $place)
    {
        return view('pages.global-places.form', [
            'title' => __('hiko.global_place') . ': ' . $place->id,
            'place' => $place,
            'method' => 'PUT',
            'action' => route('global.places.update', $place),
            'label' => __('hiko.edit'),
            'countries' => Country::all(),
        ]);
    }

    public function destroy(GlobalPlace $place): RedirectResponse
    {
        $place->delete();
        Log::info('Global place deleted successfully with ID:', ['place_id' => $place->id]);

        return redirect()
            ->route('places')
            ->with('success', __('hiko.removed'));
    }
}
