<?php

namespace App\Livewire;

use App\Models\Profession;
use App\Models\GlobalProfession;
use App\Models\ProfessionCategory;
use App\Models\GlobalProfessionCategory;
use App\Models\Identity;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\ProcessProfessionBatch;

class ProfessionsTable extends Component
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
    public $selectedLocalProfession = null;
    public $selectedGlobalProfession = null;
    public $showMergeTwoProfessions = false;
    
    // Data for previews and manual merging
    public $previewData = [];
    public $unmergedProfessions = [];
    public $selectedProfessionOne = null; // For direct profession merging
    public $selectedProfessionTwo = null; // For direct profession merging
    
    // Merge options for all merge types
    public $mergeOptions = [
        'mergeCategories' => true,
        'mergeIdentities' => true,
        'preferGlobalCategories' => true, // Prefer global categories by default when merging
    ];
    
    public $availableGlobalProfessions = [];
    public $selectedProfessionOneDetails = null;
    public $selectedProfessionTwoDetails = null;
    public $similarityThreshold = 90; // Default similarity threshold
    public $mergeStats = [
        'total' => 0,
        'merged' => 0,
        'skipped' => 0,
    ];
    
    // Selection states for bulk operations
    public $selectedProfessions = [];
    public $selectAll = false;
    public $localProfessionSearch = '';
    public $globalProfessionSearch = '';

    public function mount()
    {
        // Handle session messages
        $this->handleSessionMessages();
    }

    // Dynamically update the preview when threshold changes
    public function updatedSimilarityThreshold()
    {
        $this->previewData = $this->generatePreviewData();
        $this->sortPreviewByBestMatch(); // Ensure sorting by best match
    }
    
    public function sortPreviewByBestMatch()
    {
        usort($this->previewData, function ($a, $b) {
            return $b['csSimilarity'] + $b['enSimilarity'] <=> $a['csSimilarity'] + $a['enSimilarity'];
        });
    } 

    private function handleSessionMessages()
    {
        // Messages from session and convert to alerts
        if (session()->has('success')) {
            $this->dispatch('alert', [
                'type' => 'success',
                'message' => session('success')
            ]);
        } elseif (session()->has('error')) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => session('error')
            ]);
        } elseif (session()->has('warning')) {
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => session('warning')
            ]);
        } elseif (session()->has('info')) {
            $this->dispatch('alert', [
                'type' => 'info',
                'message' => session('info')
            ]);
        }
    }

    public function search()
    {
        $this->resetPage('professionsPage');
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
        
        // Reset selection states
        $this->selectedProfessions = [];
        $this->selectAll = false;
    }

    public function render()
    {
        // Get professions for the main table
        $professions = $this->findProfessions();
    
        // Store the unfiltered data separately to avoid affecting the main render cycle
        $unmergedProfessionsToDisplay = $this->unmergedProfessions;
        $globalProfessionsToDisplay = $this->availableGlobalProfessions;
        
        // Filter unmerged professions by search term if specified (for modal only)
        if ($this->localProfessionSearch && !empty($unmergedProfessionsToDisplay)) {
            $search = strtolower($this->localProfessionSearch);
            $unmergedProfessionsToDisplay = array_filter($unmergedProfessionsToDisplay, function($p) use ($search) {
                return strpos(strtolower($p['cs']), $search) !== false 
                    || strpos(strtolower($p['en']), $search) !== false;
            });
        }
        
        // Filter global professions by search term if specified (for modal only)
        if ($this->globalProfessionSearch && !empty($globalProfessionsToDisplay)) {
            $search = strtolower($this->globalProfessionSearch);
            $globalProfessionsToDisplay = array_filter($globalProfessionsToDisplay, function($p) use ($search) {
                return strpos(strtolower($p['cs']), $search) !== false 
                    || strpos(strtolower($p['en']), $search) !== false;
            });
        }
    
        return view('livewire.professions-table', [
            'tableData'  => $this->formatTableData($professions),
            'pagination' => $professions,
            'unmergedProfessionsToDisplay' => $unmergedProfessionsToDisplay,
            'globalProfessionsToDisplay' => $globalProfessionsToDisplay
        ]);
    }

    protected function findProfessions(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $perPage = 10;
    
        $tenantProfessionsQuery = $this->getTenantProfessionsQuery();
        $globalProfessionsQuery = $this->getGlobalProfessionsQuery();
    
        $query = match ($filters['source']) {
            'local'  => $tenantProfessionsQuery,
            'global' => $globalProfessionsQuery,
            default  => $this->mergeQueries($tenantProfessionsQuery, $globalProfessionsQuery),
        };
    
        if (in_array($filters['order'], ['cs', 'en'])) {
            $orderColumn = "CONVERT(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) USING utf8mb4) COLLATE utf8mb4_unicode_ci";
            $query->orderByRaw($orderColumn);
        }
    
        return $query->paginate($perPage, ['*'], 'professionsPage');
    }

    protected function mergeQueries($tenantProfessionsQuery, $globalProfessionsQuery): Builder
    {
        $filters = $this->filters;

        $tenantBase = $tenantProfessionsQuery->toBase();
        $globalBase = $globalProfessionsQuery->toBase();
    
        $tenantSql = $tenantBase->toSql();
        $globalSql = $globalBase->toSql();
    
        $unionSql = "(
            SELECT id, profession_category_id, name, 'local' AS source FROM ({$tenantSql}) AS local_professions
            UNION ALL
            SELECT id, profession_category_id, name, 'global' AS source FROM ({$globalSql}) AS global_professions
        ) AS combined_professions";
    
        $unionQuery = DB::table(DB::raw($unionSql))
            ->mergeBindings($tenantBase)
            ->mergeBindings($globalBase);
    
        $sortedSql = "(
            SELECT *, ROW_NUMBER() OVER (
                ORDER BY CONVERT(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) USING utf8mb4) COLLATE utf8mb4_unicode_ci
            ) AS sort_index
            FROM ({$unionQuery->toSql()}) AS sorted_professions
        ) AS final_professions";
    
        $sortedQuery = DB::table(DB::raw($sortedSql))
            ->mergeBindings($unionQuery)
            ->select([
                'id',
                'profession_category_id',
                'name',
                'source',
            ])
            ->orderBy('sort_index');
    
        return Profession::query()->from(DB::raw("({$sortedQuery->toSql()}) AS fully_sorted_professions"))
            ->mergeBindings($sortedQuery);
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
    
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
            $tenantProfessions->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%{$csFilter}%"]);
        }
    
        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
            $tenantProfessions->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$enFilter}%"]);
        }
    
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

        $globalProfessions = GlobalProfession::with('profession_category')
            ->select(
                'id',
                'name',
                'profession_category_id',
                DB::raw("'global' AS source")
            );
    
        if (!empty($filters['cs'])) {
            $globalProfessions->whereRaw(
                "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"cs\"'))) LIKE ?",
                ["%{$filters['cs']}%"]
            );
        }
    
        if (!empty($filters['en'])) {
            $globalProfessions->whereRaw(
                "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) LIKE ?",
                ["%{$filters['en']}%"]
            );
        }
    
        if (!empty($filters['category'])) {
            $globalProfessions->whereHas('profession_category', function ($query) use ($filters) {
                $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%{$filters['category']}%"]);
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
            'rows'   => $data->map(function ($pf) {
                $profession = $pf->source === 'local'
                ? Profession::with('profession_category')->find($pf->id)
                : GlobalProfession::with('profession_category')->find($pf->id);            
        
                if (!$profession) {
                    return null; // Skip if profession not found
                }
        
                $csName = $profession->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $profession->getTranslation('name', 'en') ?? 'No EN name';
                $sourceLabel = $pf->source === 'local'
                    ? "<span class='inline-block text-blue-600 bg-blue-100 border border-blue-200 text-xs uppercase px-2 py-1 rounded-full font-medium'>" . __('hiko.local') . "</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 border border-red-200 text-xs uppercase px-2 py-1 rounded-full font-medium'>" . __('hiko.global') . "</span>";
        
                $categoryDisplay = $profession->profession_category
                    ? $profession->profession_category->getTranslation('name', 'cs') ?? ''
                    : "<span class='text-red-600'>" . __('hiko.no_attached_category') . "</span>";
        
                $editLink = [
                    'label' => __('hiko.edit'),
                    'link'  => $pf->source === 'local'
                        ? route('professions.edit', $pf->id)
                        : (auth()->user()->can('manage-users')
                            ? route('global.professions.edit', $pf->id)
                            : '#'),
                    'disabled' => $pf->source === 'global' && !auth()->user()->can('manage-users'),
                    'id' => $pf->id,
                    'source' => $pf->source
                ];
        
                $row = auth()->user()->cannot('manage-metadata') ? [] : [$editLink];
                $row[] = ['label' => $sourceLabel, 'source' => $pf->source];
                $row = array_merge($row, [
                    ['label' => $csName],
                    ['label' => $enName],
                    ['label' => $categoryDisplay],
                ]);
        
                return $row;
            })->filter()->toArray(),
        ];
    }
    
    // Toggle select all professions
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectAllProfessions();
        } else {
            $this->selectedProfessions = [];
        }
    }
    
    // Select all professions in the current view
    protected function selectAllProfessions()
    {
        $professions = $this->findProfessions();
        
        $this->selectedProfessions = [];
        foreach ($professions as $pf) {
            $this->selectedProfessions[] = json_encode(['source' => $pf->source, 'id' => $pf->id]);
        }
    }
    
    // Deselect all professions
    public function deselectAll()
    {
        $this->selectedProfessions = [];
        $this->selectAll = false;
    }
    
    // Merge pair of selected professions
    public function mergePairSelected()
    {
        $this->isProcessing = true;
        
        if (count($this->selectedProfessions) !== 2) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('Please select exactly two professions to merge')
            ]);
            $this->isProcessing = false;
            return;
        }
        
        // Get the two selected professions
        $profOne = json_decode($this->selectedProfessions[0], true);
        $profTwo = json_decode($this->selectedProfessions[1], true);
        
        // Set them as the professions to merge
        $this->selectedProfessionOne = $profOne;
        $this->selectedProfessionTwo = $profTwo;
        
        try {
            // Load profession details
            $this->loadProfessionDetails('one');
            $this->loadProfessionDetails('two');
            
            // Open the merge modal
            $this->openMergeTwoProfessions();
            $this->dispatch('alert', [
                'type' => 'info',
                'message' => __('Professions loaded successfully. Configure merge options below.')
            ]);
        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('Error loading profession details: ') . $e->getMessage()
            ]);
            
            // Reset selections if there was an error
            $this->selectedProfessionOne = null;
            $this->selectedProfessionTwo = null;
        } finally {
            $this->isProcessing = false;
        }
    }
    
    // Preview merge for selected professions
    public function previewMergeSelected()
    {
        if (empty($this->selectedProfessions)) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('Please select at least one profession to preview merge')
            ]);
            return;
        }
        
        $this->isProcessing = true;
        
        // Filter preview data to only include selected professions
        $selectedIds = [];
        foreach ($this->selectedProfessions as $jsonProf) {
            $prof = json_decode($jsonProf, true);
            if ($prof['source'] === 'local') {
                $selectedIds[] = $prof['id'];
            }
        }
        
        // Generate preview data for all professions, then filter
        $allPreviewData = $this->generatePreviewData();
        
        // Filter to only include selected local professions
        $this->previewData = array_filter($allPreviewData, function($item) use ($selectedIds) {
            return in_array($item['localId'], $selectedIds);
        });
        
        // Sort preview data by willMerge first, then by similarity
        usort($this->previewData, function($a, $b) {
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
        
        // Update merge stats for the filtered selection
        $this->mergeStats = [
            'total' => count($this->previewData),
            'merged' => count(array_filter($this->previewData, function($item) { return $item['willMerge']; })),
            'skipped' => count(array_filter($this->previewData, function($item) { return !$item['willMerge']; })),
        ];
        
        $this->showPreview = true;
        $this->isProcessing = false;
    }
    
    // Auto-merge selected professions
    public function mergeAllSelected()
    {
        if (empty($this->selectedProfessions)) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('Please select at least one profession to merge')
            ]);
            return;
        }
        
        // Check if any global professions are selected
        $hasGlobalProfessions = false;
        foreach ($this->selectedProfessions as $jsonProf) {
            $prof = json_decode($jsonProf, true);
            if ($prof['source'] === 'global') {
                $hasGlobalProfessions = true;
                break;
            }
        }
        
        if ($hasGlobalProfessions) {
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => __('Global professions cannot be merged in bulk. Please use Direct Merge for global professions.')
            ]);
            return;
        }
        
        $this->isProcessing = true;
        Log::info('[mergeAllSelected] Button clicked! Fetching tenant prefix...');
    
        // Get the tenant's table prefix dynamically.
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            Log::error("[mergeAllSelected] Failed to get tenant prefix!");
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('Tenant prefix not found.')
            ]);
            $this->isProcessing = false;
            return;
        }
    
        $tenantPrefix = $tenant->table_prefix . '__';
        Log::info("[mergeAllSelected] Using Tenant Prefix: $tenantPrefix");
    
        // Get all global professions for matching.
        $globalProfessions = DB::table("global_professions")->get();
        
        // Get selected local professions
        $selectedLocalIds = [];
        foreach ($this->selectedProfessions as $jsonProf) {
            $prof = json_decode($jsonProf, true);
            if ($prof['source'] === 'local') {
                $selectedLocalIds[] = $prof['id'];
            }
        }
        
        if (empty($selectedLocalIds)) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('No local professions selected for merge')
            ]);
            $this->isProcessing = false;
            return;
        }
        
        // Get selected local professions
        $localProfessions = DB::table("{$tenantPrefix}professions")
            ->whereIn('id', $selectedLocalIds)
            ->get();
        
        $merged = 0;
        $skipped = 0;
    
        DB::beginTransaction();
        try {
            foreach ($localProfessions as $local) {
                $localNameArr = json_decode($local->name, true);
                $csName = strtolower(trim($localNameArr['cs'] ?? ''));
                $enName = strtolower(trim($localNameArr['en'] ?? ''));
                Log::info("[mergeAllSelected] Checking Local Profession: CS='$csName', EN='$enName'");
    
                // Normalize available names.
                $csNameNormalized = $csName ? Str::slug($csName) : '';
                $enNameNormalized = $enName ? Str::slug($enName) : '';
    
                // Find best matching global profession.
                $globalMatch = null;
                $bestSimilarity = 0;
    
                foreach ($globalProfessions as $global) {
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
                    Log::info("[mergeAllSelected] Merging Local Profession '{$csName}' -> Global Profession ID {$globalMatch->id}");
    
                    // IMPROVED CATEGORY HANDLING
                    $localCategoryId = $local->profession_category_id;
                    $globalCategoryId = $globalMatch->profession_category_id;
                    $finalCategoryId = $globalCategoryId; // Default to keeping the global category
                    
                    // Handle categories based on mergeOptions setting
                    if ($this->mergeOptions['mergeCategories']) {
                        // If merging categories AND global profession already has a category, use that
                        if ($globalCategoryId) {
                            $finalCategoryId = $globalCategoryId;
                            Log::info("[mergeAllSelected] Using existing global category ID: {$finalCategoryId}");
                        } 
                        // If global has no category but local does, find or create appropriate global category
                        else if ($localCategoryId) {
                            // Get local category details
                            $localCategory = DB::table("{$tenantPrefix}profession_categories")
                                ->where('id', $localCategoryId)
                                ->first();
                                
                            if ($localCategory) {
                                $localCatNameArr = json_decode($localCategory->name, true);
                                
                                // Check if a matching global category exists by either Czech or English name
                                $matchingGlobalCategory = DB::table("global_profession_categories")
                                    ->where(function($query) use ($localCatNameArr) {
                                        // Match by Czech name
                                        if (!empty($localCatNameArr['cs'])) {
                                            $query->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) = ?", 
                                                [strtolower($localCatNameArr['cs'])]);
                                        }
                                        // Match by English name
                                        if (!empty($localCatNameArr['en'])) {
                                            $query->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) = ?", 
                                                [strtolower($localCatNameArr['en'])]);
                                        }
                                    })
                                    ->first();
                                    
                                if ($matchingGlobalCategory) {
                                    // Use the matching global category
                                    $finalCategoryId = $matchingGlobalCategory->id;
                                    Log::info("[mergeAllSelected] Using matching global category ID: {$finalCategoryId}");
                                } else {
                                    // Create a new global category since no match exists
                                    $newGlobalCatId = DB::table("global_profession_categories")->insertGetId([
                                        'name' => json_encode($localCatNameArr),
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                    
                                    $finalCategoryId = $newGlobalCatId;
                                    Log::info("[mergeAllSelected] Created new global category: {$newGlobalCatId}");
                                }
                            }
                        }
                    } else {
                        // Not merging categories - keep the global category as is
                        Log::info("[mergeAllSelected] Category merging disabled, keeping global category");
                    }
                    
                    // If we have determined a category, update the global profession if needed
                    if ($finalCategoryId && $finalCategoryId != $globalMatch->profession_category_id) {
                        DB::table("global_professions")
                            ->where('id', $globalMatch->id)
                            ->update([
                                'profession_category_id' => $finalCategoryId
                            ]);
                        Log::info("[mergeAllSelected] Updated global profession {$globalMatch->id} with category ID {$finalCategoryId}");
                    }
    
                    // Update pivot records in identity_profession table - KEEP ALL IDENTITIES ATTACHED
                    $linkedIdentities = DB::table("{$tenantPrefix}identity_profession")
                        ->where('profession_id', $local->id)
                        ->get();
    
                    foreach ($linkedIdentities as $identity) {
                        DB::table("{$tenantPrefix}identity_profession")
                            ->where('identity_id', $identity->identity_id)
                            ->where('profession_id', $local->id)
                            ->update([
                                'global_profession_id' => $globalMatch->id,
                                'profession_id' => null, // Nullify local profession ID
                            ]);
                        Log::info("[mergeAllSelected] Updated identity {$identity->identity_id} with global_profession_id {$globalMatch->id}");
                    }
    
                    // Delete the local profession record.
                    DB::table("{$tenantPrefix}professions")->where('id', $local->id)->delete();
                    $merged++;
                } else {
                    Log::warning("[mergeAllSelected] No global match found for '{$csName}' ({$enName}). Skipping.");
                    $skipped++;
                }
            }
    
            // Validate pivot records before committing
            $allUpdatedPivots = DB::table("{$tenantPrefix}identity_profession")
                ->whereNotNull('global_profession_id')
                ->whereNull('profession_id')
                ->get();
    
            $this->validatePivotUpdates($tenantPrefix, $allUpdatedPivots);
    
            // Clean up orphaned categories
            $this->cleanUpOrphanedCategories($tenantPrefix);
    
            DB::commit();
            Log::info("[mergeAllSelected] Merge completed. Total merged professions: $merged, skipped: $skipped");
            $this->dispatch('alert', [
                'type' => 'success',
                'message' => "$merged " . __('professions successfully merged!') . " $skipped " . __('professions skipped.')
            ]);
            
            // Clear selected professions after successful merge
            $this->selectedProfessions = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[mergeAllSelected] Error during merge: " . $e->getMessage());
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('Error during merge: ') . $e->getMessage()
            ]);
        }
    
        $this->isProcessing = false;
        $this->dispatch('refreshTable'); // Refresh the UI after merge.
    }

    private function cleanUpOrphanedCategories($tenantPrefix)
    {
        // First check if identity_profession_category table exists (for direct identity-category relationships)
        $hasIdentityCategoryTable = false;
        try {
            // Simple query to check if table exists
            DB::select("SHOW TABLES LIKE '{$tenantPrefix}identity_profession_category'");
            $hasIdentityCategoryTable = true;
            Log::info("[cleanUpOrphanedCategories] Found identity_profession_category table");
        } catch (\Exception $e) {
            Log::info("[cleanUpOrphanedCategories] No direct identity-category relationship table found");
        }
        
        if ($hasIdentityCategoryTable) {
            // If there's a direct identity-category relationship, include that in our orphan check
            $orphanedCategories = DB::table("{$tenantPrefix}profession_categories as pc")
                ->leftJoin("{$tenantPrefix}professions as p", "pc.id", "=", "p.profession_category_id")
                ->leftJoin("{$tenantPrefix}identity_profession_category as ipc", "pc.id", "=", "ipc.profession_category_id") 
                ->whereNull("p.id")
                ->whereNull("ipc.identity_id") // Only delete if no identities are directly attached
                ->select("pc.id")
                ->get();
        } else {
            // Otherwise, just check for professions
            $orphanedCategories = DB::table("{$tenantPrefix}profession_categories as pc")
                ->leftJoin("{$tenantPrefix}professions as p", "pc.id", "=", "p.profession_category_id")
                ->whereNull("p.id")
                ->select("pc.id")
                ->get();
            
            // Additional check: ensure no identities reference professions with this category
            $safeToDelete = [];
            foreach ($orphanedCategories as $category) {
                // Double check that no identities reference this category through any means
                $hasIdentityReferences = DB::table("{$tenantPrefix}identity_profession as ip")
                    ->join("{$tenantPrefix}professions as p", function($join) use ($category) {
                        $join->on("ip.profession_id", "=", "p.id")
                             ->where("p.profession_category_id", "=", $category->id);
                    })
                    ->exists();
                    
                if (!$hasIdentityReferences) {
                    $safeToDelete[] = $category->id;
                } else {
                    Log::info("[cleanUpOrphanedCategories] Category {$category->id} has identity references, skipping deletion");
                }
            }
            
            // Redefine orphaned categories to only include those safe to delete
            $orphanedCategories = collect($safeToDelete)->map(function($id) {
                return (object)['id' => $id];
            });
        }
    
        foreach ($orphanedCategories as $orphan) {
            DB::table("{$tenantPrefix}profession_categories")->where('id', $orphan->id)->delete();
            Log::info("[cleanUpOrphanedCategories] Deleted orphaned category ID: {$orphan->id}");
        }
        
        Log::info("[cleanUpOrphanedCategories] Deleted " . count($orphanedCategories) . " orphaned categories");
    }

    public function updateSimilarityThreshold($value)
    {
        $this->similarityThreshold = intval($value);
        $this->previewData = $this->generatePreviewData(); 
        
        // Update merge stats after changing threshold
        $this->mergeStats = [
            'total' => count($this->previewData),
            'merged' => count(array_filter($this->previewData, function($item) { return $item['willMerge']; })),
            'skipped' => count(array_filter($this->previewData, function($item) { return !$item['willMerge']; })),
        ];
    }

    public function generatePreviewData()
    {
        // Get the tenant's table prefix dynamically.
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('Tenant prefix not found.')
            ]);
            return [];
        }

        $tenantPrefix = $tenant->table_prefix . '__';
        
        // Get local and global professions
        $localProfessions = DB::table("{$tenantPrefix}professions")->get();
        $globalProfessions = DB::table("global_professions")->get();
        
        $previewData = [];
        
        foreach ($localProfessions as $local) {
            $localNameArr = json_decode($local->name, true);
            $csName = strtolower(trim($localNameArr['cs'] ?? ''));
            $enName = strtolower(trim($localNameArr['en'] ?? ''));
            
            $csNameNormalized = $csName ? Str::slug($csName) : '';
            $enNameNormalized = $enName ? Str::slug($enName) : '';
            
            // Get the local category
            $localCategory = null;
            if ($local->profession_category_id) {
                $localCategory = DB::table("{$tenantPrefix}profession_categories")
                    ->where('id', $local->profession_category_id)
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
            
            foreach ($globalProfessions as $global) {
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
                    $globalCategoryId = $global->profession_category_id;
                    
                    // Get global category name
                    if ($globalCategoryId) {
                        $globalCategory = DB::table("global_profession_categories")
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
                'localCategoryId' => $local->profession_category_id,
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
        $this->mergeStats = [
            'total' => count($previewData),
            'merged' => count(array_filter($previewData, function($item) { return $item['willMerge']; })),
            'skipped' => count(array_filter($previewData, function($item) { return !$item['willMerge']; })),
        ];
        
        return $previewData;
    }
    
    public function previewMerge()
    {
        $this->isProcessing = true;
        $this->previewData = $this->generatePreviewData();
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
        
        // Get the tenant's table prefix dynamically.
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('Tenant prefix not found.')
            ]);
            $this->isProcessing = false;
            return;
        }

        $tenantPrefix = $tenant->table_prefix . '__';
        
        // Get all unmerged local professions
        $localProfessions = DB::table("{$tenantPrefix}professions")->get();
        
        $this->unmergedProfessions = [];
        foreach($localProfessions as $p) {
            $nameArr = json_decode($p->name, true);
            
            // Get category name if it exists
            $categoryName = '';
            $categoryId = $p->profession_category_id;
            if ($categoryId) {
                $category = DB::table("{$tenantPrefix}profession_categories")->find($categoryId);
                if ($category) {
                    $catNameArr = json_decode($category->name, true);
                    $categoryName = $catNameArr['cs'] ?? '';
                }
            }
            
            $this->unmergedProfessions[] = [
                'id' => $p->id,
                'cs' => $nameArr['cs'] ?? '',
                'en' => $nameArr['en'] ?? '',
                'category_id' => $categoryId,
                'category_name' => $categoryName
            ];
        }
        
        // Get all global professions with category info
        $globalProfessions = DB::table("global_professions")->get();
        
        $this->availableGlobalProfessions = [];
        foreach($globalProfessions as $p) {
            $nameArr = json_decode($p->name, true);
            
            // Get category name if it exists
            $categoryName = '';
            $categoryId = $p->profession_category_id;
            if ($categoryId) {
                $category = DB::table("global_profession_categories")->find($categoryId);
                if ($category) {
                    $catNameArr = json_decode($category->name, true);
                    $categoryName = $catNameArr['cs'] ?? '';
                }
            }
            
            $this->availableGlobalProfessions[] = [
                'id' => $p->id,
                'cs' => $nameArr['cs'] ?? '',
                'en' => $nameArr['en'] ?? '',
                'category_id' => $categoryId,
                'category_name' => $categoryName
            ];
        }
        
        // Lets be sure to reset any previously selected professions
        $this->selectedLocalProfession = null;
        $this->selectedGlobalProfession = null;
        
        // Ensure these are properly initialized and marked for serialization
        $this->unmergedProfessions = collect($this->unmergedProfessions)->toArray();
        $this->availableGlobalProfessions = collect($this->availableGlobalProfessions)->toArray();
        
        // Defer showing the modal until after the data is ready
        $this->showManualMerge = true;
        $this->isProcessing = false;
    }

    public function selectLocalProfession($id)
    {
        $this->isProcessing = true;
        $this->selectedLocalProfession = $id;
        $this->selectedGlobalProfession = null;
        
        // Find the selected local profession
        $local = collect($this->unmergedProfessions)->firstWhere('id', $id);
        
        if ($local) {
            $csNameNormalized = $local['cs'] ? Str::slug(strtolower($local['cs'])) : '';
            $enNameNormalized = $local['en'] ? Str::slug(strtolower($local['en'])) : '';
            
            foreach ($this->availableGlobalProfessions as &$global) {
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
            
            // **Sort Global Professions by Similarity and Category Match**
            usort($this->availableGlobalProfessions, function($a, $b) {
                // Prioritize category matches
                if ($a['categoryMatch'] && !$b['categoryMatch']) {
                    return -1; // a comes first
                }
                if (!$a['categoryMatch'] && $b['categoryMatch']) {
                    return 1; // b comes first
                }
                
                // Sort by similarity score (descending)
                return $b['avgSimilarity'] <=> $a['avgSimilarity'];
            });
        }
        
        $this->isProcessing = false;
    }    

    public function selectGlobalProfession($id)
    {
        $this->selectedGlobalProfession = $id;
    }

    // Method to handle selection of professions for direct merging
    public function selectProfessionForMerge($source, $id)
    {
        $this->isProcessing = true;
        
        try {
            // If first profession not selected yet, select it
            if (!$this->selectedProfessionOne) {
                $this->selectedProfessionOne = [
                    'source' => $source,
                    'id' => $id
                ];
                
                $this->loadProfessionDetails('one');
                
                if (!$this->selectedProfessionOneDetails) {
                    throw new \Exception(__("Failed to load profession details"));
                }
                
                $this->dispatch('alert', [
                    'type' => 'info', 
                    'message' => __('First profession selected. Please select a second profession to merge with.')
                ]);
                
            } else if (!$this->selectedProfessionTwo) {
                // Don't allow selecting the same profession
                if ($source === $this->selectedProfessionOne['source'] && (int)$id === (int)$this->selectedProfessionOne['id']) {
                    throw new \Exception(__("You cannot merge a profession with itself. Please select a different profession."));
                }
                
                $this->selectedProfessionTwo = [
                    'source' => $source,
                    'id' => $id
                ];
                
                $this->loadProfessionDetails('two');
                
                if (!$this->selectedProfessionTwoDetails) {
                    throw new \Exception(__("Failed to load second profession details"));
                }
                
                $this->openMergeTwoProfessions();
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'type' => 'error', 
                'message' => $e->getMessage()
            ]);
            
            // Reset if there was an error
            if (!$this->selectedProfessionOneDetails) {
                $this->selectedProfessionOne = null;
            }
            
            if (!$this->selectedProfessionTwoDetails) {
                $this->selectedProfessionTwo = null;
            }
       } finally {
           $this->isProcessing = false;
       }
   }

   // Method to load profession details
   public function loadProfessionDetails($position)
   {
       $selectedProfession = $position === 'one' ? $this->selectedProfessionOne : $this->selectedProfessionTwo;
       $source = $selectedProfession['source'];
       $id = $selectedProfession['id'];
       
       try {
           // Get the tenant's table prefix dynamically
           $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
           $tenantPrefix = $tenant->table_prefix . '__';
           
           if ($source === 'local') {
               // Get profession data from tenant's table
               $profession = DB::table("{$tenantPrefix}professions")->where('id', $id)->first();
               if (!$profession) {
                   throw new \Exception(__("Local profession not found"));
               }
               
               $nameArr = json_decode($profession->name, true);
               
               // Get categories
               $categories = [];
               if ($profession->profession_category_id) {
                   $category = DB::table("{$tenantPrefix}profession_categories")
                       ->where('id', $profession->profession_category_id)
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
               
               // Get identities
               $identities = [];
               $identityLinks = DB::table("{$tenantPrefix}identity_profession")
                   ->where('profession_id', $id)
                   ->get();
               
               foreach ($identityLinks as $link) {
                   $identity = DB::table("{$tenantPrefix}identities")
                       ->where('id', $link->identity_id)
                       ->first();
                   if ($identity) {
                       $identities[] = [
                           'id' => $identity->id,
                           'name' => $identity->name
                       ];
                   }
               }
               
               $details = [
                   'id' => $profession->id,
                   'cs' => $nameArr['cs'] ?? __('No CS Name'),
                   'en' => $nameArr['en'] ?? __('No EN Name'),
                   'categories' => $categories,
                   'identities' => $identities,
                   'source' => 'local'
               ];
               
           } else {
               // Handle global professions
               $profession = DB::table("global_professions")->where('id', $id)->first();
               if (!$profession) {
                   throw new \Exception(__("Global profession not found"));
               }
               
               $nameArr = json_decode($profession->name, true);
               
               // Get categories
               $categories = [];
               if ($profession->profession_category_id) {
                   $category = DB::table("global_profession_categories")
                       ->where('id', $profession->profession_category_id)
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
               
               // Get identities (from tenant's table)
               $identities = [];
               $identityLinks = DB::table("{$tenantPrefix}identity_profession")
                   ->where('global_profession_id', $id)
                   ->get();
               
               foreach ($identityLinks as $link) {
                   $identity = DB::table("{$tenantPrefix}identities")
                       ->where('id', $link->identity_id)
                       ->first();
                   if ($identity) {
                       $identities[] = [
                           'id' => $identity->id,
                           'name' => $identity->name
                       ];
                   }
               }
               
               $details = [
                   'id' => $profession->id,
                   'cs' => $nameArr['cs'] ?? __('No CS Name'),
                   'en' => $nameArr['en'] ?? __('No EN Name'),
                   'categories' => $categories,
                   'identities' => $identities,
                   'source' => 'global'
               ];
           }
           
           // Set the details to the appropriate variable
           if ($position === 'one') {
               $this->selectedProfessionOneDetails = $details;
           } else {
               $this->selectedProfessionTwoDetails = $details;
           }
           
       } catch (\Exception $e) {
           Log::error("[loadProfessionDetails] Error: " . $e->getMessage());
           
           if ($position === 'one') {
               $this->selectedProfessionOneDetails = null;
           } else {
               $this->selectedProfessionTwoDetails = null;
           }
           
           throw $e;  // Re-throw to be caught by the caller
       }
   }

   // Method to open the merge two professions modal
   public function openMergeTwoProfessions()
   {
       if (!$this->selectedProfessionOneDetails || !$this->selectedProfessionTwoDetails) {
           $this->dispatch('alert', [
               'type' => 'error',
               'message' => __('Failed to load profession details. Please try again.')
           ]);
           return;
       }
       
       $this->showMergeTwoProfessions = true;
   }

   // Method to close the merge two professions modal and reset selections
   public function closeMergeTwoProfessions()
   {
       $this->showMergeTwoProfessions = false;
       $this->selectedProfessionOne = null;
       $this->selectedProfessionTwo = null;
       $this->selectedProfessionOneDetails = null;
       $this->selectedProfessionTwoDetails = null;
       $this->mergeOptions = [
           'mergeCategories' => true,
           'mergeIdentities' => true,
           'preferGlobalCategories' => true,
       ];
   }

   // Method to merge two professions directly
   public function mergeTwoProfessions()
   {
       if (!$this->selectedProfessionOneDetails || !$this->selectedProfessionTwoDetails) {
           $this->dispatch('alert', [
               'type' => 'error',
               'message' => __('Missing profession details. Please try again.')
           ]);
           return;
       }
   
       try {
           // Get the tenant's table prefix dynamically
           $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
           if (!$tenant || empty($tenant->table_prefix)) {
               $this->dispatch('alert', [
                   'type' => 'error',
                   'message' => __('Tenant prefix not found.')
               ]);
               return;
           }
           $tenantPrefix = $tenant->table_prefix . '__';
   
           // Begin transaction to ensure data consistency
           DB::beginTransaction();
   
           $profOne = $this->selectedProfessionOneDetails;
           $profTwo = $this->selectedProfessionTwoDetails;
   
           // Determine the target and source professions
           $isTargetGlobal = false;
   
           if ($profOne['source'] === 'global' && $profTwo['source'] === 'local') {
               $targetProf = $profOne;
               $sourceProf = $profTwo;
               $isTargetGlobal = true;
           } elseif ($profOne['source'] === 'local' && $profTwo['source'] === 'global') {
               $targetProf = $profTwo;
               $sourceProf = $profOne;
               $isTargetGlobal = true;
           } else {
               $targetProf = $profOne;
               $sourceProf = $profTwo;
               $isTargetGlobal = ($targetProf['source'] === 'global');
           }
   
           // Create merged name object
           $mergedName = [
               'cs' => $targetProf['cs'],
               'en' => $targetProf['en'],
           ];
   
           // Prepare merged categories
           $targetCategories = [];
           $sourceCategories = [];
           
           foreach ($targetProf['categories'] as $category) {
               $targetCategories[] = [
                   'id' => $category['id'],
                   'name' => $category['name'],
                   'local' => $category['local'] ?? ($targetProf['source'] === 'local')
               ];
           }
           
           foreach ($sourceProf['categories'] as $category) {
               $sourceCategories[] = [
                   'id' => $category['id'],
                   'name' => $category['name'],
                   'local' => $category['local'] ?? ($sourceProf['source'] === 'local')
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
                   Log::info("[mergeTwoProfessions] Using global category: {$firstGlobal['name']} (ID: {$finalCategoryId})");
               } elseif (!empty($allCategories)) {
                   // No global categories, use first available
                   $firstCategory = reset($allCategories);
                   $finalCategoryId = $firstCategory['id'];
                   $finalCategoryIsLocal = $firstCategory['local'] ?? true;
                   Log::info("[mergeTwoProfessions] No global categories found, using: {$firstCategory['name']} (ID: {$finalCategoryId})");
               }
           } else {
               // Not preferring global, just use first available
               if (!empty($allCategories)) {
                   $firstCategory = reset($allCategories);
                   $finalCategoryId = $firstCategory['id'];
                   $finalCategoryIsLocal = $firstCategory['local'] ?? true;
                   Log::info("[mergeTwoProfessions] Using category: {$firstCategory['name']} (ID: {$finalCategoryId})");
               }
           }
   
           // Update or transfer identities
           $identityIds = array_column($targetProf['identities'], 'id');
   
           if ($this->mergeOptions['mergeIdentities']) {
               foreach ($sourceProf['identities'] as $identity) {
                   if (!in_array($identity['id'], $identityIds)) {
                       $identityIds[] = $identity['id'];
                   }
               }
           }
   
           // Handle different merging scenarios
           if ($isTargetGlobal) {
               // Target is a global profession
               $updateData = ['name' => json_encode($mergedName)];
               
               // Add category ID if we have a global category
               if ($finalCategoryId && !$finalCategoryIsLocal) {
                   $updateData['profession_category_id'] = $finalCategoryId;
               }
               
               DB::table("global_professions")
                   ->where('id', $targetProf['id'])
                   ->update($updateData);
   
               if ($sourceProf['source'] === 'local') {
                   // Transfer all identity links from local to global
                   DB::table("{$tenantPrefix}identity_profession")
                       ->where('profession_id', $sourceProf['id'])
                       ->update([
                           'global_profession_id' => $targetProf['id'],
                           'profession_id' => null,
                       ]);
   
                   // Delete the local profession
                   DB::table("{$tenantPrefix}professions")->where('id', $sourceProf['id'])->delete();
                   
                   // Check if the local category should be removed (if orphaned)
                   if (!empty($sourceCategories) && $this->mergeOptions['mergeCategories']) {
                       $this->cleanUpOrphanedCategories($tenantPrefix);
                   }
               } else {
                   // Both are global, merge identities
                   DB::table("{$tenantPrefix}identity_profession")
                       ->where('global_profession_id', $sourceProf['id'])
                       ->update([
                           'global_profession_id' => $targetProf['id'],
                       ]);
   
                   // Mark source global profession as merged
                   DB::table("global_professions")
                       ->where('id', $sourceProf['id'])
                       ->update([
                           'merged_into' => $targetProf['id'],
                           'name' => json_encode([
                               'cs' => 'Merged: ' . $sourceProf['cs'],
                               'en' => 'Merged: ' . $sourceProf['en']
                           ]),
                           'profession_category_id' => null, // Remove category from merged profession
                       ]);
               }
           } else {
               // Both are local professions
               DB::table("{$tenantPrefix}professions")
                   ->where('id', $targetProf['id'])
                   ->update([
                       'name' => json_encode($mergedName),
                       'profession_category_id' => $finalCategoryId,
                   ]);
   
               // Update identity links
               DB::table("{$tenantPrefix}identity_profession")
                   ->where('profession_id', $sourceProf['id'])
                   ->update([
                       'profession_id' => $targetProf['id'],
                   ]);
   
               // Delete the source local profession
               DB::table("{$tenantPrefix}professions")->where('id', $sourceProf['id'])->delete();
               
               // Clean up orphaned categories if categories were merged
               if ($this->mergeOptions['mergeCategories']) {
                   $this->cleanUpOrphanedCategories($tenantPrefix);
               }
           }
   
           // Commit the transaction
           DB::commit();
   
           // Show success message
           $this->dispatch('alert', [
               'type' => 'success',
               'message' => __('Professions successfully merged.')
           ]);
   
           // Clear selection arrays
           $this->selectedProfessions = array_filter($this->selectedProfessions, function($jsonProf) use ($sourceProf) {
               $prof = json_decode($jsonProf, true);
               return !($prof['source'] === $sourceProf['source'] && $prof['id'] == $sourceProf['id']);
           });
           
           // Close the modal and reset selections
           $this->closeMergeTwoProfessions();
   
           // Refresh the data
           $this->dispatch('refreshTable');
       } catch (\Exception $e) {
           DB::rollBack();
           Log::error("[mergeTwoProfessions] Error: " . $e->getMessage());
           $this->dispatch('alert', [
               'type' => 'error',
               'message' => __('Error merging professions: ') . $e->getMessage()
           ]);
       }
   }

   private function findOrCreateGlobalCategory($localCategoryId, $globalCategoryId, $tenantPrefix)
   {
       if ($this->mergeOptions['preferGlobalCategories'] && $globalCategoryId) {
           return $globalCategoryId;
       }
   
       if ($localCategoryId) {
           $localCategory = DB::table("{$tenantPrefix}profession_categories")->where('id', $localCategoryId)->first();
           if ($localCategory) {
               $localCatName = json_decode($localCategory->name, true)['cs'] ?? '';
   
               $existingGlobalCategory = DB::table("global_profession_categories")
                   ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) = ?", [strtolower($localCatName)])
                   ->first();
   
               if ($existingGlobalCategory) {
                   return $existingGlobalCategory->id;
               }
   
               return DB::table("global_profession_categories")->insertGetId([
                   'name' => json_encode($localCategory->name),
                   'created_at' => now(),
                   'updated_at' => now(),
               ]);
           }
       }
   
       return $globalCategoryId;
   }
   
   private function findBestGlobalMatch($local, $globalProfessions)
   {
       $localNameArr = json_decode($local->name, true);
       $bestMatch = null;
       $bestScore = 0;
   
       foreach ($globalProfessions as $global) {
           $globalNameArr = json_decode($global->name, true);
           $score = $this->calculateSimilarityScore($localNameArr, $globalNameArr);
   
           if ($score > $bestScore) {
               $bestMatch = $global;
               $bestScore = $score;
           }
       }
   
       return $bestMatch;
   }
   
   private function calculateSimilarityScore($localNameArr, $globalNameArr)
   {
       return max(
           similar_text(strtolower($localNameArr['cs'] ?? ''), strtolower($globalNameArr['cs'] ?? '')),
           similar_text(strtolower($localNameArr['en'] ?? ''), strtolower($globalNameArr['en'] ?? ''))
       );
   }   
   
   public function performManualMerge()
   {
       if (!$this->selectedLocalProfession || !$this->selectedGlobalProfession) {
           $this->dispatch('alert', ['type' => 'error', 'message' => __('Select both professions for merging.')]);
           return;
       }
   
       $tenantPrefix = tenancy()->tenant->table_prefix . '__';
   
       DB::beginTransaction();
   
       try {
           // Fetch local and global professions
           $localProfession = DB::table("{$tenantPrefix}professions")->find($this->selectedLocalProfession);
           $globalProfession = DB::table('global_professions')->find($this->selectedGlobalProfession);
   
           if (!$localProfession || !$globalProfession) {
               throw new \Exception(__('Selected professions not found.'));
           }
   
           // Get names from both professions
           $localNameArr = json_decode($localProfession->name, true);
           $globalNameArr = json_decode($globalProfession->name, true);
           
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
           $finalGlobalCategoryId = $globalProfession->profession_category_id; // Default to global category
           
           // Only process category merging if the option is enabled
           if ($this->mergeOptions['mergeCategories']) {
               // If global profession already has a category, use that (as that's what merging means)
               if ($globalProfession->profession_category_id) {
                   $finalGlobalCategoryId = $globalProfession->profession_category_id;
                   Log::info("[performManualMerge] Using existing global category ID: {$finalGlobalCategoryId}");
               }
               // If global has no category but local does, find or create appropriate global category
               else if ($localProfession->profession_category_id) {
                   $localCategory = DB::table("{$tenantPrefix}profession_categories")
                       ->find($localProfession->profession_category_id);
                   
                   if ($localCategory) {
                       $localCatNameArr = json_decode($localCategory->name, true);
                       
                       // Look for global category with matching name (by either language)
                       $matchingGlobalCategory = DB::table("global_profession_categories")
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
                           $finalGlobalCategoryId = DB::table("global_profession_categories")->insertGetId([
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
   
           // Update global profession with merged data
           DB::table('global_professions')
               ->where('id', $globalProfession->id)
               ->update([
                   'name' => json_encode($mergedName),
                   'profession_category_id' => $finalGlobalCategoryId,
                   'updated_at' => now(),
               ]);
   
           // Move attached identities from local to global profession
           DB::table("{$tenantPrefix}identity_profession")
               ->where('profession_id', $localProfession->id)
               ->update([
                   'global_profession_id' => $globalProfession->id, 
                   'profession_id' => null  // Nullify the local profession ID
               ]);
   
           // Delete local profession
           DB::table("{$tenantPrefix}professions")->where('id', $localProfession->id)->delete();
   
           // Clean up orphaned categories
           $this->cleanUpOrphanedCategories($tenantPrefix);
   
           DB::commit();
   
           $this->dispatch('alert', ['type' => 'success', 'message' => __('Profession successfully merged.')]);
           $this->reset(['selectedLocalProfession', 'selectedGlobalProfession', 'showManualMerge']);
           $this->dispatch('refreshTable');
   
       } catch (\Exception $e) {
           DB::rollBack();
           Log::error("[performManualMerge] Error: " . $e->getMessage());
           $this->dispatch('alert', ['type' => 'error', 'message' => __('Error merging professions: ') . $e->getMessage()]);
       }
   }

   public function refreshInputs()
   {
       $this->dispatch('inputRefresh');
   }

   public function closeManualMerge()
   {
       $this->showManualMerge = false;
       $this->reset('localProfessionSearch', 'globalProfessionSearch'); // Reset input values
       $this->dispatch('resetSearchFields'); // Force Livewire to refresh inputs
   }   

   private function validatePivotUpdates($tenantPrefix, $pivotRecords)
   {
       $totalRecords = count($pivotRecords);
       $validUpdates = 0;
       
       foreach ($pivotRecords as $record) {
           // Check if the global profession exists
           $globalExists = DB::table("global_professions")
               ->where('id', $record->global_profession_id)
               ->exists();
               
           if ($globalExists) {
               $validUpdates++;
           } else {
               Log::warning("[validatePivotUpdates] Missing global profession ID: {$record->global_profession_id} for identity {$record->identity_id}");
           }
       }
       
       $validationRate = $totalRecords > 0 ? ($validUpdates / $totalRecords) * 100 : 0;
       
       // If more than 5% of updates are invalid, something might be wrong
       if ($validationRate < 95) {
           Log::error("[validatePivotUpdates] High failure rate: {$validationRate}% valid updates out of {$totalRecords} records");
           throw new \Exception(__("Validation failed: Too many invalid global profession references."));
       }
       
       return true;
   }

   /**
    * Merge tenant professions into global ones while handling categories based on merge options.
    */
    public function mergeAll()
    {
        $this->isProcessing = true;
        Log::info('[mergeAll] Button clicked! Fetching tenant prefix...');
    
        // Get the tenant's table prefix dynamically.
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            Log::error("[mergeAll] Failed to get tenant prefix!");
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('Tenant prefix not found.')
            ]);
            $this->isProcessing = false;
            return;
        }
    
        $tenantPrefix = $tenant->table_prefix . '__';
        Log::info("[mergeAll] Using Tenant Prefix: $tenantPrefix");
    
        // Retrieve all tenant (local) professions.
        $localProfessions = DB::table("{$tenantPrefix}professions")->get();
        if ($localProfessions->isEmpty()) {
            Log::warning("[mergeAll] No local professions found.");
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('No local professions to merge.')
            ]);
            $this->isProcessing = false;
            return;
        }
    
        // Retrieve all global professions for matching.
        $globalProfessions = DB::table("global_professions")->get();
        $merged = 0;
        $skipped = 0;
    
        DB::beginTransaction();
        try {
            foreach ($localProfessions as $local) {
                $localNameArr = json_decode($local->name, true);
                $csName = strtolower(trim($localNameArr['cs'] ?? ''));
                $enName = strtolower(trim($localNameArr['en'] ?? ''));
                Log::info("[mergeAll] Checking Local Profession: CS='$csName', EN='$enName'");
    
                // Normalize available names.
                $csNameNormalized = $csName ? Str::slug($csName) : '';
                $enNameNormalized = $enName ? Str::slug($enName) : '';
    
                // Find best matching global profession.
                $globalMatch = null;
                $bestSimilarity = 0;
    
                foreach ($globalProfessions as $global) {
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
                    Log::info("[mergeAll] Merging Local Profession '{$csName}' -> Global Profession ID {$globalMatch->id}");
    
                    // Handle categories based on merge options (FIXED PART)
                    if ($this->mergeOptions['mergeCategories']) {
                        // Get the local category if it exists
                        $localCategoryId = $local->profession_category_id;
                        $globalCategoryId = $globalMatch->profession_category_id;
                        
                        $finalCategoryId = null;
                        
                        if ($this->mergeOptions['preferGlobalCategories']) {
                            // Prefer global category if available
                            if ($globalCategoryId) {
                                $finalCategoryId = $globalCategoryId;
                                Log::info("[mergeAll] Using global category ID: {$globalCategoryId}");
                            } elseif ($localCategoryId) {
                                // If no global category, try to find or create global equivalent
                                $localCategory = DB::table("{$tenantPrefix}profession_categories")
                                    ->where('id', $localCategoryId)
                                    ->first();
                                    
                                if ($localCategory) {
                                    $localCatNameArr = json_decode($localCategory->name, true);
                                    
                                    // Check if a similar global category exists
                                    $similarGlobalCat = DB::table("global_profession_categories")
                                        ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) = ?", 
                                            [strtolower($localCatNameArr['cs'] ?? '')])
                                        ->first();
                                        
                                    if ($similarGlobalCat) {
                                        // Use the similar global category
                                        $finalCategoryId = $similarGlobalCat->id;
                                        Log::info("[mergeAll] Found similar global category ID: {$finalCategoryId}");
                                    } else {
                                        // Create a new global category if none exists
                                        $newGlobalCatId = DB::table("global_profession_categories")->insertGetId([
                                            'name' => json_encode($localCatNameArr),
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                        
                                        $finalCategoryId = $newGlobalCatId;
                                        Log::info("[mergeAll] Created new global category ID: {$newGlobalCatId}");
                                    }
                                }
                            }
                        } else if ($localCategoryId) {
                            // Not preferring global but we still need a global category ID for global profession
                            // Try to find a matching global category or create one
                            $localCategory = DB::table("{$tenantPrefix}profession_categories")
                                ->where('id', $localCategoryId)
                                ->first();
                                
                            if ($localCategory) {
                                $localCatNameArr = json_decode($localCategory->name, true);
                                
                                // Check if a similar global category exists
                                $similarGlobalCat = DB::table("global_profession_categories")
                                    ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) = ?", 
                                        [strtolower($localCatNameArr['cs'] ?? '')])
                                    ->first();
                                    
                                if ($similarGlobalCat) {
                                    // Use the similar global category
                                    $finalCategoryId = $similarGlobalCat->id;
                                    Log::info("[mergeAll] Using similar global category ID: {$finalCategoryId}");
                                } else {
                                    // Create a new global category if none exists
                                    $newGlobalCatId = DB::table("global_profession_categories")->insertGetId([
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
                        
                        // Update the global profession's category if needed
                        if ($finalCategoryId && $finalCategoryId != $globalMatch->profession_category_id) {
                            DB::table("global_professions")
                                ->where('id', $globalMatch->id)
                                ->update([
                                    'profession_category_id' => $finalCategoryId
                                ]);
                            Log::info("[mergeAll] Updated global profession {$globalMatch->id} with category ID {$finalCategoryId}");
                        }
                    }
    
                    // Update pivot records in identity_profession table
                    $linkedIdentities = DB::table("{$tenantPrefix}identity_profession")
                        ->where('profession_id', $local->id)
                        ->get();
    
                    foreach ($linkedIdentities as $identity) {
                        DB::table("{$tenantPrefix}identity_profession")
                            ->where('identity_id', $identity->identity_id)
                            ->where('profession_id', $local->id)
                            ->update([
                                'global_profession_id' => $globalMatch->id,
                                'profession_id' => null,
                            ]);
                        Log::info("[mergeAll] Updated identity {$identity->identity_id} with global_profession_id {$globalMatch->id}");
                    }
    
                    // Delete the local profession record.
                    DB::table("{$tenantPrefix}professions")->where('id', $local->id)->delete();
                    $merged++;
                } else {
                    Log::warning("[mergeAll] No global match found for '{$csName}' ({$enName}). Skipping.");
                    $skipped++;
                }
            }
    
            // Validate pivot records before committing
            $allUpdatedPivots = DB::table("{$tenantPrefix}identity_profession")
                ->whereNotNull('global_profession_id')
                ->whereNull('profession_id')
                ->get();
    
            $this->validatePivotUpdates($tenantPrefix, $allUpdatedPivots);
    
            // Clean up orphaned categories
            $this->cleanUpOrphanedCategories($tenantPrefix);
    
            DB::commit();
            Log::info("[mergeAll] Merge completed. Total merged professions: $merged, skipped: $skipped");
            $this->dispatch('alert', [
                'type' => 'success',
                'message' => "$merged " . __('professions successfully merged!') . " $skipped " . __('professions skipped.')
            ]);
            
            // Clear selected professions after successful merge
            $this->selectedProfessions = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[mergeAll] Error during merge: " . $e->getMessage());
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('Error during merge: ') . $e->getMessage()
            ]);
        }
    
        $this->isProcessing = false;
        $this->dispatch('refreshTable'); // Refresh the UI after merge.
    }
}
