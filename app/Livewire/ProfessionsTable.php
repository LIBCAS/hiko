<?php

namespace App\Livewire;

use App\Models\Profession;
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

    public $filters = [
        'order'    => 'cs',
        'source'   => 'all', // 'local', 'global', 'all'
        'cs'       => '',
        'en'       => '',
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
            'tableData'  => $this->formatTableData($professions),
            'pagination' => $professions,
        ]);
    }

    protected function findProfessions(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $perPage = 10;

        $tenantProfessionsQuery = $this->getTenantProfessionsQuery();
        $globalProfessionsQuery = $this->getGlobalProfessionsQuery();

        $query = match($filters['source']) {
            'local'  => $tenantProfessionsQuery,
            'global' => $globalProfessionsQuery,
            default  => $this->mergeQueries($tenantProfessionsQuery, $globalProfessionsQuery),
        };

        if (in_array($filters['order'], ['cs', 'en'])) {
            $orderColumn = "CONVERT(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$filters['order']}\"')) USING utf8mb4) COLLATE utf8mb4_unicode_ci";
            $query->orderByRaw($orderColumn);
        }

        return $query->paginate($perPage);
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
            'rows'   => $data->map(function ($pf) {
                if ($pf->source === 'local') {
                    $profession = Profession::find($pf->id);
                } else {
                    $profession = \App\Models\GlobalProfession::find($pf->id);
                }
                $csName = $profession->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $profession->getTranslation('name', 'en') ?? 'No EN name';
                $sourceLabel = $pf->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>" . __('hiko.local') . "</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>" . __('hiko.global') . "</span>";

                $categoryDisplay = $profession->profession_category
                    ? $profession->profession_category->getTranslation('name', 'cs') ?? ''
                    : "<span class='text-red-600'>" . __('hiko.no_attached_category') . "</span>";

                if ($pf->source === 'local') {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link'  => route('professions.edit', $pf->id),
                    ];
                } elseif ($pf->source === 'global' && auth()->user()->can('manage-users')) {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link'  => route('global.professions.edit', $pf->id),
                    ];
                } else {
                    $editLink = [
                        'label'    => __('hiko.edit'),
                        'link'     => '#',
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

    /**
     * Merge tenant professions into global ones while merging their categories.
     * (No new columns are added in the pivot; only global_professions are updated if needed.)
     */
    public function mergeAll()
    {
        Log::info('[mergeAll] Button clicked! Fetching tenant prefix...');

        // Get the tenant's table prefix dynamically.
        $tenant = DB::table('tenants')->where('id', tenancy()->tenant->id)->first();
        if (!$tenant || empty($tenant->table_prefix)) {
            Log::error("[mergeAll] Failed to get tenant prefix!");
            session()->flash('error', 'Tenant prefix not found.');
            return;
        }

        $tenantPrefix = $tenant->table_prefix . '__';
        Log::info("[mergeAll] Using Tenant Prefix: $tenantPrefix");

        // --- Merge Categories First: Build mapping tenant_category_id => global_category_id ---
        $localCategories = DB::table("{$tenantPrefix}profession_categories")->get();
        $globalCategories = DB::table("global_profession_categories")->get();

        $categoryMap = [];
        foreach ($localCategories as $localCat) {
            $localNameArr = json_decode($localCat->name, true);
            $localCsName  = strtolower(trim($localNameArr['cs'] ?? ''));
            $localEnName  = strtolower(trim($localNameArr['en'] ?? ''));

            $globalCatMatch = null;
            foreach ($globalCategories as $globalCat) {
                $globalNameArr = json_decode($globalCat->name, true);
                $globalCsName  = strtolower(trim($globalNameArr['cs'] ?? ''));
                $globalEnName  = strtolower(trim($globalNameArr['en'] ?? ''));
                $csSimilarity  = 0;
                $enSimilarity  = 0;
                similar_text($localCsName, $globalCsName, $csSimilarity);
                similar_text($localEnName, $globalEnName, $enSimilarity);
                if ($csSimilarity > 90 || $enSimilarity > 90) {
                    $globalCatMatch = $globalCat;
                    break;
                }
            }
            if (!$globalCatMatch) {
                // Insert new global category.
                $newGlobalCatId = DB::table("global_profession_categories")->insertGetId([
                    'name'       => json_encode(['cs' => $localCsName, 'en' => $localEnName]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $categoryMap[$localCat->id] = $newGlobalCatId;
                $globalCategories->push((object)[
                    'id'   => $newGlobalCatId,
                    'name' => json_encode(['cs' => $localCsName, 'en' => $localEnName])
                ]);
            } else {
                $categoryMap[$localCat->id] = $globalCatMatch->id;
            }
        }
        // ------------------------------------------------------------------

        // Retrieve all tenant (local) professions.
        $localProfessions = DB::table("{$tenantPrefix}professions")->get();
        if ($localProfessions->isEmpty()) {
            Log::warning("[mergeAll] No local professions found.");
            session()->flash('error', 'No local professions to merge.');
            return;
        }

        // Retrieve all global professions for matching.
        $globalProfessions = DB::table("global_professions")->get();
        $merged = 0;

        DB::beginTransaction();
        try {
            foreach ($localProfessions as $local) {
                $localNameArr = json_decode($local->name, true);
                $csName       = strtolower(trim($localNameArr['cs'] ?? ''));
                $enName       = strtolower(trim($localNameArr['en'] ?? ''));
                Log::info("[mergeAll] Checking Local Profession: CS='$csName', EN='$enName'");

                // Normalize available names.
                $csNameNormalized = $csName ? Str::slug($csName) : '';
                $enNameNormalized = $enName ? Str::slug($enName) : '';

                // Find best matching global profession.
                $globalMatch = null;
                foreach ($globalProfessions as $global) {
                    $globalNameArr  = json_decode($global->name, true);
                    $globalCsName   = strtolower(trim($globalNameArr['cs'] ?? ''));
                    $globalEnName   = strtolower(trim($globalNameArr['en'] ?? ''));

                    // Remove any "global" prefix if present.
                    $globalCsStripped = preg_replace('/^global/i', '', $globalCsName);
                    $globalEnStripped = preg_replace('/^global/i', '', $globalEnName);

                    $globalCsNormalized = $globalCsStripped ? Str::slug($globalCsStripped) : '';
                    $globalEnNormalized = $globalEnStripped ? Str::slug($globalEnStripped) : '';

                    $csSimilarity = 0;
                    $enSimilarity = 0;
                    similar_text($csNameNormalized, $globalCsNormalized, $csSimilarity);
                    similar_text($enNameNormalized, $globalEnNormalized, $enSimilarity);

                    if ($csSimilarity > 90 || $enSimilarity > 90) {
                        $globalMatch = $global;
                        break;
                    }
                }

                if ($globalMatch) {
                    Log::info("[mergeAll] Merging Local Profession '{$csName}' -> Global Profession ID {$globalMatch->id}");

                    // Determine the category to use.
                    // If local profession has a category, get its global mapping.
                    // Otherwise, if the global match already has a category, we keep that.
                    $mergedCategoryId = null;
                    if (!empty($local->profession_category_id)) {
                        $mergedCategoryId = $categoryMap[$local->profession_category_id] ?? null;
                    } elseif (!empty($globalMatch->profession_category_id)) {
                        $mergedCategoryId = $globalMatch->profession_category_id;
                    }

                    // If the global profession does not have a category but we got one from the local side,
                    // update the global_professions table.
                    if (empty($globalMatch->profession_category_id) && $mergedCategoryId) {
                        DB::table("global_professions")
                            ->where('id', $globalMatch->id)
                            ->update(['profession_category_id' => $mergedCategoryId]);
                    }

                    // --- Update pivot records: set global_profession_id and clear local profession_id ---
                    $linkedIdentities = DB::table("{$tenantPrefix}identity_profession")
                        ->where('profession_id', $local->id)
                        ->get();

                    foreach ($linkedIdentities as $identity) {
                        DB::table("{$tenantPrefix}identity_profession")
                            ->where('identity_id', $identity->identity_id)
                            ->where('profession_id', $local->id)
                            ->update([
                                'global_profession_id' => $globalMatch->id,
                                // Do not update any category field in the pivot (SQL structure remains unchanged)
                                'profession_id'        => null,
                            ]);
                        Log::info("[mergeAll] Updated identity {$identity->identity_id} with global_profession_id {$globalMatch->id}");
                    }
                    // ----------------------------------------------------------------------------

                    // Delete the local profession record.
                    DB::table("{$tenantPrefix}professions")->where('id', $local->id)->delete();
                    $merged++;
                } else {
                    Log::warning("[mergeAll] No global match found for '{$csName}' ({$enName}). Skipping.");
                }
            }

            // Optionally, clean up tenant categories that have become orphaned.
            $orphanedCategories = DB::table("{$tenantPrefix}profession_categories")
                ->leftJoin("{$tenantPrefix}professions", "{$tenantPrefix}profession_categories.id", "=", "{$tenantPrefix}professions.profession_category_id")
                ->whereNull("{$tenantPrefix}professions.id")
                ->select("{$tenantPrefix}profession_categories.id")
                ->get();

            foreach ($orphanedCategories as $orphan) {
                DB::table("{$tenantPrefix}profession_categories")->where('id', $orphan->id)->delete();
                Log::info("[mergeAll] Deleted orphaned category ID: {$orphan->id}");
            }

            DB::commit();
            Log::info("[mergeAll] Merge completed. Total merged professions: $merged");
            session()->flash('success', "$merged professions merged successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[mergeAll] Error during merge: " . $e->getMessage());
            session()->flash('error', 'Error during merge: ' . $e->getMessage());
        }

        $this->dispatch('refreshTable'); // Refresh the UI after merge.
    }

    protected function getListeners()
    {
        return ['refreshTable' => '$refresh', 'mergeAll' => 'mergeAll'];
    }
}
