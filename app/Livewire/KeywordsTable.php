<?php

namespace App\Livewire;

use App\Models\Keyword;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class KeywordsTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'cs',
        'source' => 'all', // 'local', 'global', 'all'
        'cs' => '',
        'en' => '',
        'category' => '',
    ];

    public function search()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function render()
    {
        $keywords = $this->findKeywords();

        return view('livewire.keywords-table', [
            'tableData' => $this->formatTableData($keywords),
            'pagination' => $keywords,
        ]);
    }

    protected function findKeywords()
    {
        $filters = $this->filters;

        $keywords = collect();

        // Fetch tenant keywords if 'local' or 'all' is selected
        if ($filters['source'] === 'local' || $filters['source'] === 'all') {
            $tenantKeywords = $this->getTenantKeywords();
            $keywords = $keywords->merge($tenantKeywords);
        }

        // Fetch global keywords if 'global' or 'all' is selected
        if ($filters['source'] === 'global' || $filters['source'] === 'all') {
            $globalKeywords = $this->getGlobalKeywords();
            $keywords = $keywords->merge($globalKeywords);
        }

        // Sort the collection
        if ($filters['order'] === 'cs' || $filters['order'] === 'en') {
            $keywords = $keywords->sortBy(function ($item) use ($filters) {
                return strtolower($item->getTranslation('name', $filters['order']) ?? '');
            })->values();
        }

        // Paginate the collection
        $page = $this->page ?? 1;
        $perPage = 10;
        $total = $keywords->count();

        $keywordsForPage = $keywords->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $keywordsForPage,
            $total,
            $perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'pageName' => 'keywordsPage',
            ]
        );

        return $paginator;
    }

    protected function getTenantKeywords()
    {
        $filters = $this->filters;

        $tenantKeywords = Keyword::with('keyword_category')
            ->select(
                'id',
                'keyword_category_id',
                'name',
                DB::raw("'local' AS source")
            );

        // Apply search filters
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
            $tenantKeywords->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%{$csFilter}%"]);
        }

        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
            $tenantKeywords->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$enFilter}%"]);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $categoryFilter = strtolower($filters['category']);
            $tenantKeywords->whereHas('keyword_category', function ($query) use ($categoryFilter) {
                $query->searchByName($categoryFilter);
            });
        }

        return $tenantKeywords->get();
    }

    protected function getGlobalKeywords()
    {
        $filters = $this->filters;

        $globalKeywords = \App\Models\GlobalKeyword::with('keyword_category')
            ->select(
                'id',
                'name',
                'keyword_category_id',
                DB::raw("'global' AS source")
            );

        // Apply search filters
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
            $globalKeywords->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"cs\"'))) LIKE ?", ["%{$csFilter}%"]);
        }

        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
            $globalKeywords->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) LIKE ?", ["%{$enFilter}%"]);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $categoryFilter = strtolower($filters['category']);
            $globalKeywords->whereHas('keyword_category', function ($query) use ($categoryFilter) {
                $query->searchByName($categoryFilter);
            });
        }

        return $globalKeywords->get();
    }
        
    protected function formatTableData($data)
    {
        return [
            'header' => auth()->user()->cannot('manage-metadata')
                ? [__('hiko.source'), 'CS', 'EN', __('hiko.category')]
                : ['', __('hiko.source'), 'CS', 'EN', __('hiko.category')],
            'rows' => $data->map(function ($pf) {
                $csName = $pf->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $pf->getTranslation('name', 'en') ?? 'No EN name';
                $sourceLabel = $pf->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";
                $categoryDisplay = $pf->keyword_category
                    ? $pf->keyword_category->getTranslation('name', 'cs') ?? ''
                    : __('hiko.no_category');

                if ($pf->source === 'local') {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('keywords.edit', $pf->id),
                    ];
                } elseif ($pf->source === 'global' && auth()->user()->can('manage-users')) {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('global.keywords.edit', $pf->id),
                    ];
                } else {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => '#',
                        'disabled' => true,
                    ];
                }

                $row = auth()->user()->cannot('manage-metadata') ? [] : [$editLink];
                $row[] = ['label' => $sourceLabel];
                $row = array_merge($row, [
                    ['label' => $csName],
                    ['label' => $enName],
                    ['label' => $categoryDisplay],
                ]);

                return $row;
            })->toArray(),
        ];
    }
}
