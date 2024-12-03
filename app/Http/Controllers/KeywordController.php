<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Exports\KeywordsExport;
use App\Models\KeywordCategory;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KeywordController extends Controller
{
    protected array $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
        'category' => ['nullable', 'exists:keyword_categories,id'],
    ];

    public function index(): View
    {
        return view('pages.keywords.index', [
            'title' => __('hiko.keywords'),
        ]);
    }

    public function create(): View
    {
        return view('pages.keywords.form', [
            'title' => __('hiko.new_keyword'),
            'keyword' => new Keyword,
            'action' => route('keywords.store'),
            'label' => __('hiko.create'),
            'category' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $keyword = Keyword::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        if (isset($validated['category'])) {
            $keyword->keyword_category()->associate($validated['category']);
        }

        return redirect()
            ->route('keywords.edit', $keyword->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Keyword $keyword): View
    {
        $keyword->load(['letters.identities', 'letters.places']);

        return view('pages.keywords.form', [
            'title' => __('hiko.keyword') . ': ' . $keyword->id,
            'keyword' => $keyword,
            'method' => 'PUT',
            'action' => route('keywords.update', $keyword),
            'label' => __('hiko.edit'),
            'category' => null,
        ]);
    }

    public function update(Request $request, Keyword $keyword): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $keyword->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        $keyword->keyword_category()->dissociate();
        if (isset($validated['category'])) {
            $keyword->keyword_category()->associate($validated['category']);
        }

        return redirect()
            ->route('keywords.edit', $keyword->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Keyword $keyword): RedirectResponse
    {
        $keyword->delete();

        return redirect()
            ->route('keywords')
            ->with('success', __('hiko.removed'));
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new KeywordsExport, 'keywords.xlsx');
    }
}
