<?php

namespace App\Livewire;

use App\Models\Keyword;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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

    protected function findKeywords(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $perPage = 10;
    
        $tenantKeywordsQuery = $this->getTenantKeywordsQuery();
        $globalKeywordsQuery = $this->getGlobalKeywordsQuery();
    
        // Merge queries correctly
        $query = match ($filters['source']) {
            'local' => $tenantKeywordsQuery,
            'global' => $globalKeywordsQuery,
            default => $this->mergeQueries($tenantKeywordsQuery, $globalKeywordsQuery),
        };
    
        // Ensure Sorting Works Properly
        if (in_array($filters['order'], ['cs', 'en'])) {
            $query->orderByRaw(
                "CAST(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) AS CHAR) COLLATE utf8mb4_unicode_ci"
            );
        }
    
        // Apply Proper Pagination
        return $query->paginate($perPage);
    }      

    protected function mergeQueries($tenantKeywordsQuery, $globalKeywordsQuery): Builder
    {
        $filters = $this->filters;
    
        // Get SQL & Bindings separately
        $tenantBase = $tenantKeywordsQuery->toBase();
        $globalBase = $globalKeywordsQuery->toBase();
    
        $tenantSql = $tenantBase->toSql();
        $tenantBindings = $tenantBase->getBindings();
    
        $globalSql = $globalBase->toSql();
        $globalBindings = $globalBase->getBindings();
    
        // Manually merge queries while binding parameters correctly
        $unionSql = "
            SELECT id, keyword_category_id, name, 'local' AS source FROM ({$tenantSql}) AS local_keywords
            UNION ALL
            SELECT id, keyword_category_id, name, 'global' AS source FROM ({$globalSql}) AS global_keywords
        ";
    
        $unionQuery = DB::table(DB::raw("({$unionSql}) AS combined_keywords"))
            ->mergeBindings($tenantBase)
            ->mergeBindings($globalBase);
    
        // Sort the merged query properly
        $sortedSql = "
            SELECT *, ROW_NUMBER() OVER (
                ORDER BY CAST(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) AS CHAR) COLLATE utf8mb4_unicode_ci
            ) AS sort_index FROM ({$unionQuery->toSql()}) AS sorted_keywords
        ";
    
        $sortedQuery = DB::table(DB::raw("({$sortedSql}) AS final_keywords"))
            ->mergeBindings($unionQuery)
            ->select([
                'id',
                'keyword_category_id',
                'name',
                'source'
            ])
            ->orderBy('sort_index');
    
        // Wrap the final query in an Eloquent Builder and merge bindings correctly
        return Keyword::query()
            ->from(DB::raw("({$sortedQuery->toSql()}) AS fully_sorted_keywords"))
            ->mergeBindings($sortedQuery);
    }    

    protected function getTenantKeywordsQuery()
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

        return $tenantKeywords;
    }

    protected function getGlobalKeywordsQuery()
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

        return $globalKeywords;
    }
    protected function formatTableData($data): array
    {
        return [
            'header' => auth()->user()->cannot('manage-metadata')
                ? [__('hiko.source'), 'CS', 'EN', __('hiko.category')]
                : ['', __('hiko.source'), 'CS', 'EN', __('hiko.category')],
            'rows' => $data->map(function ($pf) {
                // Determine whether the keyword is local or global
                $keyword = $pf->source === 'local'
                    ? Keyword::find($pf->id)
                    : \App\Models\GlobalKeyword::find($pf->id);
    
                // Handle cases where $keyword is null
                if (!$keyword) {
                    return [
                        ['label' => 'N/A'], // Placeholder for edit link
                        ['label' => 'N/A'], // Placeholder for source
                        ['label' => 'No CS name'],
                        ['label' => 'No EN name'],
                        ['label' => "<span class='text-red-600'>" . __('hiko.no_attached_category') . "</span>"],
                    ];
                }
    
                // Translations
                $csName = $keyword->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $keyword->getTranslation('name', 'en') ?? 'No EN name';
    
                // Source label
                $sourceLabel = $pf->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";
    
                // Category display with red text for missing category
                $categoryDisplay = $keyword->keyword_category
                    ? $keyword->keyword_category->getTranslation('name', 'cs') ?? ''
                    : "<span class='text-red-600'>" . __('hiko.no_attached_category') . "</span>";
    
                // Edit link logic
                $editLink = $pf->source === 'local'
                    ? ['label' => __('hiko.edit'), 'link' => route('keywords.edit', $pf->id)]
                    : (auth()->user()->can('manage-users')
                        ? ['label' => __('hiko.edit'), 'link' => route('global.keywords.edit', $pf->id)]
                        : ['label' => __('hiko.edit'), 'link' => '#', 'disabled' => true]);
    
                // Compile the row
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
