<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessProfessionBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenantPrefix;
    protected $professions; // Collection or array of tenant professions to process

    /**
     * @param string $tenantPrefix The tenant prefix (e.g. "tenant__")
     * @param mixed  $professions  Collection or array of tenant professions to process
     */
    public function __construct(string $tenantPrefix, $professions)
    {
        $this->tenantPrefix = $tenantPrefix;
        $this->professions   = $professions;
    }

    public function handle()
    {
        Log::info("[ProcessProfessionBatch] Starting batch processing for tenant: {$this->tenantPrefix}");

        // Get all global professions for matching.
        $globalProfessions = DB::table("global_professions")->get();

        // --- Merge Categories for this batch ---
        $localCategories  = DB::table("{$this->tenantPrefix}profession_categories")->get();
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
        // --------------------------------------------------------------------

        $merged = 0;
        DB::beginTransaction();
        try {
            foreach ($this->professions as $local) {
                $localNameArr = json_decode($local->name, true);
                $csName       = strtolower(trim($localNameArr['cs'] ?? ''));
                $enName       = strtolower(trim($localNameArr['en'] ?? ''));
                Log::info("[ProcessProfessionBatch] Checking Local Profession: CS='$csName', EN='$enName'");

                $csNameNormalized = $csName ? Str::slug($csName) : '';
                $enNameNormalized = $enName ? Str::slug($enName) : '';

                $globalMatch = null;
                foreach ($globalProfessions as $global) {
                    $globalNameArr = json_decode($global->name, true);
                    $globalCsName  = strtolower(trim($globalNameArr['cs'] ?? ''));
                    $globalEnName  = strtolower(trim($globalNameArr['en'] ?? ''));

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
                    Log::info("[ProcessProfessionBatch] Merging Local Profession '{$csName}' -> Global Profession ID {$globalMatch->id}");

                    $mergedCategoryId = null;
                    if (!empty($local->profession_category_id)) {
                        $mergedCategoryId = $categoryMap[$local->profession_category_id] ?? null;
                    } elseif (!empty($globalMatch->profession_category_id)) {
                        $mergedCategoryId = $globalMatch->profession_category_id;
                    }

                    // If global profession is missing a category and we have one from the local side, update it.
                    if (empty($globalMatch->profession_category_id) && $mergedCategoryId) {
                        DB::table("global_professions")
                            ->where('id', $globalMatch->id)
                            ->update(['profession_category_id' => $mergedCategoryId]);
                    }

                    // --- Update pivot records: update global_profession_id and clear local profession_id ---
                    $linkedIdentities = DB::table("{$this->tenantPrefix}identity_profession")
                        ->where('profession_id', $local->id)
                        ->get();

                    foreach ($linkedIdentities as $identity) {
                        DB::table("{$this->tenantPrefix}identity_profession")
                            ->where('identity_id', $identity->identity_id)
                            ->where('profession_id', $local->id)
                            ->update([
                                'global_profession_id' => $globalMatch->id,
                                // Do not update any pivot category field.
                                'profession_id'        => null,
                            ]);
                        Log::info("[ProcessProfessionBatch] Updated identity {$identity->identity_id} with global_profession_id {$globalMatch->id}");
                    }
                    // ----------------------------------------------------------------------------

                    // Delete the local profession record.
                    DB::table("{$this->tenantPrefix}professions")->where('id', $local->id)->delete();
                    $merged++;
                } else {
                    Log::warning("[ProcessProfessionBatch] No global match found for '{$csName}' ({$enName}). Skipping.");
                }
            }
            DB::commit();
            Log::info("[ProcessProfessionBatch] Batch completed. Total merged professions: $merged");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[ProcessProfessionBatch] Error during merge: " . $e->getMessage());
            throw $e;
        }
    }
}
