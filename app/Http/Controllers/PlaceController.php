<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Country;
use Illuminate\Http\Request;
use App\Services\Geonames;
use App\Exports\PlacesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PlaceController extends Controller
{
    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'country' => ['required', 'string', 'max:255'],
        'division' => ['nullable', 'string'],
        'note' => ['nullable', 'string'],
        'latitude' => ['nullable', 'numeric'],
        'longitude' => ['nullable', 'numeric'],
        'geoname_id' => ['nullable', 'integer'],
    ];

    protected Geonames $geonames;

    public function __construct(Geonames $geonames)
    {
        $this->geonames = $geonames;
    }

    public function index(): View
    {
        return view('pages.places.index', [
            'title' => __('hiko.places'),
        ]);
    }

    public function create(): View
    {
        return view('pages.places.form', [
            'title' => __('hiko.new_place'),
            'place' => new Place,
            'action' => route('places.store'),
            'label' => __('hiko.create'),
            'countries' => Country::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Log::info('Store method called with request data:', $request->all());

        $validated = $request->validate($this->rules);
        Log::info('Validation passed. Data:', $validated);

        $alternativeNames = $this->fetchAlternativeNames($validated['geoname_id']);
        Log::info('Fetched alternative names:', ['alternative_names' => $alternativeNames]);

        $place = Place::create($validated);
        $place->alternative_names = $alternativeNames;
        $place->save();

        Log::info('Place created successfully with alternative names after save. ID:', ['place_id' => $place->id]);
        Log::info('Final stored alternative names:', ['alternative_names' => $place->alternative_names]);

        return redirect()
            ->route('places.edit', $place->id)
            ->with('success', __('hiko.saved'));
    }

    public function update(Request $request, Place $place): RedirectResponse
    {
        Log::info('Update method called for Place ID:', ['place_id' => $place->id]);

        $validated = $request->validate($this->rules);
        Log::info('Validation passed. Data:', $validated);

        $alternativeNames = $this->fetchAlternativeNames($validated['geoname_id']);
        Log::info('Fetched alternative names:', ['alternative_names' => $alternativeNames]);

        $place->update(array_merge($validated, ['alternative_names' => $alternativeNames]));

        Log::info('Place updated successfully with ID:', ['place_id' => $place->id]);

        return redirect()
            ->route('places.edit', $place->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Place $place): View
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


    protected function fetchAlternativeNames(?int $geonameId): array
    {
        if (!$geonameId) {
            Log::info('No geoname ID provided, skipping alternative names fetch.');
            return [];
        }

        $alternativeNames = $this->geonames->fetchAlternativeNames($geonameId);

        if (!is_array($alternativeNames)) {
            Log::error('Alternative names fetched are not an array:', ['alternative_names' => $alternativeNames]);
            return [];
        }
        return $alternativeNames;
    }
}