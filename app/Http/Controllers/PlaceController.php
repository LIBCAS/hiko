<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Country;
use Illuminate\Http\Request;

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
            'title' => __('Místa'),
        ]);
    }

    public function create()
    {
        return view('pages.places.form', [
            'title' => __('Nové místo'),
            'place' => new Place(),
            'action' => route('places.store'),
            'label' => __('Vytvořit'),
            'countries' => Country::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules);

        $place = Place::create([
            'name' => $validated['name'],
            'country' => $validated['country'],
            'note' => $validated['note'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'geoname_id' => $validated['geoname_id'],
        ]);

        return redirect()->route('places.edit', $place->id)->with('success', __('Uloženo.'));
    }

    public function edit(Place $place)
    {
        return view('pages.places.form', [
            'title' => __('Místo: '),
            'place' => $place,
            'method' => 'PUT',
            'action' => route('places.update', $place),
            'label' => __('Upravit'),
            'countries' => Country::all(),
        ]);
    }

    public function update(Request $request, Place $place)
    {
        $validated = $request->validate($this->rules);

        $place->update([
            'name' => $validated['name'],
            'country' => $validated['country'],
            'note' => $validated['note'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'geoname_id' => $validated['geoname_id'],
        ]);

        return redirect()->route('places.edit', $place->id)->with('success', __('Uloženo.'));
    }

    public function destroy(Place $place)
    {
        $place->delete();

        return redirect()->route('places')->with('success', 'Odstraněno');
    }
}
