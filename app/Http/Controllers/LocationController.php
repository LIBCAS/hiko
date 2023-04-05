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
            'labels' => $this->getTypes(),
        ]);
    }

    public function create(): View
    {
        return view('pages.locations.form', [
            'title' => __('hiko.new_location'),
            'location' => new Location,
            'action' => route('locations.store'),
            'label' => __('hiko.create'),
            'types' => $this->getTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'locations.create' : 'locations.edit';

        $location = Location::create($request->validate($this->rules));

        return redirect()
            ->route($redirectRoute, $location->id)
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
            'types' => $this->getTypes(),
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'locations.create' : 'locations.edit';

        $location->update($request->validate($this->rules));

        return redirect()
            ->route($redirectRoute, $location->id)
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

    protected function getTypes(): array
    {
        return ['repository', 'collection', 'archive'];
    }
}
