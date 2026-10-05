<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GlobalProfessionCategoryIdentityListing
{
    /** Local identities grouped by tenant */
    public function forProfessions(array $professionIds): Collection
    {
        if ($professionIds === []) {
            return collect();
        }

        $groups = collect();
        $domains = DB::table('domains')->orderBy('id')->get()->groupBy('tenant_id');

        foreach (DB::table('tenants')->orderBy('name')->orderBy('id')->get() as $tenant) {
            $prefix = $tenant->table_prefix . '__';
            if (
                !Schema::hasTable($prefix . 'identity_profession')
                || !Schema::hasColumn($prefix . 'identity_profession', 'global_profession_id')
            ) {
                continue;
            }

            try {
                // DISTINCT applies within this tenant, never across tenant identity IDs.
                $identities = DB::table($prefix . 'identity_profession as assignments')
                    ->join($prefix . 'identities as identities', 'assignments.identity_id', '=', 'identities.id')
                    ->whereIn('assignments.global_profession_id', $professionIds)
                    ->select('identities.id', 'identities.name')
                    ->distinct()
                    ->orderBy('identities.name')
                    ->orderBy('identities.id')
                    ->get();

                if ($identities->isNotEmpty()) {
                    // Query each tenant once even when it has several domains.
                    $groups->push((object) [
                        'tenant_id' => $tenant->id,
                        'is_current_tenant' => tenancy()->initialized
                            && (string) tenancy()->tenant->table_prefix === (string) $tenant->table_prefix,
                        'tenant_name' => $tenant->name,
                        'tenant_prefix' => $tenant->table_prefix,
                        'tenant_domain' => $domains->get($tenant->id, collect())->first()?->domain,
                        'identities' => $identities,
                    ]);
                }
            } catch (\Exception $exception) {
                Log::error("Error querying tenant {$tenant->name}: " . $exception->getMessage());
            }
        }

        // Keep the database's alphabetical order within the remaining tenants.
        [$current, $others] = $groups->partition(fn($group) => $group->is_current_tenant);

        return $current->concat($others)->values();
    }
}
