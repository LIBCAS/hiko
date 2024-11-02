<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\GlobalProfessionCategory;
use App\Models\Identity;
use App\Models\GlobalProfession;

class GlobalProfessionCategoryController extends Controller
{
    protected array $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function create(): View
    {
        return view('pages.professions-categories.form', [
            'title' => __('hiko.new_global_professions_category'),
            'professionCategory' => new GlobalProfessionCategory,
            'action' => route('global.profession.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $globalProfessionCategory = GlobalProfessionCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ]
        ]);

        return redirect()
            ->route('global.profession.category.edit', $globalProfessionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(GlobalProfessionCategory $globalProfessionCategory): View
    {
        $globalProfessionCategory->load('identities', 'professions');
        $availableProfessions = GlobalProfession::all();
        $professions = $globalProfessionCategory->professions;

        return view('pages.professions-categories.form', [
            'title' => __('hiko.edit_global_professions_category'),
            'professionCategory' => $globalProfessionCategory,
            'action' => route('global.profession.category.update', $globalProfessionCategory->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'availableProfessions' => $availableProfessions,
            'professions' => $professions,
        ]);
    }

    public function update(Request $request, GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $globalProfessionCategory->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ]
        ]);

        return redirect()
            ->route('global.profession.category.edit', $globalProfessionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $globalProfessionCategory->delete();

        return redirect()
            ->route('global.profession.category.create')
            ->with('success', __('hiko.removed'));
    }
}

