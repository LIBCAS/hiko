<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use Illuminate\Http\Request;
use App\Exports\KeywordsExport;
use App\Models\KeywordCategory;
use Maatwebsite\Excel\Facades\Excel;

class KeywordController extends Controller
{
    protected $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
        'category' => ['nullable', 'exists:keyword_categories,id'],
    ];

    public function index()
    {
        return view('pages.keywords.index', [
            'title' => __('Klíčová slova'),
        ]);
    }

    public function create()
    {
        $keyword = new Keyword;

        return view('pages.keywords.form', [
            'title' => __('Nové klíčové slovo'),
            'keyword' => $keyword,
            'action' => route('keywords.store'),
            'label' => __('Vytvořit'),
            'category' => $this->getCategory($keyword),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules);

        $keyword = Keyword::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        $keyword->keyword_category()->associate($validated['category']);

        $keyword->save();

        return redirect()->route('keywords.edit', $keyword->id)->with('success', __('Uloženo.'));
    }

    public function edit(Keyword $keyword)
    {
        return view('pages.keywords.form', [
            'title' => __('Klíčové slovo č. ') . $keyword->id,
            'keyword' => $keyword,
            'method' => 'PUT',
            'action' => route('keywords.update', $keyword),
            'label' => __('Upravit'),
            'category' => $this->getCategory($keyword),
        ]);
    }

    public function update(Request $request, Keyword $keyword)
    {
        $validated = $request->validate($this->rules);

        $keyword->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        $keyword->keyword_category()->associate($validated['category']);

        $keyword->save();

        return redirect()->route('keywords.edit', $keyword->id)->with('success', __('Uloženo.'));
    }

    public function destroy(Keyword $keyword)
    {
        $keyword->delete();

        return redirect()->route('keywords')->with('success', __('Odstraněno'));
    }

    public function export()
    {
        return Excel::download(new KeywordsExport, 'keywords.xlsx');
    }

    protected function getCategory(Keyword $keyword)
    {
        $category = null;

        if ($keyword->keyword_category) {
            $category = [
                'id' => $keyword->keyword_category->id,
                'name' => implode(' | ', array_values($keyword->keyword_category->getTranslations('name'))),
            ];
        }

        if (request()->old('category')) {
            $category = KeywordCategory::where('id', '=', request()->old('category'))->get()[0];
            $category = [
                'id' => request()->old('category'),
                'name' => implode(' | ', array_values($category->getTranslations('name'))),
            ];
        }

        return $category;
    }
}
