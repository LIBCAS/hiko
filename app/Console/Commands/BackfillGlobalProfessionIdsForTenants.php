<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillGlobalProfessionIdsForTenants extends Command
{
    protected $signature = 'backfill:global-profession-ids-tenants';
    protected $description = 'Backfill global profession and profession category IDs for all tenants';

    public function handle()
    {
        // Fetch all tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("Backfilling for tenant: {$tenant->name}");

            try {
                // No need for tenancy()->initialize(), as the DatabaseTenancyBootstrapper will handle prefixing
                
                // Check if the required tables exist for the tenant
                if (!Schema::hasTable('identity_profession') || !Schema::hasTable('professions')) {
                    $this->warn("Skipping tenant {$tenant->name} as required tables do not exist.");
                    continue;
                }

                // Fetch tenant-specific professions and map them by name
                $tenantProfessions = DB::table('professions')->pluck('id', 'name');

                // Fetch global professions and map them by name using the central connection
                $globalProfessions = DB::table('mysql')->pluck('id', 'name');

                // Update tenant identity_profession table with matching global professions
                foreach ($tenantProfessions as $name => $professionId) {
                    if (isset($globalProfessions[$name])) {
                        DB::table('identity_profession')
                            ->where('profession_id', $professionId)
                            ->update(['global_profession_id' => $globalProfessions[$name]]);
                        $this->info("Updated profession '{$name}' with global ID for tenant: {$tenant->name}");
                    } else {
                        $this->warn("No global profession match found for '{$name}' in tenant {$tenant->name}.");
                    }
                }

                // Check if the required tables for profession categories exist
                if (Schema::hasTable('identity_profession_category') && Schema::hasTable('profession_categories')) {
                    // Fetch tenant-specific categories and global categories
                    $tenantCategories = DB::table('profession_categories')->pluck('id', 'name');
                    $globalCategories = DB::connection('mysql')->table('global_profession_categories')->pluck('id', 'name');

                    // Update tenant identity_profession_category table with matching global categories
                    foreach ($tenantCategories as $name => $categoryId) {
                        if (isset($globalCategories[$name])) {
                            DB::table('identity_profession_category')
                                ->where('profession_category_id', $categoryId)
                                ->update(['global_profession_category_id' => $globalCategories[$name]]);
                            $this->info("Updated profession category '{$name}' with global ID for tenant: {$tenant->name}");
                        } else {
                            $this->warn("No global profession category match found for '{$name}' in tenant {$tenant->name}.");
                        }
                    }
                } else {
                    $this->warn("Skipping profession categories for tenant {$tenant->name} as required tables do not exist.");
                }
            } catch (\Exception $e) {
                $this->error("An error occurred for tenant {$tenant->name}: " . $e->getMessage());
            }
        }

        $this->info('Global profession and category IDs backfilled for all tenants.');
    }
}
