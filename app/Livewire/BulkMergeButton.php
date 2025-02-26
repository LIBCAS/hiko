<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkMergeButton extends Component
{
    public string $model;  // Model name (e.g., 'professions', 'keywords')
    public string $table;  // Table name (e.g., 'professions', 'keywords')
    public string $pivotTable; // Pivot table name

    protected $listeners = ['mergeAll'];

    public function mount(string $model)
    {
        $this->model = $model;

        // Define table and pivot table based on model type
        $this->table = match ($model) {
            'professions' => 'professions',
            'keywords' => 'keywords',
            default => throw new \Exception("Invalid model type: $model")
        };

        $this->pivotTable = match ($model) {
            'professions' => 'identity_profession',
            'keywords' => 'identity_keyword',
            default => throw new \Exception("Invalid pivot table for model: $model")
        };
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
                    ->where('profession_id', $local->id) // ✅ Fixed incorrect column name
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
    
                // **STEP 4: Remove local profession reference and delete the local profession itself**
                DB::table("{$tenantPrefix}identity_profession")
                    ->where('profession_id', $local->id) // ✅ Fixed incorrect column name
                    ->update(['profession_id' => null]);
    
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

    public function render()
    {
        return view('livewire.bulk-merge-button');
    }
}
