<?php

namespace App\Http\Controllers;

use App\Exports\KeywordCategoriesExport;
use App\Http\Requests\KeywordCategoryRequest;
use App\Models\KeywordCategory;
use App\Services\PageLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KeywordCategoryController extends Controller
{
    public function create()
    {
        return view('pages.keywords-categories.form', [
            'title' => __('hiko.new_keyword_category'),
            'keywordCategory' => new KeywordCategory,
            'action' => route('keywords.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(KeywordCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return redirect()
                ->back()
                ->withErrors(['cs' => __('hiko.entity_already_exists')])
                ->withInput();
        }

        $keywordCategory = KeywordCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()
            ->route('keywords.category.edit', $keywordCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(KeywordCategory $keywordCategory)
    {
        return view('pages.keywords-categories.form', [
            'title' => __('hiko.keyword_category') . ': ' . $keywordCategory->getTranslation('name', app()->getLocale()),
            'keywordCategory' => $keywordCategory,
            'method' => 'PUT',
            'action' => route('keywords.category.update', $keywordCategory),
            'label' => __('hiko.edit'),
        ]);
    }

    public function update(KeywordCategoryRequest $request, KeywordCategory $keywordCategory): RedirectResponse
    {
        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'tenant',
            'resource_type' => 'keyword_category_edit',
            'resource_id' => (string) $keywordCategory->id,
        ], $request->user());

        if (!$lock['ok']) {
            return redirect()
                ->route('keywords')
                ->with('success', __('hiko.page_lock_not_owned'))
                ->with('success_sticky', true);
        }

        $validated = $request->validated();

        if ($request->failsDuplicateCheck($keywordCategory->id)) {
            return redirect()
                ->back()
                ->withErrors(['cs' => __('hiko.entity_already_exists')])
                ->withInput();
        }

        $keywordCategory->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()
            ->route('keywords.category.edit', $keywordCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(KeywordCategory $keywordCategory): RedirectResponse
    {
        $keywordCategory->delete();

        return redirect()
            ->route('keywords')
            ->with('success', __('hiko.removed'));
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new KeywordCategoriesExport, 'keywords-categories.xlsx');
    }
}
