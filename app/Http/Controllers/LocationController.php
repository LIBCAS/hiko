<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected $rules = [
        'name' => ['required', 'string', 'max:255'],
        'type' => ['required', 'string'],
    ];

    public function index()
    {
        return view('pages.locations.index', [
            'title' => __('Uložení'),
            'labels' => $this->getTypes(),
        ]);
    }

    public function create()
    {
        return view('pages.locations.form', [
            'title' => __('Nové místo uložení'),
            'location' => new Location(),
            'action' => route('locations.store'),
            'label' => __('Vytvořit'),
            'types' => $this->getTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules);

        $location = Location::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);

        return redirect()->route('locations.edit', $location->id)->with('success', __('Uloženo.'));
    }

    public function edit(Location $location)
    {
        return view('pages.locations.form', [
            'title' => __('Místo uložení: ') . $location->name,
            'location' => $location,
            'action' => route('locations.update', $location),
            'method' => 'PUT',
            'label' => __('Upravit'),
            'types' => $this->getTypes(),
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate($this->rules);

        $location->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);

        return redirect()->route('locations.edit', $location->id)->with('success', __('Uloženo.'));
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()->route('locations')->with('success', 'Odstraněno');
    }

    protected function getTypes()
    {
        return [
            'repository' => __('Instituce / repozitáře'),
            'collection' => __('Sbírky / fondy'),
            'archive' => __('Archivy'),
        ];
    }
}
