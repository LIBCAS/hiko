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
        $keyword = new Keyword;

        return view('pages.keywords.form', [
            'title' => __('hiko.new_keyword'),
            'keyword' => $keyword,
            'action' => route('keywords.store'),
            'label' => __('hiko.create'),
            'category' => $this->getCategory($keyword),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'keywords.create' : 'keywords.edit';

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

        $keyword->save();

        return redirect()
            ->route($redirectRoute, $keyword->id)
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
            'category' => $this->getCategory($keyword),
        ]);
    }   

    public function update(Request $request, Keyword $keyword): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'keywords.create' : 'keywords.edit';

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

        $keyword->save();

        return redirect()
            ->route($redirectRoute, $keyword->id)
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

    protected function getCategory(Keyword $keyword): ?array
    {
        if (!$keyword->keyword_category && !request()->old('category')) {
            return null;
        }
        
        $id = request()->old('category') ? request()->old('category') : $keyword->keyword_category->id;

        $category = request()->old('category')
            ? KeywordCategory::where('id', '=', request()->old('category'))->get()[0]
            : $keyword->keyword_category;

        return [
            'id' => $id,
            'label' => $category->getTranslation('name', config('hiko.metadata_default_locale')),
        ];
    }

    protected function getLetters(Keyword $keyword): ?array
    {
        if ($keyword->letters->isEmpty() && !request()->old('letters')) {
            return null;
        }
    
        $letters = request()->old('letters') ? request()->old('letters') : $keyword->letters;
    
        return $letters->map(function ($letter) {
            return [
                'id' => $letter->id,
            ];
        })->toArray();
    }
}
