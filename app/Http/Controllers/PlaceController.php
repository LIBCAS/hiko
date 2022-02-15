<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Country;
use Illuminate\Http\Request;
use App\Exports\PlacesExport;
use Maatwebsite\Excel\Facades\Excel;

class PlaceController extends Controller
{
    protected $rules = [
        'name' => ['required', 'string', 'max:255'],
        'country' => ['required', 'string', 'max:255'],
        'note' => ['nullable'],
        'latitude' => ['nullable', 'numeric'],
        'longitude' => ['nullable', 'numeric'],
        'geoname_id' => ['nullable', 'integer', 'numeric'],
    ];

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

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules);

        $place = Place::create($validated);

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

    public function update(Request $request, Place $place)
    {
        $validated = $request->validate($this->rules);

        $place->update($validated);

        return redirect()
            ->route('places.edit', $place->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Place $place)
    {
        $place->delete();

        return redirect()
            ->route('places')
            ->with('success', __('hiko.removed'));
    }

    public function export()
    {
        return Excel::download(new PlacesExport, 'places.xlsx');
    }
}
