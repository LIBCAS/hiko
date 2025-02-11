<?php

namespace App\Livewire;

use App\Models\ProfessionCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;


class ProfessionCategoriesTable extends Component
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

        return view('livewire.profession-categories-table', [
            'tableData' => $this->formatTableData($categories),
            'pagination' => $categories,
        ]);
    }

    protected function findCategories(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $perPage = 10;
    
        $tenantCategoriesQuery = $this->getTenantCategoriesQuery();
        $globalCategoriesQuery = $this->getGlobalCategoriesQuery();
    
        $query = match($filters['source']){
            'local' => $tenantCategoriesQuery,
            'global' => $globalCategoriesQuery,
            default => $this->mergeQueries($tenantCategoriesQuery, $globalCategoriesQuery),
        };
    
        return $query->paginate($perPage);
    }
    
    protected function mergeQueries($tenantCategoriesQuery, $globalCategoriesQuery): Builder
    {
        $filters = $this->filters;
    
        // Get base queries
        $tenantBase = $tenantCategoriesQuery->toBase();
        $globalBase = $globalCategoriesQuery->toBase();
    
        // Merge both queries with a ROW_NUMBER index for proper sorting
        $unionQuery = DB::table(DB::raw("(
            SELECT id, name, 'local' AS source FROM ({$tenantBase->toSql()}) as local_categories
            UNION ALL
            SELECT id, name, 'global' AS source FROM ({$globalBase->toSql()}) as global_categories
        ) as combined_categories"))
        ->mergeBindings($tenantBase)
        ->mergeBindings($globalBase);
    
        $query = DB::table(DB::raw("(
            SELECT *, ROW_NUMBER() OVER (
                ORDER BY CONVERT(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) USING utf8mb4) COLLATE utf8mb4_unicode_ci
            ) as sort_index
            FROM ({$unionQuery->toSql()}) as sorted_categories
        ) as final_categories"))
        ->mergeBindings($unionQuery)
        ->select([
            'id',
            'name',
            'source',
        ])
        ->orderBy('sort_index');

        return ProfessionCategory::query()->from(DB::raw("({$query->toSql()}) as fully_sorted_profession_categories"));
    }     

    protected function getTenantCategoriesQuery()
    {
        $filters = $this->filters;

        $tenantCategories = ProfessionCategory::select(
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

        return $tenantCategories;
    }

    protected function getGlobalCategoriesQuery()
    {
        $filters = $this->filters;

         $globalCategories = \App\Models\GlobalProfessionCategory::select(
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

        return $globalCategories;
    }

    protected function formatTableData($data)
    {
        $header = auth()->user()->cannot('manage-metadata')
            ? [__('hiko.source'), 'CS', 'EN']
            : ['', __('hiko.source'), 'CS', 'EN'];

        return [
            'header' => $header,
              'rows' => $data->map(function ($category) {
                   if($category->source === 'local'){
                       $cat = ProfessionCategory::find($category->id);
                   }else{
                        $cat = \App\Models\GlobalProfessionCategory::find($category->id);
                   }
                // Access translations
                $csName = $cat->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $cat->getTranslation('name', 'en') ?? 'No EN name';

                // Source label
                $sourceLabel = $category->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";

                // Build the edit link with the correct route name
                 if ($category->source === 'local') {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('professions.category.edit', $category->id),
                    ];
                } elseif ($category->source === 'global' && auth()->user()->can('manage-users')) {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('global.professions.category.edit', $category->id),
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
