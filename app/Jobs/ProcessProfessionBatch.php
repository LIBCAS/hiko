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
    protected $professions;

    public function __construct(string $tenantPrefix, $professions)
    {
        $this->tenantPrefix = $tenantPrefix;
        $this->professions = $professions;
    }

    public function handle()
    {
        Log::info("[ProcessProfessionBatch] Starting batch processing for tenant: {$this->tenantPrefix}");

        // Get all global professions for matching
        $globalProfessions = DB::table("global_professions")->get();

         // Category Handling
         $localCategories = DB::table("{$this->tenantPrefix}profession_categories")->get();
         $globalCategories = DB::table("global_profession_categories")->get();

         // Map local categories to global
         $categoryMap = [];
         foreach ($localCategories as $localCat) {
             $localCatName = json_decode($localCat->name, true);
             $localCsName = strtolower(trim($localCatName['cs'] ?? ''));
             $localEnName = strtolower(trim($localCatName['en'] ?? ''));

             // Find matching global category
             $globalCatMatch = null;
             foreach ($globalCategories as $globalCat) {
                 $globalCatName = json_decode($globalCat->name, true);
                 $globalCsName = strtolower(trim($globalCatName['cs'] ?? ''));
                 $globalEnName = strtolower(trim($globalCatName['en'] ?? ''));

                 $csSimilarity = 0;
                 $enSimilarity = 0;

                 // Declare variables *before* passing by reference
                 $tempCsSimilarity = 0;
                 $tempEnSimilarity = 0;

                 similar_text($localCsName, $globalCsName, &$tempCsSimilarity);
                 similar_text($localEnName, $globalEnName, &$tempEnSimilarity);

                 $csSimilarity = $tempCsSimilarity;
                 $enSimilarity = $tempEnSimilarity;

                 if ($csSimilarity > 90 || $enSimilarity > 90) {
                     $globalCatMatch = $globalCat;
                     break;
                 }
             }

             // If no match, create a new global category
             if (!$globalCatMatch) {
                 $newGlobalCatId = DB::table("global_profession_categories")->insertGetId([
                     'name' => json_encode(['cs' => $localCsName, 'en' => $localEnName]),
                     'created_at' => now(),
                     'updated_at' => now(),
                 ]);
                 $categoryMap[$localCat->id] = $newGlobalCatId;
             } else {
                 $categoryMap[$localCat->id] = $globalCatMatch->id;
             }
         }


        $merged = 0;
        DB::beginTransaction();
        try {
             foreach ($this->professions as $local) {
                 $localNameJson = json_decode($local->name, true);
                 $csName = strtolower(trim($localNameJson['cs'] ?? ''));
                 $enName = strtolower(trim($localNameJson['en'] ?? ''));

                 Log::info("[ProcessProfessionBatch] Checking Local Profession: CS='$csName', EN='$enName'");

                 // Normalize text for comparison
                 $csNameNormalized = Str::slug($csName);
                 $enNameNormalized = Str::slug($enName);

                 // Find best match using enhanced name matching
                 $globalMatch = null;
                 foreach ($globalProfessions as $global) {
                     $globalNameJson = json_decode($global->name, true);
                     $globalCsName = strtolower(trim($globalNameJson['cs'] ?? ''));
                     $globalEnName = strtolower(trim($globalNameJson['en'] ?? ''));

                     // Normalize global names
                     $globalCsStripped = preg_replace('/^global/i', '', $globalCsName);
                     $globalEnStripped = preg_replace('/^global/i', '', $globalEnName);

                     $globalCsStrippedNormalized = Str::slug($globalCsStripped);
                     $globalEnStrippedNormalized = Str::slug($globalEnStripped);

                     // Check for exact or fuzzy matches with higher similarity
                     $csSimilarity = 0;
                     $enSimilarity = 0;

                     // Declare temporary variables for passing by reference
                     $tempCsSimilarity = 0;
                     $tempEnSimilarity = 0;

                     similar_text($csNameNormalized, $globalCsStrippedNormalized, &$tempCsSimilarity);
                     similar_text($enNameNormalized, $globalEnStrippedNormalized, &$tempEnSimilarity);

                     $csSimilarity = $tempCsSimilarity;
                     $enSimilarity = $tempEnSimilarity;


                     if ($csSimilarity > 90 || $enSimilarity > 90) {
                         $globalMatch = $global;
                         break;
                     }
                 }
                 if ($globalMatch) {
                     Log::info("[ProcessProfessionBatch] Merging Local Profession '{$csName}' -> Global Profession ID {$globalMatch->id}");

                     // **STEP 1: Find identities linked to the local profession**
                     $linkedIdentities = DB::table("{$this->tenantPrefix}identity_profession")
                         ->where('profession_id', $local->id)
                         ->get();

                     foreach ($linkedIdentities as $identity) {
                         // **STEP 2: Check if this identity already has a global_profession_id**
                         $existingGlobal = DB::table("{$this->tenantPrefix}identity_profession")
                             ->where('identity_id', $identity->identity_id)
                             ->whereNotNull('global_profession_id')
                             ->first();

                         if ($existingGlobal) {
                             Log::info("[ProcessProfessionBatch] Identity {$identity->identity_id} already has global_profession_id: {$existingGlobal->global_profession_id}");
                         } else {
                             // **STEP 3: Reassign the global_profession_id to the identity_id of local profession**
                             DB::table("{$this->tenantPrefix}identity_profession")
                                 ->where('identity_id', $identity->identity_id)
                                 ->update(['global_profession_id' => $globalMatch->id]);

                             Log::info("[ProcessProfessionBatch] Reassigned global_profession_id {$globalMatch->id} to identity_id {$identity->identity_id}");
                         }
                     }
                     // **STEP 4: Delete local profession reference**
                     DB::table("{$this->tenantPrefix}identity_profession")
                     ->where('profession_id', $local->id)
                     ->delete();

                     // **STEP 5: Delete the local profession itself**
                     DB::table("{$this->tenantPrefix}professions")->where('id', $local->id)->delete();

                     $merged++;
                 } else {
                     Log::warning("[ProcessProfessionBatch] No global match found for '{$csName}' ({$enName}). Skipping.");
                 }
             }
            DB::commit();
             Log::info("[ProcessProfessionBatch] Batch completed. Total merged: $merged");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[ProcessProfessionBatch] Error during merge: " . $e->getMessage());
            throw $e; // Re-throw the exception to mark the job as failed
        }
    }
}
