<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Exports\LocationsExport;
use Maatwebsite\Excel\Facades\Excel;

class LocationController extends Controller
{
    protected $rules = [
        'name' => ['required', 'string', 'max:255'],
        'type' => ['required', 'string'],
    ];

    public function index()
    {
        return view('pages.locations.index', [
            'title' => __('hiko.locations'),
            'labels' => $this->getTypes(),
        ]);
    }

    public function create()
    {
        return view('pages.locations.form', [
            'title' => __('hiko.new_location'),
            'location' => new Location,
            'action' => route('locations.store'),
            'label' => __('hiko.create'),
            'types' => $this->getTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $location = Location::create($request->validate($this->rules));

        return redirect()
            ->route('locations.edit', $location->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Location $location)
    {
        return view('pages.locations.form', [
            'title' => __('hiko.location') . ': '. $location->id,
            'location' => $location,
            'action' => route('locations.update', $location),
            'method' => 'PUT',
            'label' => __('hiko.edit'),
            'types' => $this->getTypes(),
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $location->update($request->validate($this->rules));

        return redirect()
            ->route('locations.edit', $location->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()
            ->route('locations')
            ->with('success', __('hiko.removed'));
    }

    public function export()
    {
        return Excel::download(new LocationsExport, 'locations.xlsx');
    }

    protected function getTypes()
    {
        return ['repository', 'collection', 'archive'];
    }
}
