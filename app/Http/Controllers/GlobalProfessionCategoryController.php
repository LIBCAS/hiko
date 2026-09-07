<?php

namespace App\Http\Controllers;

use App\Models\GlobalProfessionCategory;
use App\Services\PageLockService;
use App\Http\Requests\GlobalProfessionCategoryRequest;
use Illuminate\Http\RedirectResponse;

class GlobalProfessionCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = GlobalProfessionCategory::with('professions')->paginate(20);
        return view('pages.global-professions-categories', compact('categories'))
            ->with('title', __('hiko.global_profession_categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
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
    public function store(GlobalProfessionCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
    public function edit(GlobalProfessionCategory $globalProfessionCategory)
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
    public function update(GlobalProfessionCategoryRequest $request, GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'global',
            'resource_type' => 'global_profession_category_edit',
            'resource_id' => (string) $globalProfessionCategory->id,
        ], $request->user());

        if (!$lock['ok']) {
            return redirect()
                ->route('professions')
                ->with('success', __('hiko.page_lock_not_owned'))
                ->with('success_sticky', true);
        }

        $validated = $request->validated();

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
        if ($globalProfessionCategory->professions()->exists()) {
            return redirect()->back()->withErrors([
                'category' => __('hiko.profession_category_in_use'),
            ]);
        }

        $globalProfessionCategory->delete();

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }
}
