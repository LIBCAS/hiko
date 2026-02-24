<?php

namespace App\Http\Controllers;

use App\Models\GlobalIdentity;
use App\Http\Requests\GlobalIdentityRequest;
use App\Services\PageLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class GlobalIdentityController extends Controller
{
    public function create(): View
    {
        $identity = new GlobalIdentity();
        // Initialize arrays for Livewire components
        $identity->related_names = [];
        $identity->related_identity_resources = [];

        return view('pages.global-identities.form', [
            'title' => __('hiko.new_global_identity'),
            'method' => 'POST',
            'action' => route('global.identities.store'),
            'label' => __('hiko.create'),
            'identity' => $identity,
            'types' => GlobalIdentity::types(),
            'selectedType' => 'person',
            'selectedProfessions' => [],
            'selectedReligions' => [],
        ]);
    }

    public function store(GlobalIdentityRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $identityData = collect($validated)->except(['professions', 'religions'])->toArray();

        $identity = GlobalIdentity::create($identityData);
        $identity->professions()->sync($validated['professions'] ?? []);
        if ($validated['type'] === 'person' && Schema::hasTable('global_identity_religion')) {
            $identity->syncReligions($request->input('religions', null));
        }

        if ($request->input('action') === 'create') {
            return redirect()
                ->route('global.identities.create')
                ->with('success', __('hiko.saved_and_new'));
        }

        return redirect()
            ->route('global.identities.edit', $identity->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(GlobalIdentity $globalIdentity): View
    {
        return view('pages.global-identities.form', [
            'title' => __('hiko.identity') . ': ' . $globalIdentity->id . ' (' . __('hiko.global') . ')',
            'method' => 'PUT',
            'action' => route('global.identities.update', $globalIdentity->id),
            'label' => __('hiko.edit'),
            'identity' => $globalIdentity,
            'types' => GlobalIdentity::types(),
            'selectedType' => $globalIdentity->type,
            'selectedProfessions' => $this->getSelectedProfessions($globalIdentity),
            'selectedReligions' => $this->getSelectedReligions($globalIdentity),
        ]);
    }

    public function update(GlobalIdentityRequest $request, GlobalIdentity $globalIdentity): RedirectResponse
    {
        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'global',
            'resource_type' => 'global_identity_edit',
            'resource_id' => (string) $globalIdentity->id,
        ], $request->user());

        if (!$lock['ok']) {
            return redirect()
                ->route('identities')
                ->with('success', __('hiko.page_lock_not_owned'))
                ->with('success_sticky', true);
        }

        $validated = $request->validated();
        $identityData = collect($validated)->except(['professions', 'religions'])->toArray();

        $globalIdentity->update($identityData);
        $globalIdentity->professions()->sync($validated['professions'] ?? []);
        if ($validated['type'] === 'person' && Schema::hasTable('global_identity_religion')) {
            $globalIdentity->syncReligions($request->input('religions', null));
        } elseif (Schema::hasTable('global_identity_religion')) {
            $globalIdentity->syncReligions([]);
        }

        if ($request->input('action') === 'create') {
            return redirect()
                ->route('global.identities.create')
                ->with('success', __('hiko.saved_and_new'));
        }

        return redirect()
            ->route('global.identities.edit', $globalIdentity->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(GlobalIdentity $globalIdentity): RedirectResponse
    {
        $globalIdentity->delete();

        return redirect()
            ->route('identities') // Redirect to main list
            ->with('success', __('hiko.removed'));
    }

    protected function getSelectedProfessions(GlobalIdentity $identity): array
    {
        return $identity->professions->map(fn($p) => [
            'value' => $p->id,
            'label' => $p->name . ' (Global)',
        ])->toArray();
    }

    protected function getSelectedReligions(GlobalIdentity $identity): array
    {
        if (!Schema::hasTable('global_identity_religion')) {
            return [];
        }

        $locale = app()->getLocale();
        $ids = $identity->religions->pluck('id')->all();

        if (empty($ids)) {
            return [];
        }

        $rows = DB::table('religion_translations as rt')
            ->join('religions as r', 'r.id', '=', 'rt.religion_id')
            ->select('rt.religion_id', 'rt.path_text', 'r.is_active')
            ->where('rt.locale', $locale)
            ->whereIn('rt.religion_id', $ids)
            ->get();

        return $rows->map(fn($r) => [
            'value' => (int)$r->religion_id,
            'label' => $r->is_active
                ? $r->path_text
                : "{$r->path_text} — (inactive)",
        ])->toArray();
    }
}
