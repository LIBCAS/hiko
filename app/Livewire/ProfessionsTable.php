<?php

namespace App\Livewire;

use App\Models\Profession;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProfessionsTable extends Component
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
        $this->resetPage('professionsPage');
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function render()
    {
        $professions = $this->findProfessions();

        return view('livewire.professions-table', [
            'tableData' => $this->formatTableData($professions),
            'pagination' => $professions,
        ]);
    }

    protected function findProfessions(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $perPage = 10;
    
        $tenantProfessionsQuery = $this->getTenantProfessionsQuery();
        $globalProfessionsQuery = $this->getGlobalProfessionsQuery();
    
        $query = match($filters['source']){
            'local' => $tenantProfessionsQuery,
            'global' => $globalProfessionsQuery,
            default => $this->mergeQueries($tenantProfessionsQuery, $globalProfessionsQuery),
        };
    
        // Proper sorting
        if (in_array($filters['order'], ['cs', 'en'])) {
            $orderColumn = "CONVERT(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) USING utf8mb4) COLLATE utf8mb4_unicode_ci";
            $query->orderByRaw($orderColumn);
        }
    
        return $query->paginate($perPage);
    }
    
    protected function mergeQueries($tenantProfessionsQuery, $globalProfessionsQuery): Builder
    {
        $filters = $this->filters;
    
        // Get base queries
        $tenantBase = $tenantProfessionsQuery->toBase();
        $globalBase = $globalProfessionsQuery->toBase();
    
        // Merge both queries with a ROW_NUMBER index for proper sorting
        $unionQuery = DB::table(DB::raw("(
            SELECT id, profession_category_id, name, 'local' AS source FROM ({$tenantBase->toSql()}) as local_professions
            UNION ALL
            SELECT id, profession_category_id, name, 'global' AS source FROM ({$globalBase->toSql()}) as global_professions
        ) as combined_professions"))
        ->mergeBindings($tenantBase)
        ->mergeBindings($globalBase);
    
        $query = DB::table(DB::raw("(
            SELECT *, ROW_NUMBER() OVER (
                ORDER BY CONVERT(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) USING utf8mb4) COLLATE utf8mb4_unicode_ci
            ) as sort_index
            FROM ({$unionQuery->toSql()}) as sorted_professions
        ) as final_professions"))
        ->mergeBindings($unionQuery)
        ->select([
            'id',
            'profession_category_id',
            'name',
            'source',
        ])
        ->orderBy('sort_index');
    
        return Profession::query()->from(DB::raw("({$query->toSql()}) as fully_sorted_professions"));
    }

    protected function getTenantProfessionsQuery()
    {
        $filters = $this->filters;

         $tenantProfessions = Profession::with('profession_category')
            ->select(
                'id',
                'profession_category_id',
                'name',
                DB::raw("'local' AS source")
            );

        // Apply search filters
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
             $tenantProfessions->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%{$csFilter}%"]);
        }

        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
             $tenantProfessions->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$enFilter}%"]);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $categoryFilter = strtolower($filters['category']);
            $tenantProfessions->whereHas('profession_category', function ($query) use ($categoryFilter) {
                $query->searchByName($categoryFilter);
            });
        }


        return $tenantProfessions;
    }

    protected function getGlobalProfessionsQuery()
    {
        $filters = $this->filters;

         $globalProfessions = \App\Models\GlobalProfession::with('profession_category')
            ->select(
                'id',
                'name',
                'profession_category_id',
                DB::raw("'global' AS source")
            );

        // Apply search filters
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
            $globalProfessions->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"cs\"'))) LIKE ?", ["%{$csFilter}%"]);
        }

        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
            $globalProfessions->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) LIKE ?", ["%{$enFilter}%"]);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $categoryFilter = strtolower($filters['category']);
           $globalProfessions->whereHas('profession_category', function ($query) use ($categoryFilter) {
                $query->searchByName($categoryFilter);
            });
        }

        return $globalProfessions;
    }

    protected function formatTableData($data)
    {
        return [
            'header' => auth()->user()->cannot('manage-metadata')
                ? [__('hiko.source'), 'CS', 'EN', __('hiko.category')]
                : ['', __('hiko.source'), 'CS', 'EN', __('hiko.category')],
            'rows' => $data->map(function ($pf) {
                if($pf->source === 'local'){
                    $profession = Profession::find($pf->id);
                } else {
                    $profession = \App\Models\GlobalProfession::find($pf->id);
                }
                $csName = $profession->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $profession->getTranslation('name', 'en') ?? 'No EN name';
                $sourceLabel = $pf->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";
                    
                // Wrap "no_attached_category" in a span with red text
                $categoryDisplay = $profession->profession_category
                    ? $profession->profession_category->getTranslation('name', 'cs') ?? ''
                    : "<span class='text-red-600'>".__('hiko.no_attached_category')."</span>";
    
                if ($pf->source === 'local') {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('professions.edit', $pf->id),
                    ];
                } elseif ($pf->source === 'global' && auth()->user()->can('manage-users')) {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('global.professions.edit', $pf->id),
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
