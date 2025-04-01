<?php

namespace App\Livewire;

use App\Models\Keyword;
use App\Models\GlobalKeyword;
use App\Models\KeywordCategory;
use App\Models\GlobalKeywordCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KeywordsTable extends Component
{
    use WithPagination;

    // Filter and search states
    public $filters = [
        'order'    => 'cs',
        'source'   => 'all', // 'local', 'global', 'all'
        'cs'       => '',
        'en'       => '',
        'category' => '',
    ];

    // Modal states
    public $showPreview = false;
    public $showManualMerge = false;
    public $isProcessing = false;
    public $selectedLocalKeyword = null;
    public $selectedGlobalKeyword = null;
    public $showMergeTwoKeywords = false;
    
    // Data for previews and manual merging
    public $previewData = [];
    public $unmergedKeywords = [];
    public $selectedKeywordOne = null; // For direct keyword merging
    public $selectedKeywordTwo = null; // For direct keyword merging
    
    // Merge options for all merge types
    public $mergeOptions = [
        'mergeCategories' => true,
        'preferGlobalCategories' => true, // Prefer global categories by default when merging
    ];

    public $categoryKeywords = [];
    public $availableGlobalKeywords = [];
    public $selectedKeywordOneDetails = null;
    public $selectedKeywordTwoDetails = null;
    public $similarityThreshold = 90; // Default similarity threshold
    public $mergeStats = [
        'total' => 0,
        'merged' => 0,
        'skipped' => 0,
    ];
    
    // Selection states for bulk operations
    public $selectedKeywords = [];
    public $selectAll = false;
    public $localKeywordSearch = '';
    public $globalKeywordSearch = '';

    public function mount()
    {
        // Handle session messages (convert them to alerts if your UI uses alerts)
        if (session()->has('success')) {
            session()->flash('message', session('success'));
            session()->flash('message-type', 'success');
        } elseif (session()->has('error')) {
            session()->flash('message', session('error'));
            session()->flash('message-type', 'error');
        } elseif (session()->has('warning')) {
            session()->flash('message', session('warning'));
            session()->flash('message-type', 'warning');
        }
    }

    // Dynamically update the preview when threshold changes
    public function updatedSimilarityThreshold()
    {
        if ($this->showPreview) {
            $this->previewData = $this->generatePreviewData();
            $this->sortPreviewByBestMatch(); // Ensure sorting by best match
            
            // Update merge stats after changing threshold
            $this->updateMergeStats();
        }
    }
    
    public function sortPreviewByBestMatch()
    {
        usort($this->previewData, function ($a, $b) {
            // First compare by willMerge (true comes first)
            if ($a['willMerge'] && !$b['willMerge']) {
                return -1;
            }
            if (!$a['willMerge'] && $b['willMerge']) {
                return 1;
            }
            
            // If both have same merge status, sort by similarity (highest first)
            $aAvgSimilarity = ($a['csSimilarity'] + $a['enSimilarity']) / 2;
            $bAvgSimilarity = ($b['csSimilarity'] + $b['enSimilarity']) / 2;
            
            return $bAvgSimilarity <=> $aAvgSimilarity;
        });
    }
    
    private function updateMergeStats()
    {
        $this->mergeStats = [
            'total' => count($this->previewData),
            'merged' => count(array_filter($this->previewData, function($item) { return $item['willMerge']; })),
            'skipped' => count(array_filter($this->previewData, function($item) { return !$item['willMerge']; })),
        ];
    }

    public function search()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
        
        // Reset selection states
        $this->selectedKeywords = [];
        $this->selectAll = false;
    }

    public function render()
    {
        // Get keywords for the main table
        $keywords = $this->findKeywords();
    
        // Store the unfiltered data separately to avoid affecting the main render cycle
        $unmergedKeywordsToDisplay = $this->unmergedKeywords;
        $globalKeywordsToDisplay = $this->availableGlobalKeywords;
        
        // Filter unmerged keywords by search term if specified (for modal only)
        if ($this->localKeywordSearch && !empty($unmergedKeywordsToDisplay)) {
            $search = strtolower($this->localKeywordSearch);
            $unmergedKeywordsToDisplay = array_filter($unmergedKeywordsToDisplay, function($k) use ($search) {
                return strpos(strtolower($k['cs']), $search) !== false 
                    || strpos(strtolower($k['en']), $search) !== false;
            });
        }
        
        // Filter global keywords by search term if specified (for modal only)
        if ($this->globalKeywordSearch && !empty($globalKeywordsToDisplay)) {
            $search = strtolower($this->globalKeywordSearch);
            
            $globalKeywordsToDisplay = array_filter($globalKeywordsToDisplay, function($k) use ($search) {
                $cs = strtolower($k['cs']);
                $en = strtolower($k['en']);
                return Str::startsWith($cs, $search) || Str::startsWith($en, $search)
                    || str_contains($cs, $search) || str_contains($en, $search);
            });
        
            // Re-sort after filtering
            usort($globalKeywordsToDisplay, function($a, $b) use ($search) {
                $aScore = (Str::startsWith(strtolower($a['cs']), $search) ? 2 : (str_contains(strtolower($a['cs']), $search) ? 1 : 0))
                        + ($a['avgSimilarity'] ?? 0);
                $bScore = (Str::startsWith(strtolower($b['cs']), $search) ? 2 : (str_contains(strtolower($b['cs']), $search) ? 1 : 0))
                        + ($b['avgSimilarity'] ?? 0);
                return $bScore <=> $aScore;
            });
        }        
    
        return view('livewire.keywords-table', [
            'tableData'  => $this->formatTableData($keywords),
            'pagination' => $keywords,
            'unmergedKeywordsToDisplay' => $unmergedKeywordsToDisplay,
            'globalKeywordsToDisplay' => $globalKeywordsToDisplay
        ]);
    }

    protected function findKeywords(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $perPage = 10;
    
        $tenantKeywordsQuery = $this->getTenantKeywordsQuery();
        $globalKeywordsQuery = $this->getGlobalKeywordsQuery();
    
        $query = match ($filters['source']) {
            'local'  => $tenantKeywordsQuery,
            'global' => $globalKeywordsQuery,
            default  => $this->mergeQueries($tenantKeywordsQuery, $globalKeywordsQuery),
        };
    
        if (in_array($filters['order'], ['cs', 'en'])) {
            $orderColumn = "CONVERT(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) USING utf8mb4) COLLATE utf8mb4_unicode_ci";
            $query->orderByRaw($orderColumn);
        }
    
        return $query->paginate($perPage);
    }

    protected function mergeQueries($tenantKeywordsQuery, $globalKeywordsQuery): Builder
    {
        $filters = $this->filters;

        $tenantBase = $tenantKeywordsQuery->toBase();
        $globalBase = $globalKeywordsQuery->toBase();
    
        $tenantSql = $tenantBase->toSql();
        $globalSql = $globalBase->toSql();
    
        $unionSql = "(
            SELECT id, keyword_category_id, name, 'local' AS source FROM ({$tenantSql}) AS local_keywords
            UNION ALL
            SELECT id, keyword_category_id, name, 'global' AS source FROM ({$globalSql}) AS global_keywords
        ) AS combined_keywords";
    
        $unionQuery = DB::table(DB::raw($unionSql))
            ->mergeBindings($tenantBase)
            ->mergeBindings($globalBase);
    
        $sortedSql = "(
            SELECT *, ROW_NUMBER() OVER (
                ORDER BY CONVERT(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) USING utf8mb4) COLLATE utf8mb4_unicode_ci
            ) AS sort_index
            FROM ({$unionQuery->toSql()}) AS sorted_keywords
        ) AS final_keywords";
    
        $sortedQuery = DB::table(DB::raw($sortedSql))
            ->mergeBindings($unionQuery)
            ->select([
                'id',
                'keyword_category_id',
                'name',
                'source',
            ])
            ->orderBy('sort_index');
    
        return Keyword::query()->from(DB::raw("({$sortedQuery->toSql()}) AS fully_sorted_keywords"))
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
    
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
            $tenantKeywords->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%{$csFilter}%"]);
        }
    
        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
            $tenantKeywords->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$enFilter}%"]);
        }
    
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

        $globalKeywords = GlobalKeyword::with('keyword_category')
            ->select(
                'id',
                'name',
                'keyword_category_id',
                DB::raw("'global' AS source")
            );
    
        if (!empty($filters['cs'])) {
            $globalKeywords->whereRaw(
                "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"cs\"'))) LIKE ?",
                ["%{$filters['cs']}%"]
            );
        }
    
        if (!empty($filters['en'])) {
            $globalKeywords->whereRaw(
                "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) LIKE ?",
                ["%{$filters['en']}%"]
            );
        }
    
        if (!empty($filters['category'])) {
            $globalKeywords->whereHas('keyword_category', function ($query) use ($filters) {
                $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%{$filters['category']}%"]);
            });
        }
    
        return $globalKeywords;
    }    

    protected function formatTableData($data)
    {
        return [
            'header' => auth()->user()->cannot('manage-metadata')
                ? [__('hiko.source'), 'CS', 'EN', __('hiko.category')]
                : ['', __('hiko.source'), 'CS', 'EN', __('hiko.category')],
            'rows'   => $data->map(function ($kw) {
                $keyword = $kw->source === 'local'
                ? Keyword::with('keyword_category')->find($kw->id)
                : GlobalKeyword::with('keyword_category')->find($kw->id);            
        
                if (!$keyword) {
                    return null; // Skip if keyword not found
                }
        
                $csName = $keyword->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $keyword->getTranslation('name', 'en') ?? 'No EN name';
                $sourceLabel = $kw->source === 'local'
                    ? "<span class='inline-block text-blue-600 bg-blue-100 border border-blue-200 text-xs uppercase px-2 py-1 rounded-full font-medium'>" . __('hiko.local') . "</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 border border-red-200 text-xs uppercase px-2 py-1 rounded-full font-medium'>" . __('hiko.global') . "</span>";
        
                $categoryDisplay = $keyword->keyword_category
                    ? $keyword->keyword_category->getTranslation('name', 'cs') ?? ''
                    : "<span class='text-red-600'>" . __('hiko.no_attached_category') . "</span>";
        
                $editLink = [
                    'label' => __('hiko.edit'),
                    'link'  => $kw->source === 'local'
                        ? route('keywords.edit', $kw->id)
                        : (auth()->user()->can('manage-users')
                            ? route('global.keywords.edit', $kw->id)
                            : '#'),
                    'disabled' => $kw->source === 'global' && !auth()->user()->can('manage-users'),
                    'id' => $kw->id,
                    'source' => $kw->source
                ];
        
                $row = auth()->user()->cannot('manage-metadata') ? [] : [$editLink];
                $row[] = ['label' => $sourceLabel, 'source' => $kw->source];
                $row = array_merge($row, [
                    ['label' => $csName],
                    ['label' => $enName],
                    ['label' => $categoryDisplay],
                ]);
        
                return $row;
            })->filter()->toArray(),
        ];
    }
    
    // Toggle select all keywords
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectAllKeywords();
        } else {
            $this->selectedKeywords = [];
        }
    }
    
    // Select all keywords in the current view
    protected function selectAllKeywords()
    {
        $keywords = $this->findKeywords();
        
        $this->selectedKeywords = [];
        foreach ($keywords as $kw) {
            $this->selectedKeywords[] = json_encode(['source' => $kw->source, 'id' => $kw->id]);
        }
    }
    
    // Deselect all keywords
    public function deselectAll()
    {
        $this->selectedKeywords = [];
        $this->selectAll = false;
    }
    
    // Merge pair of selected keywords
    public function mergePairSelected()
    {
        $this->isProcessing = true;
        
        if (count($this->selectedKeywords) !== 2) {
            session()->flash('error', __('Please select exactly two keywords to merge'));
            $this->isProcessing = false;
            return;
        }
        
        // Get the two selected keywords
        $keywordOne = json_decode($this->selectedKeywords[0], true);
        $keywordTwo = json_decode($this->selectedKeywords[1], true);
        
        // Set them as the keywords to merge
        $this->selectedKeywordOne = $keywordOne;
        $this->selectedKeywordTwo = $keywordTwo;
        
        try {
            // Load keyword details
            $this->loadKeywordDetails('one');
            $this->loadKeywordDetails('two');
            
            // Open the merge modal
            $this->openMergeTwoKeywords();
            session()->flash('info', __('Keywords loaded successfully. Configure merge options below.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Error loading keyword details: ') . $e->getMessage());
            
            // Reset selections if there was an error
            $this->selectedKeywordOne = null;
            $this->selectedKeywordTwo = null;
        } finally {
            $this->isProcessing = false;
        }
    }
    
    // Preview merge for selected keywords
    public function previewMergeSelected()
    {
        if (empty($this->selectedKeywords)) {
            session()->flash('error', __('Please select at least one keyword to preview merge'));
            return;
        }
        
        $this->isProcessing = true;
        
        // Get the tenant's table prefix dynamically
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            session()->flash('error', __('Tenant prefix not found.'));
            $this->isProcessing = false;
            return;
        }

        $tenantPrefix = $tenant->table_prefix . '__';
        
        // Filter preview data to only include selected keywords
        $selectedIds = [];
        foreach ($this->selectedKeywords as $jsonKeyword) {
            $keyword = json_decode($jsonKeyword, true);
            if ($keyword['source'] === 'local') {
                $selectedIds[] = $keyword['id'];
            }
        }
        
        // Generate preview data for all keywords, then filter
        $allPreviewData = $this->generatePreviewData();
        
        // Filter to only include selected local keywords
        $this->previewData = array_filter($allPreviewData, function($item) use ($selectedIds) {
            return in_array($item['localId'], $selectedIds);
        });
        
        // Sort preview data
        $this->sortPreviewByBestMatch();
        
        // Load keywords for each category to display in tooltips
        $this->categoryKeywords = $this->loadCategoryKeywords($tenantPrefix);
        
        // Update merge stats for the filtered selection
        $this->updateMergeStats();
        
        $this->showPreview = true;
        $this->isProcessing = false;
    }
    
    // Auto-merge selected keywords
    public function mergeAllSelected()
    {
        if (empty($this->selectedKeywords)) {
            session()->flash('error', __('Please select at least one keyword to merge'));
            return;
        }
        
        // Check if any global keywords are selected
        $hasGlobalKeywords = false;
        foreach ($this->selectedKeywords as $jsonKeyword) {
            $keyword = json_decode($jsonKeyword, true);
            if ($keyword['source'] === 'global') {
                $hasGlobalKeywords = true;
                break;
            }
        }
        
        if ($hasGlobalKeywords) {
            session()->flash('warning', __('Global keywords cannot be merged in bulk. Please use Direct Merge for global keywords.'));
            return;
        }
        
        $this->isProcessing = true;
        Log::info('[mergeAllSelected] Button clicked! Fetching tenant prefix...');
    
        // Get the tenant's table prefix dynamically
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            Log::error("[mergeAllSelected] Failed to get tenant prefix!");
            session()->flash('error', __('Tenant prefix not found.'));
            $this->isProcessing = false;
            return;
        }
    
        $tenantPrefix = $tenant->table_prefix . '__';
        Log::info("[mergeAllSelected] Using Tenant Prefix: $tenantPrefix");
    
        // Get all global keywords for matching
        $globalKeywords = DB::table("global_keywords")->get();
        
        // Get selected local keywords
        $selectedLocalIds = [];
        foreach ($this->selectedKeywords as $jsonKeyword) {
            $keyword = json_decode($jsonKeyword, true);
            if ($keyword['source'] === 'local') {
                $selectedLocalIds[] = $keyword['id'];
            }
        }
        
        if (empty($selectedLocalIds)) {
            session()->flash('error', __('No local keywords selected for merge'));
            $this->isProcessing = false;
            return;
        }
        
        // Get selected local keywords
        $localKeywords = DB::table("{$tenantPrefix}keywords")
            ->whereIn('id', $selectedLocalIds)
            ->get();
        
        $merged = 0;
        $skipped = 0;
    
        DB::beginTransaction();
        try {
            foreach ($localKeywords as $local) {
                try {
                    $localNameArr = json_decode($local->name, true);
                    $csName = strtolower(trim($localNameArr['cs'] ?? ''));
                    $enName = strtolower(trim($localNameArr['en'] ?? ''));
                    Log::info("[mergeAllSelected] Checking Local Keyword: CS='$csName', EN='$enName'");
    
                    // Normalize available names
                    $csNameNormalized = $csName ? Str::slug($csName) : '';
                    $enNameNormalized = $enName ? Str::slug($enName) : '';
    
                    // Find best matching global keyword
                    $globalMatch = null;
                    $bestSimilarity = 0;
    
                    foreach ($globalKeywords as $global) {
                        $globalNameArr = json_decode($global->name, true);
                        $globalCsName = strtolower(trim($globalNameArr['cs'] ?? ''));
                        $globalEnName = strtolower(trim($globalNameArr['en'] ?? ''));
    
                        // Remove any "global" prefix if present
                        $globalCsStripped = preg_replace('/^global\s+/i', '', $globalCsName);
                        $globalEnStripped = preg_replace('/^global\s+/i', '', $globalEnName);
    
                        $globalCsNormalized = $globalCsStripped ? Str::slug($globalCsStripped) : '';
                        $globalEnNormalized = $globalEnStripped ? Str::slug($globalEnStripped) : '';
    
                        $csSimilarity = 0;
                        $enSimilarity = 0;
                        similar_text($csNameNormalized, $globalCsNormalized, $csSimilarity);
                        similar_text($enNameNormalized, $globalEnNormalized, $enSimilarity);
    
                        // Check merge criteria with threshold
                        $csMatch = $csSimilarity > $this->similarityThreshold;
                        $enMatch = $enSimilarity > $this->similarityThreshold;
    
                        $csEmpty = empty($csNameNormalized) || empty($globalCsNormalized);
                        $enEmpty = empty($enNameNormalized) || empty($globalEnNormalized);
    
                        // Calculate average similarity for ranking
                        $avgSimilarity = ($csSimilarity + $enSimilarity) / 2;
    
                        // Only merge if either language meets the threshold and it's the best match so far
                        if ((($csMatch && $enMatch) || 
                            ($csMatch && $enEmpty) || 
                            ($enMatch && $csEmpty) ||
                            (($csMatch || $enMatch) && !$csEmpty && !$enEmpty)) && 
                            $avgSimilarity > $bestSimilarity) {
                            $globalMatch = $global;
                            $bestSimilarity = $avgSimilarity;
                        }
                    }
    
                    if ($globalMatch) {
                        Log::info("[mergeAllSelected] Merging Local Keyword '{$csName}' -> Global Keyword ID {$globalMatch->id}");
    
                        // Handle categories based on merge options
                        $localCategoryId = $local->keyword_category_id;
                        $globalCategoryId = $globalMatch->keyword_category_id;
                        $finalCategoryId = $globalCategoryId; // Default to keeping the global category
                        
                        // Handle categories based on mergeOptions setting
                        if ($this->mergeOptions['mergeCategories']) {
                            // If merging categories AND global keyword already has a category, use that
                            if ($globalCategoryId) {
                                $finalCategoryId = $globalCategoryId;
                                Log::info("[mergeAllSelected] Using existing global category ID: {$finalCategoryId}");
                            } 
                            // If global has no category but local does AND preferGlobalCategories is true, 
                            // do NOT create a new global category
                            else if ($localCategoryId && $this->mergeOptions['preferGlobalCategories']) {
                                // Keep the global keyword uncategorized
                                $finalCategoryId = null;
                                Log::info("[mergeAllSelected] No global category and preferGlobalCategories=true, keeping global keyword uncategorized");
                            }
                        } else {
                            // Not merging categories - keep the global category as is
                            Log::info("[mergeAllSelected] Category merging disabled, keeping global category");
                            $finalCategoryId = $globalCategoryId;
                        }
                        
                        // If we have determined a category, update the global keyword if needed
                        if ($finalCategoryId && $finalCategoryId != $globalMatch->keyword_category_id) {
                            DB::table("global_keywords")
                                ->where('id', $globalMatch->id)
                                ->update([
                                    'keyword_category_id' => $finalCategoryId
                                ]);
                            Log::info("[mergeAllSelected] Updated global keyword {$globalMatch->id} with category ID {$finalCategoryId}");
                        }
    
                        // Get letters linked to this local keyword
                        $linkedLetters = DB::table("{$tenantPrefix}keyword_letter")
                            ->where('keyword_id', $local->id)
                            ->get();
    
                        foreach ($linkedLetters as $letter) {
                            // Check if this letter already has the global keyword
                            $existingGlobalLink = DB::table("{$tenantPrefix}keyword_letter")
                                ->where('letter_id', $letter->letter_id)
                                ->where('global_keyword_id', $globalMatch->id)
                                ->first();
                                
                            if ($existingGlobalLink) {
                                // If already linked to global, simply delete the local link
                                DB::table("{$tenantPrefix}keyword_letter")
                                    ->where('letter_id', $letter->letter_id)
                                    ->where('keyword_id', $local->id)
                                    ->delete();
                                    
                                Log::info("[mergeAllSelected] Deleted duplicate link for letter {$letter->letter_id} - already had global keyword {$globalMatch->id}");
                            } else {
                                // Otherwise update the local link to point to global
                                DB::table("{$tenantPrefix}keyword_letter")
                                    ->where('letter_id', $letter->letter_id)
                                    ->where('keyword_id', $local->id)
                                    ->update([
                                        'global_keyword_id' => $globalMatch->id,
                                        'keyword_id' => null, // Nullify local keyword ID
                                    ]);
                                    
                                Log::info("[mergeAllSelected] Updated letter {$letter->letter_id} with global_keyword_id {$globalMatch->id}");
                            }
                        }
    
                        // Delete the local keyword record
                        DB::table("{$tenantPrefix}keywords")->where('id', $local->id)->delete();
                        $merged++;
                    } else {
                        Log::warning("[mergeAllSelected] No global match found for '{$csName}' ({$enName}). Skipping.");
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    // Catch individual keyword merge errors but continue with others
                    Log::error("[mergeAllSelected] Error merging keyword {$local->id}: " . $e->getMessage());
                    $skipped++;
                    // Don't rethrow - continue with next keyword
                }
            }
    
            // Only validate pivot records if we successfully merged some keywords
            if ($merged > 0) {
                // Validate pivot records before committing
                $allUpdatedPivots = DB::table("{$tenantPrefix}keyword_letter")
                    ->whereNotNull('global_keyword_id')
                    ->whereNull('keyword_id')
                    ->get();
    
                // Only validate if we have any updated pivots
                if ($allUpdatedPivots->count() > 0) {
                    $this->validatePivotUpdates($tenantPrefix, $allUpdatedPivots);
                }
    
                // Clean up orphaned categories
                $this->cleanUpOrphanedCategories($tenantPrefix);
            }
    
            DB::commit();
            
            $message = "";
            if ($merged > 0) {
                $message = "$merged " . __('keywords successfully merged!');
            }
            if ($skipped > 0) {
                $message .= " $skipped " . __('keywords skipped.');
            }
            
            Log::info("[mergeAllSelected] Merge completed. Total merged keywords: $merged, skipped: $skipped");
            session()->flash('success', $message);
            
            // Clear selected keywords after successful merge
            $this->selectedKeywords = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[mergeAllSelected] Error during merge: " . $e->getMessage());
            session()->flash('error', __('Error during merge: ') . $e->getMessage());
        }
    
        $this->isProcessing = false;
    }

    private function cleanUpOrphanedCategories($tenantPrefix)
    {
        // First check if identity_keyword_category table exists (for direct identity-category relationships)
        $hasIdentityCategoryTable = false;
        try {
            // Simple query to check if table exists
            DB::select("SHOW TABLES LIKE '{$tenantPrefix}identity_keyword_category'");
            $hasIdentityCategoryTable = true;
            Log::info("[cleanUpOrphanedCategories] Found identity_keyword_category table");
        } catch (\Exception $e) {
            Log::info("[cleanUpOrphanedCategories] No direct identity-category relationship table found");
        }
        
        if ($hasIdentityCategoryTable) {
            // If there's a direct identity-category relationship, include that in our orphan check
            $orphanedCategories = DB::table("{$tenantPrefix}keyword_categories as kc")
                ->leftJoin("{$tenantPrefix}keywords as k", "kc.id", "=", "k.keyword_category_id")
                ->leftJoin("{$tenantPrefix}identity_keyword_category as ikc", "kc.id", "=", "ikc.keyword_category_id") 
                ->whereNull("k.id")
                ->whereNull("ikc.identity_id") // Only delete if no identities are directly attached
                ->select("kc.id")
                ->get();
        } else {
            // Otherwise, just check for keywords
            $orphanedCategories = DB::table("{$tenantPrefix}keyword_categories as kc")
                ->leftJoin("{$tenantPrefix}keywords as k", "kc.id", "=", "k.keyword_category_id")
                ->whereNull("k.id")
                ->select("kc.id")
                ->get();
        }
    
        foreach ($orphanedCategories as $orphan) {
            DB::table("{$tenantPrefix}keyword_categories")->where('id', $orphan->id)->delete();
            Log::info("[cleanUpOrphanedCategories] Deleted orphaned category ID: {$orphan->id}");
        }
        
        Log::info("[cleanUpOrphanedCategories] Deleted " . count($orphanedCategories) . " orphaned categories");
    }

    public function updateSimilarityThreshold($value)
    {
        $this->similarityThreshold = intval($value);
        $this->previewData = $this->generatePreviewData(); 
        $this->sortPreviewByBestMatch();
        $this->updateMergeStats();
    }

    public function generatePreviewData()
    {
        // Get the tenant's table prefix dynamically
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            session()->flash('error', __('Tenant prefix not found.'));
            return [];
        }

        $tenantPrefix = $tenant->table_prefix . '__';
        
        // Get local and global keywords
        $localKeywords = DB::table("{$tenantPrefix}keywords")->get();
        $globalKeywords = DB::table("global_keywords")->get();
        
        $previewData = [];
        
        foreach ($localKeywords as $local) {
            $localNameArr = json_decode($local->name, true);
            $csName = strtolower(trim($localNameArr['cs'] ?? ''));
            $enName = strtolower(trim($localNameArr['en'] ?? ''));
            
            $csNameNormalized = $csName ? Str::slug($csName) : '';
            $enNameNormalized = $enName ? Str::slug($enName) : '';
            
            // Get the local category
            $localCategory = null;
            if ($local->keyword_category_id) {
                $localCategory = DB::table("{$tenantPrefix}keyword_categories")
                    ->where('id', $local->keyword_category_id)
                    ->first();
            }
            
            $bestMatch = null;
            $bestCsSimilarity = 0;
            $bestEnSimilarity = 0;
            $willMerge = false;
            $globalCs = '';
            $globalEn = '';
            $globalCategoryId = null;
            $globalCategoryName = '';
            $mergeReason = '';
            
            foreach ($globalKeywords as $global) {
                $globalNameArr = json_decode($global->name, true);
                $globalCsName = strtolower(trim($globalNameArr['cs'] ?? ''));
                $globalEnName = strtolower(trim($globalNameArr['en'] ?? ''));
                
                $globalCsStripped = preg_replace('/^global\s+/i', '', $globalCsName);
                $globalEnStripped = preg_replace('/^global\s+/i', '', $globalEnName);
                
                $globalCsNormalized = $globalCsStripped ? Str::slug($globalCsStripped) : '';
                $globalEnNormalized = $globalEnStripped ? Str::slug($globalEnStripped) : '';
                
                $csSimilarity = 0;
                $enSimilarity = 0;
                similar_text($csNameNormalized, $globalCsNormalized, $csSimilarity);
                similar_text($enNameNormalized, $globalEnNormalized, $enSimilarity);
                
                // Calculate average similarity for ranking
                $avgSimilarity = ($csSimilarity + $enSimilarity) / 2;
                
                // Consider as a match if it's the best so far
                if ($avgSimilarity > (($bestCsSimilarity + $bestEnSimilarity) / 2)) {
                    $bestMatch = $global;
                    $bestCsSimilarity = $csSimilarity;
                    $bestEnSimilarity = $enSimilarity;
                    $globalCs = $globalCsName;
                    $globalEn = $globalEnName;
                    $globalCategoryId = $global->keyword_category_id;
                    
                    // Get global category name
                    if ($globalCategoryId) {
                        $globalCategory = DB::table("global_keyword_categories")
                            ->where('id', $globalCategoryId)
                            ->first();
                        if ($globalCategory) {
                            $globalCatNameArr = json_decode($globalCategory->name, true);
                            $globalCategoryName = $globalCatNameArr['cs'] ?? '';
                        }
                    }
                    
                    // Merge criteria
                    $csMatch = $csSimilarity > $this->similarityThreshold;
                    $enMatch = $enSimilarity > $this->similarityThreshold;

                    $csEmpty = empty($csNameNormalized) || empty($globalCsNormalized);
                    $enEmpty = empty($enNameNormalized) || empty($globalEnNormalized);

                    // Either both match, or one matches and the other is empty, or either matches if both exist
                    if (($csMatch && $enMatch) || 
                        ($csMatch && $enEmpty) || 
                        ($enMatch && $csEmpty) ||
                        (($csMatch || $enMatch) && !$csEmpty && !$enEmpty)) 
                    {
                        $willMerge = true;
                        if ($csMatch && $enMatch) {
                            $mergeReason = __('Both language variants have high similarity');
                        } else if ($csMatch && !$enMatch) {
                            $mergeReason = __('CS variant has high similarity');
                        } else if (!$csMatch && $enMatch) {
                            $mergeReason = __('EN variant has high similarity');
                        } else if ($csMatch && $enEmpty) {
                            $mergeReason = __('CS variant has high similarity, EN is missing');
                        } else if ($enMatch && $csEmpty) {
                            $mergeReason = __('EN variant has high similarity, CS is missing');
                        }
                    } else {
                        $willMerge = false;
                        if (!$csEmpty && !$csMatch && !$enEmpty && !$enMatch) {
                            $mergeReason = __('Both language variants have low similarity');
                        } else if (!$csEmpty && !$csMatch) {
                            $mergeReason = __('CS variant has low similarity');
                        } else if (!$enEmpty && !$enMatch) {
                            $mergeReason = __('EN variant has low similarity');
                        }
                    }
                }
            }
            
            // Get local category name
            $localCategoryName = '';
            if ($localCategory) {
                $localCatNameArr = json_decode($localCategory->name, true);
                $localCategoryName = $localCatNameArr['cs'] ?? '';
            }
            
            // Add category comparisons for more detailed preview
            $categoryMatch = false;
            $categoryInfo = '';
            
            if ($localCategory && $globalCategoryId) {
                // Both have categories, check if they're the same
                $categoryMatch = ($localCategoryName == $globalCategoryName);
                if ($categoryMatch) {
                    $categoryInfo = __('Categories match');
                } else {
                    $categoryInfo = __('Categories differ') . ": '$localCategoryName' vs '$globalCategoryName'";
                }
            } else if ($localCategory) {
                $categoryInfo = __('Only local has category') . ": '$localCategoryName'";
            } else if ($globalCategoryId) {
                $categoryInfo = __('Only global has category') . ": '$globalCategoryName'";
            } else {
                $categoryInfo = __('No categories present');
            }
            
            $previewData[] = [
                'localId' => $local->id,
                'localCs' => $csName,
                'localEn' => $enName,
                'localCategoryId' => $local->keyword_category_id,
                'localCategoryName' => $localCategoryName,
                'globalId' => $bestMatch ? $bestMatch->id : null,
                'globalCs' => $globalCs,
                'globalEn' => $globalEn,
                'globalCategoryId' => $globalCategoryId,
                'globalCategoryName' => $globalCategoryName,
                'categoryMatch' => $categoryMatch,
                'categoryInfo' => $categoryInfo,
                'csSimilarity' => $bestCsSimilarity,
                'enSimilarity' => $bestEnSimilarity,
                'willMerge' => $willMerge,
                'mergeReason' => $mergeReason
            ];
        }
        
        // Update merge stats
        $this->updateMergeStats();
        
        return $previewData;
    }
    
    public function previewMerge()
    {
        $this->isProcessing = true;
        
        // Get the tenant's table prefix dynamically
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            session()->flash('error', __('Tenant prefix not found.'));
            $this->isProcessing = false;
            return;
        }
        
        $tenantPrefix = $tenant->table_prefix . '__';
        
        // Generate preview data
        $this->previewData = $this->generatePreviewData();
        $this->sortPreviewByBestMatch();
        
        // Load keywords for each category to display in tooltips
        $this->categoryKeywords = $this->loadCategoryKeywords($tenantPrefix);
        
        $this->showPreview = true;
        $this->isProcessing = false;
    }

    public function closePreview()
    {
        $this->showPreview = false;
    }

    public function openManualMerge()
    {
        $this->isProcessing = true;

        // Threshold to 50% for manual merge
        $this->similarityThreshold = 50;
        
        // Get the tenant's table prefix dynamically
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            session()->flash('error', __('Tenant prefix not found.'));
            $this->isProcessing = false;
            return;
        }

        $tenantPrefix = $tenant->table_prefix . '__';
        
        // Get all unmerged local keywords
        $localKeywords = DB::table("{$tenantPrefix}keywords")->get();
        
        $this->unmergedKeywords = [];
        foreach($localKeywords as $k) {
            $nameArr = json_decode($k->name, true);
            
            // Get category name if it exists
            $categoryName = '';
            $categoryId = $k->keyword_category_id;
            if ($categoryId) {
                $category = DB::table("{$tenantPrefix}keyword_categories")->find($categoryId);
                if ($category) {
                    $catNameArr = json_decode($category->name, true);
                    $categoryName = $catNameArr['cs'] ?? '';
                }
            }
            
            $this->unmergedKeywords[] = [
                'id' => $k->id,
                'cs' => $nameArr['cs'] ?? '',
                'en' => $nameArr['en'] ?? '',
                'category_id' => $categoryId,
                'category_name' => $categoryName
            ];
        }
        
        // Get all global keywords with category info
        $globalKeywords = DB::table("global_keywords")->get();
        
        $this->availableGlobalKeywords = [];
        foreach($globalKeywords as $k) {
            $nameArr = json_decode($k->name, true);
            
            // Get category name if it exists
            $categoryName = '';
            $categoryId = $k->keyword_category_id;
            if ($categoryId) {
                $category = DB::table("global_keyword_categories")->find($categoryId);
                if ($category) {
                    $catNameArr = json_decode($category->name, true);
                    $categoryName = $catNameArr['cs'] ?? '';
                }
            }
            
            $this->availableGlobalKeywords[] = [
                'id' => $k->id,
                'cs' => $nameArr['cs'] ?? '',
                'en' => $nameArr['en'] ?? '',
                'category_id' => $categoryId,
                'category_name' => $categoryName
            ];
        }
        
        // Let's be sure to reset any previously selected keywords
        $this->selectedLocalKeyword = null;
        $this->selectedGlobalKeyword = null;
        
        // Ensure these are properly initialized and marked for serialization
        $this->unmergedKeywords = collect($this->unmergedKeywords)->toArray();
        $this->availableGlobalKeywords = collect($this->availableGlobalKeywords)->toArray();
        
        // Defer showing the modal until after the data is ready
        $this->showManualMerge = true;
        $this->isProcessing = false;
    }

    public function selectLocalKeyword($id)
    {
        $this->isProcessing = true;
        $this->selectedLocalKeyword = $id;
        $this->selectedGlobalKeyword = null;
        
        // Find the selected local keyword
        $local = collect($this->unmergedKeywords)->firstWhere('id', $id);
        
        if ($local) {
            $csNameNormalized = $local['cs'] ? Str::slug(strtolower($local['cs'])) : '';
            $enNameNormalized = $local['en'] ? Str::slug(strtolower($local['en'])) : '';
            
            foreach ($this->availableGlobalKeywords as &$global) {
                $globalCsNormalized = $global['cs'] ? Str::slug(strtolower($global['cs'])) : '';
                $globalEnNormalized = $global['en'] ? Str::slug(strtolower($global['en'])) : '';
                
                $csSimilarity = 0;
                $enSimilarity = 0;
                similar_text($csNameNormalized, $globalCsNormalized, $csSimilarity);
                similar_text($enNameNormalized, $globalEnNormalized, $enSimilarity);
                
                $global['csSimilarity'] = round($csSimilarity, 1);
                $global['enSimilarity'] = round($enSimilarity, 1);
                $global['avgSimilarity'] = round(($csSimilarity + $enSimilarity) / 2, 1);
                
                // Check if categories match
                $categoryMatch = ($local['category_id'] && $global['category_id']) ? 
                    ($local['category_name'] === $global['category_name']) : false;
                $global['categoryMatch'] = $categoryMatch;
            }
            
            // Sort Global Keywords by Similarity and Category Match
            usort($this->availableGlobalKeywords, function($a, $b) {
                // 1. Prioritize starts-with matches over contains
                $prefix = strtolower(Str::slug($this->unmergedKeywords[array_search($this->selectedLocalKeyword, array_column($this->unmergedKeywords, 'id'))]['cs'] ?? ''));
            
                $aStarts = Str::startsWith(strtolower(Str::slug($a['cs'])), $prefix);
                $bStarts = Str::startsWith(strtolower(Str::slug($b['cs'])), $prefix);
            
                if ($aStarts && !$bStarts) return -1;
                if (!$aStarts && $bStarts) return 1;
            
                // 2. Prioritize category match
                if ($a['categoryMatch'] && !$b['categoryMatch']) return -1;
                if (!$a['categoryMatch'] && $b['categoryMatch']) return 1;
            
                // 3. Fallback to similarity
                return $b['avgSimilarity'] <=> $a['avgSimilarity'];
            });            
        }
        
        $this->isProcessing = false;
    }    

    public function selectGlobalKeyword($id)
    {
        $this->selectedGlobalKeyword = $id;
    }

    // Method to handle selection of keywords for direct merging
    public function selectKeywordForMerge($source, $id)
    {
        $this->isProcessing = true;
        
        try {
            // If first keyword not selected yet, select it
            if (!$this->selectedKeywordOne) {
                $this->selectedKeywordOne = [
                    'source' => $source,
                    'id' => $id
                ];
                
                $this->selectedKeywordOneDetails = null; // Explicitly set to null first
                $this->loadKeywordDetails('one');
                
                if (!$this->selectedKeywordOneDetails) {
                    throw new \Exception(__("Failed to load keyword details"));
                }
                
                session()->flash('info', __('First keyword selected. Please select a second keyword to merge with.'));
                
            } else if (!$this->selectedKeywordTwo) {
                // Don't allow selecting the same keyword
                if ($source === $this->selectedKeywordOne['source'] && (int)$id === (int)$this->selectedKeywordOne['id']) {
                    throw new \Exception(__("You cannot merge a keyword with itself. Please select a different keyword."));
                }
                
                $this->selectedKeywordTwo = [
                    'source' => $source,
                    'id' => $id
                ];
                
                $this->selectedKeywordTwoDetails = null; // Explicitly set to null first
                $this->loadKeywordDetails('two');
                
                if (!$this->selectedKeywordTwoDetails) {
                    throw new \Exception(__("Failed to load second keyword details"));
                }
                
                $this->openMergeTwoKeywords();
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            
            // Reset if there was an error
            $this->selectedKeywordOne = null;
            $this->selectedKeywordTwo = null;
            $this->selectedKeywordOneDetails = null;
            $this->selectedKeywordTwoDetails = null;
        } finally {
            $this->isProcessing = false;
        }
    }

    // Method to load keyword details
    public function loadKeywordDetails($position)
    {
        $selectedKeyword = $position === 'one' ? $this->selectedKeywordOne : $this->selectedKeywordTwo;
        $source = $selectedKeyword['source'];
        $id = $selectedKeyword['id'];
        
        try {
            // Get the tenant's table prefix dynamically
            $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
            $tenantPrefix = $tenant->table_prefix . '__';
            
            if ($source === 'local') {
                // Get keyword data from tenant's table
                $keyword = DB::table("{$tenantPrefix}keywords")->where('id', $id)->first();
                if (!$keyword) {
                    throw new \Exception(__("Local keyword not found"));
                }
                
                $nameArr = json_decode($keyword->name, true);
                
                // Get categories
                $categories = [];
                if ($keyword->keyword_category_id) {
                    $category = DB::table("{$tenantPrefix}keyword_categories")
                        ->where('id', $keyword->keyword_category_id)
                        ->first();
                    if ($category) {
                        $catNameArr = json_decode($category->name, true);
                        $categories[] = [
                            'id' => $category->id,
                            'name' => $catNameArr['cs'] ?? __('Unknown Category'),
                            'local' => true
                        ];
                    }
                }
                
                // Get letters
                $letters = [];
                $letterLinks = DB::table("{$tenantPrefix}keyword_letter")
                    ->where('keyword_id', $id)
                    ->get();
                
                foreach ($letterLinks as $link) {
                    $letter = DB::table("{$tenantPrefix}letters")
                        ->where('id', $link->letter_id)
                        ->first();
                    if ($letter) {
                        $letters[] = [
                            'id' => $letter->id,
                            'uuid' => $letter->uuid
                        ];
                    }
                }
                
                $details = [
                    'id' => $keyword->id,
                    'cs' => $nameArr['cs'] ?? __('No CS Name'),
                    'en' => $nameArr['en'] ?? __('No EN Name'),
                    'categories' => $categories,
                    'letters' => $letters,
                    'source' => 'local'
                ];
                
            } else {
                // Handle global keywords
                $keyword = DB::table("global_keywords")->where('id', $id)->first();
                if (!$keyword) {
                    throw new \Exception(__("Global keyword not found"));
                }
                
                $nameArr = json_decode($keyword->name, true);
                
                // Get categories
                $categories = [];
                if ($keyword->keyword_category_id) {
                    $category = DB::table("global_keyword_categories")
                        ->where('id', $keyword->keyword_category_id)
                        ->first();
                    if ($category) {
                        $catNameArr = json_decode($category->name, true);
                        $categories[] = [
                            'id' => $category->id,
                            'name' => $catNameArr['cs'] ?? __('Unknown Category'),
                            'local' => false
                        ];
                    }
                }
                
                // Get letters (from tenant's table)
                $letters = [];
                $letterLinks = DB::table("{$tenantPrefix}keyword_letter")
                    ->where('global_keyword_id', $id)
                    ->get();
                
                foreach ($letterLinks as $link) {
                    $letter = DB::table("{$tenantPrefix}letters")
                        ->where('id', $link->letter_id)
                        ->first();
                    if ($letter) {
                        $letters[] = [
                            'id' => $letter->id,
                            'uuid' => $letter->uuid
                        ];
                    }
                }
                
                $details = [
                    'id' => $keyword->id,
                    'cs' => $nameArr['cs'] ?? __('No CS Name'),
                    'en' => $nameArr['en'] ?? __('No EN Name'),
                    'categories' => $categories,
                    'letters' => $letters,
                    'source' => 'global'
                ];
            }
            
            // Set the details to the appropriate variable
            if ($position === 'one') {
                $this->selectedKeywordOneDetails = $details;
            } else {
                $this->selectedKeywordTwoDetails = $details;
            }
            
        } catch (\Exception $e) {
            Log::error("[loadKeywordDetails] Error: " . $e->getMessage());
            
            // Explicitly set to null
            if ($position === 'one') {
                $this->selectedKeywordOneDetails = null;
            } else {
                $this->selectedKeywordTwoDetails = null;
            }
            
            throw $e;  // Re-throw to be caught by the caller
        }
    }

    // Method to open the merge two keywords modal
    public function openMergeTwoKeywords()
    {
        if (!$this->selectedKeywordOneDetails || !$this->selectedKeywordTwoDetails) {
            session()->flash('error', __('Failed to load keyword details. Please try again.'));
            return;
        }
        
        $this->showMergeTwoKeywords = true;
    }

    // Method to close the merge two keywords modal and reset selections
    public function closeMergeTwoKeywords()
    {
        $this->showMergeTwoKeywords = false;
        $this->selectedKeywordOne = null;
        $this->selectedKeywordTwo = null;
        $this->selectedKeywordOneDetails = null;
        $this->selectedKeywordTwoDetails = null;
        $this->mergeOptions = [
            'mergeCategories' => true,
            'preferGlobalCategories' => true,
        ];
    }

    // Method to merge two keywords directly
    public function mergeTwoKeywords()
    {
        if (!$this->selectedKeywordOneDetails || !$this->selectedKeywordTwoDetails) {
            session()->flash('error', __('Missing keyword details. Please try again.'));
            return;
        }
    
        try {
            // Get the tenant's table prefix dynamically
            $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
            if (!$tenant || empty($tenant->table_prefix)) {
                session()->flash('error', __('Tenant prefix not found.'));
                return;
            }
            $tenantPrefix = $tenant->table_prefix . '__';
    
            // Begin transaction to ensure data consistency
            DB::beginTransaction();
    
            $keywordOne = $this->selectedKeywordOneDetails;
            $keywordTwo = $this->selectedKeywordTwoDetails;
    
            // Determine the target and source keywords
            $isTargetGlobal = false;
    
            if ($keywordOne['source'] === 'global' && $keywordTwo['source'] === 'local') {
                $targetKeyword = $keywordOne;
                $sourceKeyword = $keywordTwo;
                $isTargetGlobal = true;
            } elseif ($keywordOne['source'] === 'local' && $keywordTwo['source'] === 'global') {
                $targetKeyword = $keywordTwo;
                $sourceKeyword = $keywordOne;
                $isTargetGlobal = true;
            } else {
                $targetKeyword = $keywordOne;
                $sourceKeyword = $keywordTwo;
                $isTargetGlobal = ($targetKeyword['source'] === 'global');
            }
    
            // Create merged name object
            $mergedName = [
                'cs' => $targetKeyword['cs'],
                'en' => $targetKeyword['en'],
            ];
    
            // Prepare merged categories
            $targetCategories = [];
            $sourceCategories = [];
            
            foreach ($targetKeyword['categories'] as $category) {
                $targetCategories[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'local' => $category['local'] ?? ($targetKeyword['source'] === 'local')
                ];
            }
            
            foreach ($sourceKeyword['categories'] as $category) {
                $sourceCategories[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'local' => $category['local'] ?? ($sourceKeyword['source'] === 'local')
                ];
            }
    
            // Handle category merging based on options - IMPROVED LOGIC
            $finalCategoryId = null;
            $finalCategoryIsLocal = false;
            
            // Get all available categories if merging is enabled
            $allCategories = [];
            if ($this->mergeOptions['mergeCategories']) {
                $allCategories = array_merge($targetCategories, $sourceCategories);
            } else {
                // If not merging, only consider target categories
                $allCategories = $targetCategories;
            }
            
            if (empty($allCategories) && !empty($sourceCategories) && $this->mergeOptions['mergeCategories']) {
                // If target has no categories but source does and merging is enabled
                $allCategories = $sourceCategories;
            }
            
            // First handle the global category preference
            if ($this->mergeOptions['preferGlobalCategories']) {
                // Look for global categories first
                $globalCategories = array_filter($allCategories, function($cat) {
                    return isset($cat['local']) && !$cat['local'];
                });
                
                if (!empty($globalCategories)) {
                    // Use the first global category
                    $firstGlobal = reset($globalCategories);
                    $finalCategoryId = $firstGlobal['id'];
                    $finalCategoryIsLocal = false;
                    Log::info("[mergeTwoKeywords] Using global category: {$firstGlobal['name']} (ID: {$finalCategoryId})");
                } elseif (!empty($allCategories)) {
                    // No global categories, use first available
                    $firstCategory = reset($allCategories);
                    $finalCategoryId = $firstCategory['id'];
                    $finalCategoryIsLocal = $firstCategory['local'] ?? true;
                    Log::info("[mergeTwoKeywords] No global categories found, using: {$firstCategory['name']} (ID: {$finalCategoryId})");
                }
            } else {
                // Not preferring global, just use first available
                if (!empty($allCategories)) {
                    $firstCategory = reset($allCategories);
                    $finalCategoryId = $firstCategory['id'];
                    $finalCategoryIsLocal = $firstCategory['local'] ?? true;
                    Log::info("[mergeTwoKeywords] Using category: {$firstCategory['name']} (ID: {$finalCategoryId})");
                }
            }
    
            // Handle different merging scenarios
            if ($isTargetGlobal) {
                // Target is a global keyword
                $updateData = ['name' => json_encode($mergedName)];
                
                // Add category ID if we have a global category
                if ($finalCategoryId && !$finalCategoryIsLocal) {
                    $updateData['keyword_category_id'] = $finalCategoryId;
                }
                
                DB::table("global_keywords")
                    ->where('id', $targetKeyword['id'])
                    ->update($updateData);
    
                // SOURCE IS LOCAL: Transfer letters from local to global
                if ($sourceKeyword['source'] === 'local') {
                    // FIXED: Check for duplicate letter links before transferring
                    foreach ($sourceKeyword['letters'] as $letter) {
                        // Check if this letter already has the target global keyword
                        $existingLink = DB::table("{$tenantPrefix}keyword_letter")
                            ->where('letter_id', $letter['id'])
                            ->where('global_keyword_id', $targetKeyword['id'])
                            ->first();
                        
                        if ($existingLink) {
                            // If already linked to global, simply delete the local link
                            DB::table("{$tenantPrefix}keyword_letter")
                                ->where('letter_id', $letter['id'])
                                ->where('keyword_id', $sourceKeyword['id'])
                                ->delete();
                                
                            Log::info("[mergeTwoKeywords] Deleted duplicate link for letter {$letter['id']} - already had global keyword {$targetKeyword['id']}");
                        } else {
                            // Otherwise, update the local link to point to global
                            DB::table("{$tenantPrefix}keyword_letter")
                                ->where('letter_id', $letter['id'])
                                ->where('keyword_id', $sourceKeyword['id'])
                                ->update([
                                    'global_keyword_id' => $targetKeyword['id'],
                                    'keyword_id' => null,
                                ]);
                                
                            Log::info("[mergeTwoKeywords] Updated letter {$letter['id']} link from local {$sourceKeyword['id']} to global {$targetKeyword['id']}");
                        }
                    }

                    // Delete the local keyword
                    DB::table("{$tenantPrefix}keywords")->where('id', $sourceKeyword['id'])->delete();
                    
                    // Check if the local category should be removed (if orphaned)
                    if (!empty($sourceCategories) && $this->mergeOptions['mergeCategories']) {
                        $this->cleanUpOrphanedCategories($tenantPrefix);
                    }
                } else {
                    // BOTH ARE GLOBAL: merge letters
                    foreach ($sourceKeyword['letters'] as $letter) {
                        // Check if letter already has the target global keyword
                        $existingLink = DB::table("{$tenantPrefix}keyword_letter")
                            ->where('letter_id', $letter['id'])
                            ->where('global_keyword_id', $targetKeyword['id'])
                            ->first();
                            
                        if (!$existingLink) {
                            // Only update if not already linked to target
                            DB::table("{$tenantPrefix}keyword_letter")
                                ->where('letter_id', $letter['id'])
                                ->where('global_keyword_id', $sourceKeyword['id'])
                                ->update([
                                    'global_keyword_id' => $targetKeyword['id'],
                                ]);
                            
                            Log::info("[mergeTwoKeywords] Updated letter {$letter['id']} from global {$sourceKeyword['id']} to global {$targetKeyword['id']}");
                        } else {
                            // Letter already has target global, remove link to source global
                            DB::table("{$tenantPrefix}keyword_letter")
                                ->where('letter_id', $letter['id'])
                                ->where('global_keyword_id', $sourceKeyword['id'])
                                ->delete();
                                
                            Log::info("[mergeTwoKeywords] Removed duplicate global keyword {$sourceKeyword['id']} from letter {$letter['id']}");
                        }
                    }
    
                    // Mark source global keyword as merged
                    DB::table("global_keywords")
                        ->where('id', $sourceKeyword['id'])
                        ->update([
                            'merged_into' => $targetKeyword['id'],
                            'name' => json_encode([
                                'cs' => 'Merged: ' . $sourceKeyword['cs'],
                                'en' => 'Merged: ' . $sourceKeyword['en']
                            ]),
                            'keyword_category_id' => null, // Remove category from merged keyword
                        ]);
                }
            } else {
                // Both are local keywords
                DB::table("{$tenantPrefix}keywords")
                    ->where('id', $targetKeyword['id'])
                    ->update([
                        'name' => json_encode($mergedName),
                        'keyword_category_id' => $finalCategoryId,
                    ]);
    
                // FIXED: Handle letter merging with duplicate checks for local-to-local merges
                foreach ($sourceKeyword['letters'] as $letter) {
                    // Check if letter already has the target local keyword
                    $existingLink = DB::table("{$tenantPrefix}keyword_letter")
                        ->where('letter_id', $letter['id'])
                        ->where('keyword_id', $targetKeyword['id'])
                        ->first();
                        
                    if (!$existingLink) {
                        // If not already linked, update the link
                        DB::table("{$tenantPrefix}keyword_letter")
                            ->where('letter_id', $letter['id'])
                            ->where('keyword_id', $sourceKeyword['id'])
                            ->update([
                                'keyword_id' => $targetKeyword['id'],
                            ]);
                            
                        Log::info("[mergeTwoKeywords] Updated letter {$letter['id']} from local {$sourceKeyword['id']} to local {$targetKeyword['id']}");
                    } else {
                        // If already linked, just delete the source link
                        DB::table("{$tenantPrefix}keyword_letter")
                            ->where('letter_id', $letter['id'])
                            ->where('keyword_id', $sourceKeyword['id'])
                            ->delete();
                            
                        Log::info("[mergeTwoKeywords] Removed duplicate local keyword {$sourceKeyword['id']} from letter {$letter['id']}");
                    }
                }
    
                // Delete the source local keyword
                DB::table("{$tenantPrefix}keywords")->where('id', $sourceKeyword['id'])->delete();
                
                // Clean up orphaned categories if categories were merged
                if ($this->mergeOptions['mergeCategories']) {
                    $this->cleanUpOrphanedCategories($tenantPrefix);
                }
            }
    
            // Commit the transaction
            DB::commit();
    
            // Show success message
            session()->flash('success', __('Keywords successfully merged.'));
    
            // Clear selection arrays
            $this->selectedKeywords = array_filter($this->selectedKeywords, function($jsonKeyword) use ($sourceKeyword) {
                $keyword = json_decode($jsonKeyword, true);
                return !($keyword['source'] === $sourceKeyword['source'] && $keyword['id'] == $sourceKeyword['id']);
            });
            
            // Close the modal and reset selections
            $this->closeMergeTwoKeywords();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[mergeTwoKeywords] Error: " . $e->getMessage());
            session()->flash('error', __('Error merging keywords: ') . $e->getMessage());
        }
    }
   
    public function performManualMerge()
    {
        if (!$this->selectedLocalKeyword || !$this->selectedGlobalKeyword) {
            session()->flash('error', __('Select both keywords for merging.'));
            return;
        }
    
        $tenantPrefix = tenancy()->tenant->table_prefix . '__';
    
        DB::beginTransaction();
    
        try {
            // Fetch local and global keywords
            $localKeyword = DB::table("{$tenantPrefix}keywords")->find($this->selectedLocalKeyword);
            $globalKeyword = DB::table('global_keywords')->find($this->selectedGlobalKeyword);
    
            if (!$localKeyword || !$globalKeyword) {
                throw new \Exception(__('Selected keywords not found.'));
            }
    
            // Get names from both keywords
            $localNameArr = json_decode($localKeyword->name, true);
            $globalNameArr = json_decode($globalKeyword->name, true);
            
            // Create a merged name object, keeping both language variants
            // Strategy: Prefer global name, but use local if global is missing for a language
            $mergedName = [];
            foreach (['cs', 'en'] as $lang) {
                if (!empty($globalNameArr[$lang])) {
                    $mergedName[$lang] = $globalNameArr[$lang];
                } elseif (!empty($localNameArr[$lang])) {
                    $mergedName[$lang] = $localNameArr[$lang]; 
                } else {
                    $mergedName[$lang] = ""; // Fallback if both are empty
                }
            }
    
            // Handle category merging
            $finalGlobalCategoryId = $globalKeyword->keyword_category_id; // Default to global category
            
            // Only process category merging if the option is enabled
            if ($this->mergeOptions['mergeCategories']) {
                // If global keyword already has a category, use that (as that's what merging means)
                if ($globalKeyword->keyword_category_id) {
                    $finalGlobalCategoryId = $globalKeyword->keyword_category_id;
                    Log::info("[performManualMerge] Using existing global category ID: {$finalGlobalCategoryId}");
                }
                // If global has no category but local does, find or create appropriate global category
                else if ($localKeyword->keyword_category_id) {
                    $localCategory = DB::table("{$tenantPrefix}keyword_categories")
                        ->find($localKeyword->keyword_category_id);
                    
                    if ($localCategory) {
                        $localCatNameArr = json_decode($localCategory->name, true);
                        
                        // Look for global category with matching name (by either language)
                        $matchingGlobalCategory = DB::table("global_keyword_categories")
                            ->where(function($query) use ($localCatNameArr) {
                                if (!empty($localCatNameArr['cs'])) {
                                    $query->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) = ?", 
                                        [strtolower($localCatNameArr['cs'])]);
                                }
                                if (!empty($localCatNameArr['en'])) {
                                    $query->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) = ?", 
                                        [strtolower($localCatNameArr['en'])]);
                                }
                            })
                            ->first();
                        
                        if ($matchingGlobalCategory) {
                            // Use the matching global category
                            $finalGlobalCategoryId = $matchingGlobalCategory->id;
                            Log::info("[performManualMerge] Using matching global category ID: {$finalGlobalCategoryId}");
                        } else {
                            // Create a new global category only if global has no category
                            $finalGlobalCategoryId = DB::table("global_keyword_categories")->insertGetId([
                                'name' => json_encode($localCatNameArr),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            Log::info("[performManualMerge] Created new global category ID: {$finalGlobalCategoryId}");
                        }
                    }
                }
            } else {
                // If merging categories is disabled, just keep the global category as is
                Log::info("[performManualMerge] Category merging disabled, keeping global category");
            }
    
            // Update global keyword with merged data
            DB::table('global_keywords')
                ->where('id', $globalKeyword->id)
                ->update([
                    'name' => json_encode($mergedName),
                    'keyword_category_id' => $finalGlobalCategoryId,
                    'updated_at' => now(),
                ]);
    
            // Move attached letters from local to global keyword
            DB::table("{$tenantPrefix}keyword_letter")
                ->where('keyword_id', $localKeyword->id)
                ->update([
                    'global_keyword_id' => $globalKeyword->id, 
                    'keyword_id' => null  // Nullify the local keyword ID
                ]);
    
            // Delete local keyword
            DB::table("{$tenantPrefix}keywords")->where('id', $localKeyword->id)->delete();
    
            // Clean up orphaned categories
            $this->cleanUpOrphanedCategories($tenantPrefix);
    
            DB::commit();
    
            session()->flash('success', __('Keyword successfully merged.'));
            $this->reset(['selectedLocalKeyword', 'selectedGlobalKeyword', 'showManualMerge']);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[performManualMerge] Error: " . $e->getMessage());
            session()->flash('error', __('Error merging keywords: ') . $e->getMessage());
        }
    }

    public function closeManualMerge()
    {
        $this->showManualMerge = false;
        $this->reset('localKeywordSearch', 'globalKeywordSearch'); // Reset input values
    }

    private function validatePivotUpdates($tenantPrefix, $pivotRecords)
    {
        $totalRecords = count($pivotRecords);
        $validUpdates = 0;
        
        foreach ($pivotRecords as $record) {
            // Check if the global keyword exists
            $globalExists = DB::table("global_keywords")
                ->where('id', $record->global_keyword_id)
                ->exists();
                
            if ($globalExists) {
                $validUpdates++;
            } else {
                Log::warning("[validatePivotUpdates] Missing global keyword ID: {$record->global_keyword_id} for letter {$record->letter_id}");
            }
        }
        
        $validationRate = $totalRecords > 0 ? ($validUpdates / $totalRecords) * 100 : 0;
        
        // If more than 5% of updates are invalid, something might be wrong
        if ($validationRate < 95) {
            Log::error("[validatePivotUpdates] High failure rate: {$validationRate}% valid updates out of {$totalRecords} records");
            throw new \Exception(__("Validation failed: Too many invalid global keyword references."));
        }
        
        return true;
    }

    /**
     * Merge tenant keywords into global ones while handling categories based on merge options.
     */
    public function mergeAll()
    {
        $this->isProcessing = true;
        Log::info('[mergeAll] Button clicked! Fetching tenant prefix...');
    
        // Get the tenant's table prefix dynamically.
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            Log::error("[mergeAll] Failed to get tenant prefix!");
            session()->flash('error', __('Tenant prefix not found.'));
            $this->isProcessing = false;
            return;
        }
    
        $tenantPrefix = $tenant->table_prefix . '__';
        Log::info("[mergeAll] Using Tenant Prefix: $tenantPrefix");
    
        // Retrieve all tenant (local) keywords.
        $localKeywords = DB::table("{$tenantPrefix}keywords")->get();
        if ($localKeywords->isEmpty()) {
            Log::warning("[mergeAll] No local keywords found.");
            session()->flash('warning', __('No local keywords to merge.'));
            $this->isProcessing = false;
            return;
        }
    
        // Retrieve all global keywords for matching.
        $globalKeywords = DB::table("global_keywords")->get();
        $merged = 0;
        $skipped = 0;
    
        DB::beginTransaction();
        try {
            foreach ($localKeywords as $local) {
                $localNameArr = json_decode($local->name, true);
                $csName = strtolower(trim($localNameArr['cs'] ?? ''));
                $enName = strtolower(trim($localNameArr['en'] ?? ''));
                Log::info("[mergeAll] Checking Local Keyword: CS='$csName', EN='$enName'");
    
                // Normalize available names.
                $csNameNormalized = $csName ? Str::slug($csName) : '';
                $enNameNormalized = $enName ? Str::slug($enName) : '';
    
                // Find best matching global keyword.
                $globalMatch = null;
                $bestSimilarity = 0;
    
                foreach ($globalKeywords as $global) {
                    $globalNameArr = json_decode($global->name, true);
                    $globalCsName = strtolower(trim($globalNameArr['cs'] ?? ''));
                    $globalEnName = strtolower(trim($globalNameArr['en'] ?? ''));
    
                    // Remove any "global" prefix if present.
                    $globalCsStripped = preg_replace('/^global\s+/i', '', $globalCsName);
                    $globalEnStripped = preg_replace('/^global\s+/i', '', $globalEnName);
    
                    $globalCsNormalized = $globalCsStripped ? Str::slug($globalCsStripped) : '';
                    $globalEnNormalized = $globalEnStripped ? Str::slug($globalEnStripped) : '';
    
                    $csSimilarity = 0;
                    $enSimilarity = 0;
                    similar_text($csNameNormalized, $globalCsNormalized, $csSimilarity);
                    similar_text($enNameNormalized, $globalEnNormalized, $enSimilarity);
    
                    // Check merge criteria with threshold
                    $csMatch = $csSimilarity > $this->similarityThreshold;
                    $enMatch = $enSimilarity > $this->similarityThreshold;
    
                    $csEmpty = empty($csNameNormalized) || empty($globalCsNormalized);
                    $enEmpty = empty($enNameNormalized) || empty($globalEnNormalized);
    
                    // Calculate average similarity for ranking
                    $avgSimilarity = ($csSimilarity + $enSimilarity) / 2;
    
                    // Only merge if either language meets the threshold and it's the best match so far
                    if ((($csMatch && $enMatch) || 
                        ($csMatch && $enEmpty) || 
                        ($enMatch && $csEmpty) ||
                        (($csMatch || $enMatch) && !$csEmpty && !$enEmpty)) && 
                        $avgSimilarity > $bestSimilarity) {
                        $globalMatch = $global;
                        $bestSimilarity = $avgSimilarity;
                    }
                }
    
                if ($globalMatch) {
                    Log::info("[mergeAll] Merging Local Keyword '{$csName}' -> Global Keyword ID {$globalMatch->id}");
    
                    // Handle categories based on merge options (FIXED PART)
                    if ($this->mergeOptions['mergeCategories']) {
                        // Get the local category if it exists
                        $localCategoryId = $local->keyword_category_id;
                        $globalCategoryId = $globalMatch->keyword_category_id;
                        
                        $finalCategoryId = null;
                        
                        if ($this->mergeOptions['preferGlobalCategories']) {
                            // FIXED: Prefer global category if available
                            if ($globalCategoryId) {
                                $finalCategoryId = $globalCategoryId;
                                Log::info("[mergeAll] Using global category ID: {$globalCategoryId}");
                            } 
                            // FIXED: If no global category and preferGlobalCategories=true, keep it uncategorized
                            else {
                                // Don't create a global category from the local one
                                // Just keep it null (uncategorized)
                                $finalCategoryId = null;
                                Log::info("[mergeAll] No global category and preferGlobalCategories=true, keeping global keyword uncategorized");
                            }
                        } 
                        // Only if NOT preferring global categories
                        else if ($localCategoryId) {
                            // Not preferring global but we need a global category ID for global keyword
                            // Try to find a matching global category or create one
                            $localCategory = DB::table("{$tenantPrefix}keyword_categories")
                                ->where('id', $localCategoryId)
                                ->first();
                                
                            if ($localCategory) {
                                $localCatNameArr = json_decode($localCategory->name, true);
                                
                                // Check if a similar global category exists
                                $similarGlobalCat = DB::table("global_keyword_categories")
                                    ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) = ?", 
                                        [strtolower($localCatNameArr['cs'] ?? '')])
                                    ->first();
                                    
                                if ($similarGlobalCat) {
                                    // Use the similar global category
                                    $finalCategoryId = $similarGlobalCat->id;
                                    Log::info("[mergeAll] Using similar global category ID: {$finalCategoryId}");
                                } else {
                                    // Create a new global category if none exists
                                    $newGlobalCatId = DB::table("global_keyword_categories")->insertGetId([
                                        'name' => json_encode($localCatNameArr),
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                    
                                    $finalCategoryId = $newGlobalCatId;
                                    Log::info("[mergeAll] Created new global category for local category: {$newGlobalCatId}");
                                }
                            }
                        } else if ($globalCategoryId) {
                            // Just use global category if available
                            $finalCategoryId = $globalCategoryId;
                        }
                        
                        // Update the global keyword's category if needed
                        if ($finalCategoryId != $globalMatch->keyword_category_id) {
                            DB::table("global_keywords")
                                ->where('id', $globalMatch->id)
                                ->update([
                                    'keyword_category_id' => $finalCategoryId
                                ]);
                            Log::info("[mergeAll] Updated global keyword {$globalMatch->id} with category ID " . 
                                ($finalCategoryId ? $finalCategoryId : "NULL (uncategorized)"));
                        }
                    }
    
                    // Get letters linked to this local keyword
                    $linkedLetters = DB::table("{$tenantPrefix}keyword_letter")
                        ->where('keyword_id', $local->id)
                        ->get();
    
                    foreach ($linkedLetters as $letter) {
                        // Check if this letter already has a link to the global keyword
                        $existingLink = DB::table("{$tenantPrefix}keyword_letter")
                            ->where('letter_id', $letter->letter_id)
                            ->where('global_keyword_id', $globalMatch->id)
                            ->first();
                            
                        if ($existingLink) {
                            // If already linked to global, delete the local link
                            DB::table("{$tenantPrefix}keyword_letter")
                                ->where('letter_id', $letter->letter_id)
                                ->where('keyword_id', $local->id)
                                ->delete();
                                
                            Log::info("[mergeAll] Deleted duplicate link for letter {$letter->letter_id} - already had global keyword {$globalMatch->id}");
                        } else {
                            // Update the letter-keyword link to point to global keyword
                            DB::table("{$tenantPrefix}keyword_letter")
                                ->where('letter_id', $letter->letter_id)
                                ->where('keyword_id', $local->id)
                                ->update([
                                    'global_keyword_id' => $globalMatch->id,
                                    'keyword_id' => null,
                                ]);
                            Log::info("[mergeAll] Updated letter {$letter->letter_id} with global_keyword_id {$globalMatch->id}");
                        }
                    }
    
                    // Delete the local keyword record.
                    DB::table("{$tenantPrefix}keywords")->where('id', $local->id)->delete();
                    $merged++;
                } else {
                    Log::warning("[mergeAll] No global match found for '{$csName}' ({$enName}). Skipping.");
                    $skipped++;
                }
            }
    
            // Validate pivot records before committing
            $allUpdatedPivots = DB::table("{$tenantPrefix}keyword_letter")
                ->whereNotNull('global_keyword_id')
                ->whereNull('keyword_id')
                ->get();
    
            $this->validatePivotUpdates($tenantPrefix, $allUpdatedPivots);
    
            // Clean up orphaned categories
            $this->cleanUpOrphanedCategories($tenantPrefix);
    
            DB::commit();
            
            if ($merged > 0) {
                session()->flash('success', "$merged " . __('keywords successfully merged!') . 
                    ($skipped > 0 ? " ($skipped " . __('keywords skipped') . ")" : ""));
            } else if ($skipped > 0) {
                session()->flash('warning', __('No keywords merged') . " ($skipped " . __('keywords skipped') . ")");
            }
            
            Log::info("[mergeAll] Merge completed. Total merged keywords: $merged, skipped: $skipped");
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[mergeAll] Error during merge: " . $e->getMessage());
            session()->flash('error', __('Error during merge') . ": " . $e->getMessage());
        }
    
        $this->isProcessing = false;
    }
    
    /**
     * Load keywords for each category to be displayed in tooltips
     */
    private function loadCategoryKeywords($tenantPrefix) 
    {
        // Get all local categories that are used in the preview
        $categoryIds = array_unique(array_filter(array_column($this->previewData, 'localCategoryId')));
        
        if (empty($categoryIds)) {
            return [];
        }
        
        $result = [];
        
        // For each category, fetch up to 10 keywords to display in the tooltip
        foreach ($categoryIds as $categoryId) {
            $keywords = DB::table("{$tenantPrefix}keywords")
                ->where('keyword_category_id', $categoryId)
                ->limit(10) // Limit to prevent too many entries
                ->get();
                
            $result[$categoryId] = [];
            
            foreach ($keywords as $kw) {
                $nameArr = json_decode($kw->name, true);
                $result[$categoryId][] = [
                    'id' => $kw->id,
                    'cs' => $nameArr['cs'] ?? __('hiko.no_cs_name'),
                    'en' => $nameArr['en'] ?? __('hiko.no_en_name'),
                ];
            }
        }
        
        return $result;
    }
}