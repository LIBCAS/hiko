<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use Illuminate\Http\Request;

class KeywordController extends Controller
{
    protected $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function index()
    {
        return view('pages.keywords.index', [
            'title' => __('Klíčová slova'),
        ]);
    }

    public function create()
    {
        return view('pages.keywords.form', [
            'title' => __('Nové klíčové slovo'),
            'keyword' => new Keyword(),
            'action' => route('keywords.store'),
            'label' => __('Vytvořit'),
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

        return redirect()->route('keywords.edit', $keyword->id)->with('success', __('Uloženo.'));
    }

    public function edit(Keyword $keyword)
    {
        return view('pages.keywords.form', [
            'title' => __('Klíčové slovo: '),
            'keyword' => $keyword,
            'method' => 'PUT',
            'action' => route('keywords.update', $keyword),
            'label' => __('Upravit'),
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

        return redirect()->route('keywords.edit', $keyword->id)->with('success', __('Uloženo.'));
    }

    public function destroy(Keyword $keyword)
    {
        $keyword->delete();

        return redirect()->route('keywords')->with('success', __('Odstraněno'));
    }
}
