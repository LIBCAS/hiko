<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalKeywordRequest;
use App\Models\GlobalKeyword;
use App\Models\GlobalKeywordCategory;
use App\Services\PageLockService;
use Illuminate\Http\RedirectResponse;

class GlobalKeywordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $keywords = GlobalKeyword::with('keyword_category')->paginate(20);
        return view('pages.global-keywords', compact('keywords'))
            ->with('title', __('hiko.global_keywords'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(GlobalKeywordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $keywordData = [
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
            'keyword_category_id' => $validated['keyword_category_id'] ?? null,
        ];

        $keyword = GlobalKeyword::create($keywordData);

        // Handle 'action' parameter
        if ($request->input('action') === 'create') {
            return redirect()
                ->route('global.keywords.create')
                ->with('success', __('hiko.saved'));
        }

        return redirect()
            ->route('global.keywords.edit', $keyword->id)
            ->with('success', __('hiko.saved'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GlobalKeyword $globalKeyword)
    {
        $categories = GlobalKeywordCategory::all();
        $globalKeyword->load('keyword_category');

        return view('pages.global-keywords.form', [
            'title' => __('hiko.global_keyword'),
            'keyword' => $globalKeyword,
            'action' => route('global.keywords.update', $globalKeyword->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'availableCategories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GlobalKeywordRequest $request, GlobalKeyword $globalKeyword): RedirectResponse
    {
        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'global',
            'resource_type' => 'global_keyword_edit',
            'resource_id' => (string) $globalKeyword->id,
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
            'keyword_category_id' => $validated['keyword_category_id'] ?? null,
        ];

        $globalKeyword->update($updateData);

        // Handle 'action' parameter
        if ($request->input('action') === 'create') {
            return redirect()
                ->route('global.keywords.create')
                ->with('success', __('hiko.saved'));
        }

        return redirect()
            ->route('global.keywords.edit', $globalKeyword->id)
            ->with('success', __('hiko.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GlobalKeyword $globalKeyword): RedirectResponse
    {
        $globalKeyword->delete();

        return redirect()
            ->route('keywords')
            ->with('success', __('hiko.removed'));
    }
}
