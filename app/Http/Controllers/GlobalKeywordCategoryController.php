<?php

namespace App\Http\Controllers;

use App\Models\GlobalKeywordCategory;
use App\Services\PageLockService;
use App\Http\Requests\GlobalKeywordCategoryRequest;
use Illuminate\Http\RedirectResponse;

class GlobalKeywordCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = GlobalKeywordCategory::with('keywords')->paginate(20);
        return view('pages.global-keywords-categories.index', compact('categories'))
            ->with('title', __('hiko.global_keyword_categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.global-keywords-categories.form', [
            'title' => __('hiko.global_keyword_categories'),
            'keywordCategory' => new GlobalKeywordCategory(),
            'action' => route('global.keywords.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GlobalKeywordCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $categoryData = [
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
        ];

        $category = GlobalKeywordCategory::create($categoryData);

        // Handle action parameter
        if ($request->input('action') === 'create') {
            return redirect()
                ->route('global.keywords.category.create')
                ->with('success', __('hiko.saved'));
        }

        return redirect()
            ->route('global.keywords.category.edit', $category->id)
            ->with('success', __('hiko.saved'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GlobalKeywordCategory $globalKeywordCategory)
    {
        $globalKeywordCategory->load('keywords');

        return view('pages.global-keywords-categories.form', [
            'title' => __('hiko.global_keyword_categories'),
            'keywordCategory' => $globalKeywordCategory,
            'action' => route('global.keywords.category.update', $globalKeywordCategory->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GlobalKeywordCategoryRequest $request, GlobalKeywordCategory $globalKeywordCategory): RedirectResponse
    {
        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'global',
            'resource_type' => 'global_keyword_category_edit',
            'resource_id' => (string) $globalKeywordCategory->id,
        ], $request->user());

        if (!$lock['ok']) {
            return redirect()
                ->route('keywords')
                ->with('success', __('hiko.page_lock_not_owned'))
                ->with('success_sticky', true);
        }

        $validated = $request->validated();

        $updateData = [
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
        ];

        $globalKeywordCategory->update($updateData);

        // Handle action parameter
        if ($request->input('action') === 'create') {
            return redirect()
                ->route('global.keywords.category.create')
                ->with('success', __('hiko.saved'));
        }

        return redirect()
            ->route('global.keywords.category.edit', $globalKeywordCategory->id)
            ->with('success', __('hiko.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GlobalKeywordCategory $globalKeywordCategory): RedirectResponse
    {
        $globalKeywordCategory->delete();

        return redirect()
            ->route('keywords')
            ->with('success', __('hiko.removed'));
    }
}
