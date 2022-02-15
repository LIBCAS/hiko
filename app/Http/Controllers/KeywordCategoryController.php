<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KeywordCategory;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KeywordCategoriesExport;

class KeywordCategoryController extends Controller
{
    protected $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function create()
    {
        return view('pages.keywords-categories.form', [
            'title' => __('hiko.new_keyword_category'),
            'keywordCategory' => new KeywordCategory,
            'action' => route('keywords.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules);

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
            'title' => __('hiko.keyword_category') . ': ' . $keywordCategory->id,
            'keywordCategory' => $keywordCategory,
            'method' => 'PUT',
            'action' => route('keywords.category.update', $keywordCategory),
            'label' => __('hiko.edit'),
        ]);
    }

    public function update(Request $request, KeywordCategory $keywordCategory)
    {
        $validated = $request->validate($this->rules);

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

    public function destroy(KeywordCategory $keywordCategory)
    {
        $keywordCategory->delete();

        return redirect()
            ->route('keywords')
            ->with('success', __('hiko.removed'));
    }

    public function export()
    {
        return Excel::download(new KeywordCategoriesExport, 'keywords-categories.xlsx');
    }
}
