<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\GlobalKeywordCategory;
use App\Models\GlobalKeyword;

class GlobalKeywordCategoryController extends Controller
{
    protected array $rules = [
        'cs' => ['required_without:en', 'max:255'],
        'en' => ['required_without:cs', 'max:255'],
    ];

    public function index(): View
    {
        $categories = GlobalKeywordCategory::with('keywords')->paginate(20);
        return view('pages.global-keywords-categories.index', compact('categories'))
            ->with('title', __('hiko.global_keyword_categories'));
    }

    public function create(): View
    {
        return view('pages.global-keywords-categories.form', [
            'title' => __('hiko.new_global_keywords_category'),
            'keywordCategory' => new GlobalKeywordCategory(),
            'action' => route('global.keywords.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $globalKeywordCategory = GlobalKeywordCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
        ]);

        return redirect()
            ->route('global.keywords.category.edit', $globalKeywordCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(GlobalKeywordCategory $globalKeywordCategory): View
    {
        $globalKeywordCategory->load('keywords');

        return view('pages.global-keywords-categories.form', [
            'title' => __('hiko.global_keywords_category'),
            'keywordCategory' => $globalKeywordCategory,
            'action' => route('global.keywords.category.update', $globalKeywordCategory->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
        ]);
    }

    public function update(Request $request, GlobalKeywordCategory $globalKeywordCategory): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $globalKeywordCategory->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
        ]);

        return redirect()
            ->route('global.keywords.category.edit', $globalKeywordCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(GlobalKeywordCategory $globalKeywordCategory): RedirectResponse
    {
        $globalKeywordCategory->delete();

        return redirect()
            ->route('global.keywords.category.index')
            ->with('success', __('hiko.removed'));
    }
}
