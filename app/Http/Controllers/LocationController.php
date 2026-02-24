<?php

namespace App\Http\Controllers;

use App\Exports\LocationsExport;
use App\Models\Location;
use App\Models\GlobalLocation;
use App\Models\Manifestation;
use App\Services\PageLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        // Fetch all manifestations that use this location
        $manifestations = Manifestation::with('letter')
            ->where('repository_id', $location->id)
            ->orWhere('archive_id', $location->id)
            ->orWhere('collection_id', $location->id)
            ->get();

        // Extract unique letters
        $letters = $manifestations->pluck('letter')->filter()->unique('id');

        return view('pages.locations.form', [
            'title' => __('hiko.location') . ': '. $location->id,
            'location' => $location,
            'action' => route('locations.update', $location),
            'method' => 'PUT',
            'label' => __('hiko.edit'),
            'types' => Location::types(),
            'letters' => $letters,
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'tenant',
            'resource_type' => 'location_edit',
            'resource_id' => (string) $location->id,
        ], $request->user());

        if (!$lock['ok']) {
            return redirect()
                ->route('locations')
                ->with('success', __('hiko.page_lock_not_owned'))
                ->with('success_sticky', true);
        }

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
        return $this->searchLocations($request, 'repository');
    }

    public function searchArchive(Request $request)
    {
        return $this->searchLocations($request, 'archive');
    }

    public function searchCollection(Request $request)
    {
        return $this->searchLocations($request, 'collection');
    }

    protected function searchLocations(Request $request, string $type)
    {
        $search = $request->input('search');

        // Local Locations
        $local = Location::where('type', $type)
            ->where('name', 'like', '%' . $search . '%')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($loc) => [
                'id' => 'local-' . $loc->id,
                'value' => 'local-' . $loc->id,
                'label' => $loc->name . ' (' . __('hiko.local') . ')',
            ]);

        // Global Locations
        $global = GlobalLocation::where('type', $type)
            ->where('name', 'like', '%' . $search . '%')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($loc) => [
                'id' => 'global-' . $loc->id,
                'value' => 'global-' . $loc->id,
                'label' => $loc->name . ' (' . __('hiko.global') . ')',
            ]);

        return response()->json($local->merge($global));
    }

    public function validation()
    {
        return view('pages.locations.validation', [
            'title' => __('hiko.input_control'),
        ]);
    }

    public function localMerge()
    {
        return view('pages.locations.local-merge', [
            'title' => __('hiko.local_location_merging'),
        ]);
    }
}
