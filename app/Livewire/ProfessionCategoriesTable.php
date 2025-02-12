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
        'source' => 'all',
        'cs' => '',
        'en' => '',
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

        $query = match ($filters['source']) {
            'local' => $tenantCategoriesQuery,
            'global' => $globalCategoriesQuery,
            default => $this->mergeQueries($tenantCategoriesQuery, $globalCategoriesQuery),
        };

        if (in_array($filters['order'], ['cs', 'en'])) {
            $query->orderByRaw(
                "CAST(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) AS CHAR) COLLATE utf8mb4_unicode_ci"
            );
        }

        return $query->paginate($perPage);
    }

    protected function mergeQueries($tenantCategoriesQuery, $globalCategoriesQuery): Builder
    {
        $filters = $this->filters;

        $tenantBase = $tenantCategoriesQuery->toBase();
        $globalBase = $globalCategoriesQuery->toBase();

        $unionSql = "
            SELECT id, name, 'local' AS source FROM ({$tenantBase->toSql()}) AS local_categories
            UNION ALL
            SELECT id, name, 'global' AS source FROM ({$globalBase->toSql()}) AS global_categories
        ";

        $unionQuery = DB::table(DB::raw("({$unionSql}) AS combined_categories"))
            ->mergeBindings($tenantBase)
            ->mergeBindings($globalBase);

        $sortedSql = "
            SELECT *, ROW_NUMBER() OVER (
                ORDER BY CAST(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) AS CHAR) COLLATE utf8mb4_unicode_ci
            ) AS sort_index FROM ({$unionQuery->toSql()}) AS sorted_categories
        ";

        $sortedQuery = DB::table(DB::raw("({$sortedSql}) AS final_categories"))
            ->mergeBindings($unionQuery)
            ->select(['id', 'name', 'source'])
            ->orderBy('sort_index');

        return ProfessionCategory::query()
            ->from(DB::raw("({$sortedQuery->toSql()}) AS fully_sorted_profession_categories"))
            ->mergeBindings($sortedQuery);
    }

    protected function getTenantCategoriesQuery()
    {
        $filters = $this->filters;

        $tenantCategories = ProfessionCategory::select(
            'id',
            'name',
            DB::raw("'local' AS source")
        );

        if (!empty($filters['cs'])) {
            $tenantCategories->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"cs\"'))) LIKE ?", ["%{$filters['cs']}%"]);
        }

        if (!empty($filters['en'])) {
            $tenantCategories->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) LIKE ?", ["%{$filters['en']}%"]);
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

        if (!empty($filters['cs'])) {
            $globalCategories->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"cs\"'))) LIKE ?", ["%{$filters['cs']}%"]);
        }

        if (!empty($filters['en'])) {
            $globalCategories->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) LIKE ?", ["%{$filters['en']}%"]);
        }

        return $globalCategories;
    }

    protected function formatTableData($data): array
    {
        return [
            'header' => auth()->user()->cannot('manage-metadata')
                ? [__('hiko.source'), 'CS', 'EN']
                : ['', __('hiko.source'), 'CS', 'EN'],
            'rows' => $data->map(function ($category) {
                $cat = ($category->source === 'local')
                    ? ProfessionCategory::find($category->id)
                    : \App\Models\GlobalProfessionCategory::find($category->id);
    
                if (!$cat) {
                    return [
                        ['label' => 'N/A'], // Placeholder for edit link
                        ['label' => 'N/A'], // Placeholder for source
                        ['label' => 'No CS name'],
                        ['label' => 'No EN name'],
                    ];
                }
    
                $csName = $cat->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $cat->getTranslation('name', 'en') ?? 'No EN name';
    
                $sourceLabel = $category->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";
    
                // Restore edit link logic
                $editLink = ($category->source === 'local')
                    ? ['label' => __('hiko.edit'), 'link' => route('professions.category.edit', $category->id)]
                    : (auth()->user()->can('manage-users')
                        ? ['label' => __('hiko.edit'), 'link' => route('global.professions.category.edit', $category->id)]
                        : ['label' => __('hiko.edit'), 'link' => '#', 'disabled' => true]);
    
                // Compile the row
                $row = auth()->user()->cannot('manage-metadata') ? [] : [$editLink];
                $row[] = ['label' => $sourceLabel];
                $row[] = ['label' => $csName];
                $row[] = ['label' => $enName];
    
                return $row;
            })->toArray(),
        ];
    }       
}
