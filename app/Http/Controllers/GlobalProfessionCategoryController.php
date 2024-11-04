<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\GlobalProfessionCategory;
use App\Models\GlobalProfession;

class GlobalProfessionCategoryController extends Controller
{
    protected array $rules = [
        'cs' => ['required_without:en', 'max:255'],
        'en' => ['required_without:cs', 'max:255'],
    ];

    public function index(): View
    {
        $categories = GlobalProfessionCategory::with('professions')->paginate(20);
        return view('pages.global-professions-categories.index', compact('categories'))
            ->with('title', __('hiko.global_profession_categories'));
    }

    public function create(): View
    {
        return view('pages.global-professions-categories.form', [
            'title' => __('hiko.new_global_professions_category'),
            'professionCategory' => new GlobalProfessionCategory(),
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
            ],
        ]);

        return redirect()
            ->route('global.profession.category.edit', $globalProfessionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(GlobalProfessionCategory $globalProfessionCategory): View
    {
        $globalProfessionCategory->load('professions');

        return view('pages.global-professions-categories.form', [
            'title' => __('hiko.edit_global_professions_category'),
            'professionCategory' => $globalProfessionCategory,
            'action' => route('global.profession.category.update', $globalProfessionCategory->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
        ]);
    }

    public function update(Request $request, GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $globalProfessionCategory->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
        ]);

        return redirect()
            ->route('global.profession.category.edit', $globalProfessionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $globalProfessionCategory->delete();

        return redirect()
            ->route('global.profession.category.index')
            ->with('success', __('hiko.removed'));
    }
}
