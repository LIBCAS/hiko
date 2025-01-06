<?php

namespace App\Http\Controllers;

use App\Models\GlobalProfessionCategory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GlobalProfessionCategoryController extends Controller
{
    protected array $rules = [
        'cs' => ['required_without:en', 'string', 'max:255'],
        'en' => ['nullable', 'string', 'max:255'],
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
            'title' => __('hiko.new_global_profession_category'),
            'professionCategory' => new GlobalProfessionCategory(),
            'action' => route('global.professions.category.store'),
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
            ->route('global.professions.category.edit', $globalProfessionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(GlobalProfessionCategory $globalProfessionCategory): View
    {
        $globalProfessionCategory->load('professions');

        return view('pages.global-professions-categories.form', [
            'title' => __('hiko.global_profession_category'),
            'professionCategory' => $globalProfessionCategory,
            'action' => route('global.professions.category.update', $globalProfessionCategory->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'professions' => $globalProfessionCategory->professions, // Assuming you pass related professions
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
            ->route('global.professions.category.edit', $globalProfessionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $globalProfessionCategory->delete();

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }
}
