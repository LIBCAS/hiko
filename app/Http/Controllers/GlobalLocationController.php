<?php

namespace App\Http\Controllers;

use App\Models\GlobalLocation;
use App\Models\Manifestation;
use App\Services\PageLockService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Enums\LocationType;

class GlobalLocationController extends Controller
{
    public function create()
    {
        return view('pages.global-locations.form', [
            'title' => __('hiko.new_global_location'),
            'location' => new GlobalLocation,
            'action' => route('global.locations.store'),
            'label' => __('hiko.create'),
            'types' => LocationType::values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(LocationType::values())],
        ]);

        $exists = GlobalLocation::where('name', $validated['name'])
            ->where('type', $validated['type'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => __('hiko.entity_already_exists')])->withInput();
        }

        $location = GlobalLocation::create($validated);

        return redirect()
            ->route('global.locations.edit', $location->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(GlobalLocation $globalLocation)
    {
        $letters = collect();

        if (function_exists('tenancy') && tenancy()->initialized) {
            $manifestations = Manifestation::with('letter')
                ->where('global_repository_id', $globalLocation->id)
                ->orWhere('global_archive_id', $globalLocation->id)
                ->orWhere('global_collection_id', $globalLocation->id)
                ->get();

            $letters = $manifestations->pluck('letter')->filter()->unique('id');
        }

        return view('pages.global-locations.form', [
            'title' => __('hiko.global_location') . ': ' . $globalLocation->name,
            'location' => $globalLocation,
            'action' => route('global.locations.update', $globalLocation->id),
            'method' => 'PUT',
            'label' => __('hiko.edit'),
            'types' => LocationType::values(),
            'letters' => $letters,
        ]);
    }

    public function update(Request $request, GlobalLocation $globalLocation)
    {
        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'global',
            'resource_type' => 'global_location_edit',
            'resource_id' => (string) $globalLocation->id,
        ], $request->user());

        if (!$lock['ok']) {
            return redirect()
                ->route('locations')
                ->with('success', __('hiko.page_lock_not_owned'))
                ->with('success_sticky', true);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(LocationType::values())],
        ]);

        $exists = GlobalLocation::where('name', $validated['name'])
            ->where('type', $validated['type'])
            ->where('id', '!=', $globalLocation->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => __('hiko.entity_already_exists')])->withInput();
        }

        $globalLocation->update($validated);

        return redirect()
            ->route('global.locations.edit', $globalLocation->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(GlobalLocation $globalLocation)
    {
        $globalLocation->delete();
        return redirect()->route('locations')->with('success', __('hiko.removed'));
    }
}
