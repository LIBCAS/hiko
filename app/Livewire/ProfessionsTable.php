<?php

namespace App\Livewire;

use App\Models\Profession;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

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
    
        // Get base queries and bindings
        $tenantBase = $tenantProfessionsQuery->toBase();
        $globalBase = $globalProfessionsQuery->toBase();
    
        $tenantSql = $tenantBase->toSql();
        $globalSql = $globalBase->toSql();
    
        // Merge both queries using raw SQL with bindings
        $unionSql = "(
            SELECT id, profession_category_id, name, 'local' AS source FROM ({$tenantSql}) AS local_professions
            UNION ALL
            SELECT id, profession_category_id, name, 'global' AS source FROM ({$globalSql}) AS global_professions
        ) AS combined_professions";
    
        $unionQuery = DB::table(DB::raw($unionSql))
            ->mergeBindings($tenantBase)
            ->mergeBindings($globalBase);
    
        // Add sorting and row_number()
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
    
        // Retrieve all local professions
        $localProfessions = DB::table("{$tenantPrefix}professions")->get();
    
        if ($localProfessions->isEmpty()) {
            Log::warning("[mergeAll] No local professions found.");
            session()->flash('error', 'No local professions to merge.');
            return;
        }
    
        // Get all global professions for matching
        $globalProfessions = DB::table("global_professions")->get();
        $merged = 0;
    
        foreach ($localProfessions as $local) {
            $localNameJson = json_decode($local->name, true);
            $csName = strtolower(trim($localNameJson['cs'] ?? ''));
            $enName = strtolower(trim($localNameJson['en'] ?? ''));
    
            Log::info("[mergeAll] Checking Local Profession: CS='$csName', EN='$enName'");
    
            // Find exact match first
            $globalMatch = null;
            foreach ($globalProfessions as $global) {
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
                Log::info("[mergeAll] Merging Local Profession '{$csName}' -> Global Profession ID {$globalMatch->id}");
    
                // **STEP 1: Find identities linked to the local profession**
                $linkedIdentities = DB::table("{$tenantPrefix}identity_profession")
                    ->where('profession_id', $local->id)
                    ->get();
    
                foreach ($linkedIdentities as $identity) {
                    // **STEP 2: Check if this identity already has a global_profession_id**
                    $existingGlobal = DB::table("{$tenantPrefix}identity_profession")
                        ->where('identity_id', $identity->identity_id)
                        ->whereNotNull('global_profession_id')
                        ->first();
    
                    if ($existingGlobal) {
                        Log::info("[mergeAll] Identity {$identity->identity_id} already has global_profession_id: {$existingGlobal->global_profession_id}");
                    } else {
                        // **STEP 3: Reassign the global_profession_id to the identity_id of local profession**
                        DB::table("{$tenantPrefix}identity_profession")
                            ->where('identity_id', $identity->identity_id)
                            ->update(['global_profession_id' => $globalMatch->id]);
    
                        Log::info("[mergeAll] Reassigned global_profession_id {$globalMatch->id} to identity_id {$identity->identity_id}");
                    }
                }
    
                // **STEP 4: Delete local profession reference**
                DB::table("{$tenantPrefix}identity_profession")
                    ->where('profession_id', $local->id)
                    ->update(['profession_id' => null]);
    
                // **STEP 5: Delete the local profession itself**
                DB::table("{$tenantPrefix}professions")->where('id', $local->id)->delete();
    
                $merged++;
            } else {
                Log::warning("[mergeAll] No global match found for '{$csName}' ({$enName}). Skipping.");
            }
        }
    
        Log::info("[mergeAll] Merge completed. Total merged: $merged");
        session()->flash('success', "$merged professions merged successfully!");
    
        $this->dispatch('refreshTable'); // Ensure UI refresh
    }
    
    
    protected function getListeners()
    {
        return ['refreshTable' => '$refresh', 'mergeAll' => 'mergeAll'];
    }    
}
