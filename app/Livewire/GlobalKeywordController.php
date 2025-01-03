<?php

namespace App\Http\Controllers;

use App\Models\GlobalKeyword;
use App\Models\GlobalKeywordCategory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GlobalKeywordController extends Controller
{
    protected array $rules = [
        'cs' => ['required', 'string', 'max:255'],
        'en' => ['nullable', 'string', 'max:255'],
        'category_id' => ['nullable', 'exists:global_keyword_categories,id'],
    ];

    public function index(): View
    {
        $keywords = GlobalKeyword::with('keyword_category')->paginate(20);
        return view('pages.global-keywords.index', compact('keywords'))
            ->with('title', __('hiko.global_keywords'));
    }

    public function create(): View
    {
        $categories = GlobalKeywordCategory::all();
        return view('pages.global-keywords.form', [
            'title' => __('hiko.new_global_keyword'),
            'keyword' => new GlobalKeyword(),
            'action' => route('global.keywords.store'),
            'label' => __('hiko.create'),
            'availableCategories' => $categories,
        ]);
    }    

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $keywordData = [
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
            'keyword_category_id' => $validated['category_id'] ?? null,
        ];
    
        $keyword = GlobalKeyword::create($keywordData);
    
        return redirect()
            ->route('global.keywords.edit', $keyword->id)
            ->with('success', __('hiko.saved'));
    }    

    public function edit(GlobalKeyword $globalKeyword): View
    {
        $categories = GlobalKeywordCategory::all();
        $globalKeyword->load('identities');
    
        return view('pages.global-keywords.form', [
            'title' => __('hiko.global_keyword'),
            'keyword' => $globalKeyword,
            'action' => route('global.keywords.update', $globalKeyword->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'availableCategories' => $categories,
        ]);
    }    
    
    public function update(Request $request, GlobalKeyword $globalKeyword): RedirectResponse
    {
        $validated = $request->validate($this->rules);
    
        $updateData = [
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
            'keyword_category_id' => $validated['category_id'] ?? null,
        ];
    
        $globalKeyword->update($updateData);
    
        return redirect()
            ->route('global.keywords.edit', $globalKeyword->id)
            ->with('success', __('hiko.saved'));
    }    

    public function destroy(GlobalKeyword $globalKeyword): RedirectResponse
    {
        $globalKeyword->delete();
    
        return redirect()
            ->route('keywords')
            ->with('success', __('hiko.removed'));
    }      
}
