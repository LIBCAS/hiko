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

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $categories = GlobalProfessionCategory::with('professions')->paginate(20);
        return view('pages.global-professions-categories', compact('categories'))
            ->with('title', __('hiko.global_profession_categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('pages.global-professions-categories.form', [
            'title' => __('hiko.new_global_profession_category'),
            'professionCategory' => new GlobalProfessionCategory(),
            'action' => route('global.professions.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $globalProfessionCategory = GlobalProfessionCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
        ]);

        // Handle 'action' parameter
        if ($request->input('action') === 'create') {
            return redirect()
                ->route('global.professions.category.create')
                ->with('success', __('hiko.saved'));
        }

        return redirect()
            ->route('global.professions.category.edit', $globalProfessionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    /**
     * Show the form for editing the specified resource.
     */
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $globalProfessionCategory->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
        ]);

        // Handle 'action' parameter
        if ($request->input('action') === 'create') {
            return redirect()
                ->route('global.professions.category.create')
                ->with('success', __('hiko.saved'));
        }

        return redirect()
            ->route('global.professions.category.edit', $globalProfessionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $globalProfessionCategory->delete();

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }
}
