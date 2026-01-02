<?php

namespace App\Http\Controllers;

use App\Exports\KeywordsExport;
use App\Http\Requests\KeywordRequest;
use App\Models\Keyword;
use App\Models\KeywordCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KeywordController extends Controller
{
    public function index()
    {
        return view('pages.keywords.index', [
            'title' => __('hiko.keywords'),
        ]);
    }

    public function create()
    {
        if (!tenancy()->initialized) {
            abort(403, __('hiko.tenancy_not_initialized'));
        }

        return view('pages.keywords.form', [
            'title' => __('hiko.new_keyword'),
            'keyword' => new Keyword,
            'action' => route('keywords.store'),
            'label' => __('hiko.create'),
            'categories' => KeywordCategory::all(),
            'category' => null,
        ]);
    }

    public function store(KeywordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return redirect()->back()
                ->withErrors(['cs' => __('hiko.entity_already_exists')])
                ->withInput();
        }

        $keyword = Keyword::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'keyword_category_id' => $validated['keyword_category_id'] ?? null,
        ]);

        if ($request->input('action') === 'create') {
            return redirect()
                ->route('keywords.create')
                ->with('success', __('hiko.saved'));
        }

        return redirect()
            ->route('keywords.edit', $keyword->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Keyword $keyword)
    {
        $keyword->load([
            'letters' => function ($query) {
                $query->with(['identities:id,name', 'places:id,name']);
            },
            'keyword_category'
        ]);

        return view('pages.keywords.form', [
            'title' => __('hiko.keyword') . ': ' . $keyword->getTranslation('name', app()->getLocale()),
            'keyword' => $keyword,
            'method' => 'PUT',
            'action' => route('keywords.update', $keyword),
            'label' => __('hiko.edit'),
            'categories' => KeywordCategory::all(),
            'category' => $keyword->keyword_category,
        ]);
    }

    public function update(KeywordRequest $request, Keyword $keyword): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($keyword->id)) {
            return redirect()->back()
                ->withErrors(['cs' => __('hiko.entity_already_exists')])
                ->withInput();
        }

        $keyword->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'keyword_category_id' => $validated['keyword_category_id'] ?? null,
        ]);

        if ($request->input('action') === 'create') {
            return redirect()
                ->route('keywords.create')
                ->with('success', __('hiko.saved'));
        }

        return redirect()
            ->route('keywords.edit', $keyword->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Keyword $keyword): RedirectResponse
    {
        try {
            $keyword->delete();

            return redirect()
                ->route('keywords')
                ->with('success', __('hiko.removed'));
        } catch (\Exception $e) {
            Log::error('Error deleting keyword: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', __('hiko.error_removing'));
        }
    }

    public function export(): BinaryFileResponse
    {
        try {
            return Excel::download(new KeywordsExport, 'keywords.xlsx');
        } catch (\Exception $e) {
            Log::error('Error exporting keywords: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', __('hiko.error_exporting'));
        }
    }
}
