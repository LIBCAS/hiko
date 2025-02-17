<?php

namespace App\Livewire;

use App\Models\Keyword;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

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
    
    public function mergeAll()
    {
        Log::info('[mergeAll] Button clicked! Fetching tenant prefix...');
    
        // Get the tenant's table prefix dynamically
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            Log::error("[mergeAll] Failed to get tenant prefix!");
            session()->flash('error', 'Tenant prefix not found.');
            return;
        }
    
        $tenantPrefix = $tenant->table_prefix . '__';
        Log::info("[mergeAll] Using Tenant Prefix: $tenantPrefix");
    
        // Retrieve all local keywords
        $localKeywords = DB::table("{$tenantPrefix}keywords")->get();
    
        if ($localKeywords->isEmpty()) {
            Log::warning("[mergeAll] No local keywords found.");
            session()->flash('warning', 'No local keywords to merge.');
            return;
        }
    
        // Get all global keywords for matching
        $globalKeywords = DB::table("global_keywords")->get();
        $merged = 0;
    
        foreach ($localKeywords as $local) {
            $localNameJson = json_decode($local->name, true);
            $csName = strtolower(trim($localNameJson['cs'] ?? ''));
            $enName = strtolower(trim($localNameJson['en'] ?? ''));
    
            Log::info("[mergeAll] Checking Local Keyword: CS='$csName', EN='$enName'");
    
            // Find exact match first
            $globalMatch = null;
            foreach ($globalKeywords as $global) {
                $globalNameJson = json_decode($global->name, true);
                $globalCsName = strtolower(trim($globalNameJson['cs'] ?? ''));
                $globalEnName = strtolower(trim($globalNameJson['en'] ?? ''));
    
                // Remove 'GLOBAL' prefixes
                $globalCsStripped = preg_replace('/^global/i', '', $globalCsName);
                $globalEnStripped = preg_replace('/^global/i', '', $globalEnName);
    
                // Exact match check
                if ($csName === $globalCsStripped || $enName === $globalEnStripped) {
                    $globalMatch = $global;
                    break;
                }
            }
    
            if ($globalMatch) {
                Log::info("[mergeAll] Merging Local Keyword '{$csName}' -> Global Keyword ID {$globalMatch->id}");
    
                // **STEP 1: Find letters linked to the local keyword**
                $linkedLetters = DB::table("{$tenantPrefix}letter_keyword")
                    ->where('keyword_id', $local->id)
                    ->get();
    
                foreach ($linkedLetters as $letter) {
                    // **STEP 2: Check if this letter already has a global_keyword_id**
                    $existingGlobal = DB::table("{$tenantPrefix}letter_keyword")
                        ->where('letter_id', $letter->letter_id)
                        ->whereNotNull('global_keyword_id')
                        ->first();
    
                    if ($existingGlobal) {
                        Log::info("[mergeAll] Letter {$letter->letter_id} already has global_keyword_id: {$existingGlobal->global_keyword_id}");
                    } else {
                        // **STEP 3: Reassign the global_keyword_id to the letter_id of local keyword**
                        DB::table("{$tenantPrefix}letter_keyword")
                            ->where('letter_id', $letter->letter_id)
                            ->update(['global_keyword_id' => $globalMatch->id]);
    
                        Log::info("[mergeAll] Reassigned global_keyword_id {$globalMatch->id} to letter_id {$letter->letter_id}");
                    }
                }
    
                // **STEP 4: Delete local keyword reference**
                DB::table("{$tenantPrefix}letter_keyword")
                    ->where('keyword_id', $local->id)
                    ->update(['keyword_id' => null]);
    
                // **STEP 5: Delete the local keyword itself**
                DB::table("{$tenantPrefix}keywords")->where('id', $local->id)->delete();
    
                $merged++;
            } else {
                Log::warning("[mergeAll] No global match found for '{$csName}' ({$enName}). Skipping.");
            }
        }
    
        Log::info("[mergeAll] Merge completed. Total merged: $merged");
        session()->flash('success', "$merged keywords merged successfully!");
    
        $this->dispatch('refreshTable'); // Ensure UI refresh
    }    
}
