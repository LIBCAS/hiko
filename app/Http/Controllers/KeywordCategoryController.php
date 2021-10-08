<?php

namespace App\Http\Controllers;

use App\Models\KeywordCategory;
use Illuminate\Http\Request;

class KeywordCategoryController extends Controller
{
    protected $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function create()
    {
        return view('pages.keywords-categories.form', [
            'title' => __('Nová kategorie klíčových slov'),
            'keywordCategory' => new KeywordCategory(),
            'action' => route('keywords.category.store'),
            'label' => __('Vytvořit'),
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

        return redirect()->route('keywords.category.edit', $keywordCategory->id)->with('success', __('Uloženo.'));
    }

    public function edit(KeywordCategory $keywordCategory)
    {
        return view('pages.keywords-categories.form', [
            'title' => __('Kategorie klíčových slov: '),
            'keywordCategory' => $keywordCategory,
            'method' => 'PUT',
            'action' => route('keywords.category.update', $keywordCategory),
            'label' => __('Upravit'),
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

        return redirect()->route('keywords.category.edit', $keywordCategory->id)->with('success', __('Uloženo.'));
    }

    public function destroy(KeywordCategory $keywordCategory)
    {
        $keywordCategory->delete();

        return redirect()->route('keywords')->with('success', __('Odstraněno'));
    }
}
