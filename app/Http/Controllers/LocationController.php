<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Exports\LocationsExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LocationController extends Controller
{
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(Location::types())],
        ];
    }

    public function index()
    {
        return view('pages.locations.index', [
            'title' => __('hiko.locations'),
            'labels' => Location::types(),
        ]);
    }

    public function create()
    {
        return view('pages.locations.form', [
            'title' => __('hiko.new_location'),
            'location' => new Location,
            'action' => route('locations.store'),
            'label' => __('hiko.create'),
            'types' => Location::types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim($request->input('name')),
            'type' => trim($request->input('type')),
        ]);

        $validated = $request->validate($this->rules());

        $exists = Location::whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
                        ->where('type', $validated['type'])
                        ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withErrors(['name' => __('hiko.entity_already_exists')])
                ->withInput();
        }

        $location = Location::create($validated);

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
            'types' => Location::types(),
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $request->merge([
            'name' => trim($request->input('name')),
            'type' => trim($request->input('type')),
        ]);

        $validated = $request->validate($this->rules());

        $name = $validated['name'] ?? $location->name;
        $type = $validated['type'] ?? $location->type;

        $exists = Location::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                        ->where('type', $type)
                        ->where('id', '!=', $location->id)
                        ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withErrors(['name' => __('hiko.entity_already_exists')])
                ->withInput();
        }

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
