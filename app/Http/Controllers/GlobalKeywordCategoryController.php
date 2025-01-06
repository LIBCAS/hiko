<?php

namespace App\Http\Controllers;

use App\Models\GlobalKeywordCategory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GlobalKeywordCategoryController extends Controller
{
    protected array $rules = [
        'cs' => ['required_without:en', 'string', 'max:255'],
        'en' => ['required_without:cs', 'string', 'max:255'],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $categories = GlobalKeywordCategory::with('keywords')->paginate(20);
        return view('pages.global-keywords-categories.index', compact('categories'))
            ->with('title', __('hiko.global_keyword_categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('pages.global-keywords-categories.form', [
            'title' => __('hiko.new_global_keyword_category'),
            'keywordCategory' => new GlobalKeywordCategory(),
            'action' => route('global.keywords.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);

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
    public function edit(GlobalKeywordCategory $globalKeywordCategory): View
    {
        $globalKeywordCategory->load('keywords');

        return view('pages.global-keywords-categories.form', [
            'title' => __('hiko.edit_global_keyword_category'),
            'keywordCategory' => $globalKeywordCategory,
            'action' => route('global.keywords.category.update', $globalKeywordCategory->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GlobalKeywordCategory $globalKeywordCategory): RedirectResponse
    {
        $validated = $request->validate($this->rules);

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
