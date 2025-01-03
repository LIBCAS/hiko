<?php

namespace App\Livewire;

use App\Models\KeywordCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class KeywordCategoriesTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'cs',
        'source' => 'all', // 'local', 'global', 'all'
        'cs' => '',
        'en' => '',
    ];

    public function search()
    {
        $this->resetPage('categoriesPage');
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function render()
    {
        $categories = $this->findCategories();

        return view('livewire.keyword-categories-table', [
            'tableData' => $this->formatTableData($categories),
            'pagination' => $categories,
        ]);
    }

    protected function findCategories()
    {
        $filters = $this->filters;

        $categories = collect();

        // Fetch tenant categories if 'local' or 'all' is selected
        if ($filters['source'] === 'local' || $filters['source'] === 'all') {
            $tenantCategories = $this->getTenantCategories();
            $categories = $categories->merge($tenantCategories);
        }

        // Fetch global categories if 'global' or 'all' is selected
        if ($filters['source'] === 'global' || $filters['source'] === 'all') {
            $globalCategories = $this->getGlobalCategories();
            $categories = $categories->merge($globalCategories);
        }

        // Sort the collection
        if ($filters['order'] === 'cs' || $filters['order'] === 'en') {
            $categories = $categories->sortBy(function ($item) use ($filters) {
                return strtolower($item->getTranslation('name', $filters['order']) ?? '');
            })->values();
        }

        // Paginate the collection
        $page = $this->categoriesPage ?? 1;
        $perPage = 10;
        $total = $categories->count();

        $categoriesForPage = $categories->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $categoriesForPage,
            $total,
            $perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'pageName' => 'categoriesPage',
            ]
        );

        return $paginator;
    }

    protected function getTenantCategories()
    {
        $filters = $this->filters;

        $tenantCategories = KeywordCategory::select(
                'id',
                'name',
                DB::raw("'local' AS source")
            );

        // Apply search filters
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
            $tenantCategories->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%{$csFilter}%"]);
        }

        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
            $tenantCategories->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$enFilter}%"]);
        }

        return $tenantCategories->get();
    }

    protected function getGlobalCategories()
    {
        $filters = $this->filters;

        $globalCategories = \App\Models\GlobalKeywordCategory::select(
                'id',
                'name',
                DB::raw("'global' AS source")
            );

        // Apply search filters
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
            $globalCategories->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"cs\"'))) LIKE ?", ["%{$csFilter}%"]);
        }

        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
            $globalCategories->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) LIKE ?", ["%{$enFilter}%"]);
        }

        return $globalCategories->get();
    }

    protected function formatTableData($data)
    {
        $header = auth()->user()->cannot('manage-metadata')
            ? [__('hiko.source'), 'CS', 'EN']
            : ['', __('hiko.source'), 'CS', 'EN'];
    
        return [
            'header' => $header,
            'rows' => $data->map(function ($category) {
                // Access translations
                $csName = $category->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $category->getTranslation('name', 'en') ?? 'No EN name';
    
                // Source label
                $sourceLabel = $category->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";
    
                // Build the edit link with the correct route name
                if ($category->source === 'local') {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('keywords.category.edit', $category->id),
                    ];
                } elseif ($category->source === 'global' && auth()->user()->can('manage-users')) {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('global.keywords.category.edit', $category->id),
                    ];
                } else {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => '#',
                        'disabled' => true,
                    ];
                }
    
                // Construct the row
                $row = auth()->user()->cannot('manage-metadata') ? [] : [$editLink];
    
                $row[] = [
                    'label' => $sourceLabel,
                ];
    
                $row = array_merge($row, [
                    [
                        'label' => $csName,
                    ],
                    [
                        'label' => $enName,
                    ],
                ]);
    
                return $row;
            })->toArray(),
        ];
    }
}
