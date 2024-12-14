<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\KeywordCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\KeywordsExport;

class KeywordController extends Controller
{
    protected function getRules(): array
    {
        $categoryTable = tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__keyword_categories'
            : 'keyword_categories';

        return [
            'cs' => ['max:255', 'required_without:en'],
            'en' => ['max:255', 'required_without:cs'],
            'category' => [
                'nullable',
                "exists:{$categoryTable},id",
            ],
        ];
    }

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
            'categories' => KeywordCategory::all(),
            'category' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate($this->getRules());

            $keyword = DB::transaction(function () use ($validated) {
                $keyword = Keyword::create([
                    'name' => [
                        'cs' => $validated['cs'],
                        'en' => $validated['en'],
                    ],
                ]);

                if (!empty($validated['category'])) {
                    $keyword->keyword_category()->associate($validated['category']);
                    $keyword->save(); // Обязательно сохранить изменения
                }

                return $keyword;
            });

            return redirect()
                ->route('keywords.edit', $keyword->id)
                ->with('success', __('hiko.saved'));
        } catch (\Exception $e) {
            Log::error('Error storing keyword: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', __('hiko.error_saving'));
        }
    }

    public function edit(Keyword $keyword): View
    {
        $keyword->load([
            'letters' => function ($query) {
                $query->with(['identities:id,name', 'places:id,name']);
            },
            'keyword_category'
        ]);
    
        return view('pages.keywords.form', [
            'title' => __('hiko.keyword') . ': ' . $keyword->id,
            'keyword' => $keyword,
            'method' => 'PUT',
            'action' => route('keywords.update', $keyword),
            'label' => __('hiko.edit'),
            'categories' => KeywordCategory::all(),
            'category' => $keyword->keyword_category,
        ]);
    }    

    public function update(Request $request, Keyword $keyword): RedirectResponse
    {
        $validated = $request->validate($this->getRules());
    
        DB::transaction(function () use ($validated, $keyword) {
            $keyword->update([
                'name' => [
                    'cs' => $validated['cs'],
                    'en' => $validated['en'],
                ],
            ]);
    
            $keyword->keyword_category()->dissociate();
    
            if (!empty($validated['category'])) {
                $keyword->keyword_category()->associate($validated['category']);
                $keyword->save();
            }
        });
    
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
