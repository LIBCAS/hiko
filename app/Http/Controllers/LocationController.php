<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Exports\LocationsExport;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LocationController extends Controller
{
    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'type' => ['required', 'string'],
    ];

    public function index(): View
    {
        return view('pages.locations.index', [
            'title' => __('hiko.locations'),
            'labels' => ['repository', 'collection', 'archive'],
        ]);
    }

    public function create(): View
    {
        return view('pages.locations.form', [
            'title' => __('hiko.new_location'),
            'location' => new Location,
            'action' => route('locations.store'),
            'label' => __('hiko.create'),
            'types' => ['repository', 'collection', 'archive'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $location = Location::create($validated);

        return redirect()
            ->route('locations.edit', $location->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Location $location): View
    {
        return view('pages.locations.form', [
            'title' => __('hiko.location') . ': '. $location->id,
            'location' => $location,
            'action' => route('locations.update', $location),
            'method' => 'PUT',
            'label' => __('hiko.edit'),
            'types' => ['repository', 'collection', 'archive'],
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $location->update($validated);

        return redirect()
            ->route('locations.edit', $location->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return redirect()
            ->route('locations')
            ->with('success', __('hiko.removed'));
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new LocationsExport, 'locations.xlsx');
    }

    public function searchRepository(Request $request)
    {
        $search = $request->input('search');

        $locations = Location::where('type', 'repository')
                             ->where('name', 'like', '%' . $search . '%')
                             ->orderBy('name')
                             ->limit(10)
                             ->get(['id', 'name as label', 'name as value']);

        return response()->json($locations);
    }

    public function searchArchive(Request $request)
    {
        $search = $request->input('search');

        $locations = Location::where('type', 'archive')
                             ->where('name', 'like', '%' . $search . '%')
                             ->orderBy('name')
                             ->limit(10)
                             ->get(['id', 'name as label', 'name as value']);

        return response()->json($locations);
    }

    public function searchCollection(Request $request)
    {
        $search = $request->input('search');

        $locations = Location::where('type', 'collection')
                             ->where('name', 'like', '%' . $search . '%')
                             ->orderBy('name')
                             ->limit(10)
                             ->get(['id', 'name as label', 'name as value']);

        return response()->json($locations);
    }
}
