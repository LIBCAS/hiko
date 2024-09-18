<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillGlobalProfessionIdsForTenants extends Command
{
    protected $signature = 'backfill:global-profession-ids-tenants';
    protected $description = 'Backfill global profession and profession category IDs for all tenants';

    public function handle()
    {
        $tenants = DB::table('tenants')->pluck('table_prefix');

        foreach ($tenants as $tenant) {
            $this->info("Backfilling for tenant: $tenant");

            if (!Schema::hasTable("{$tenant}__identity_profession") || !Schema::hasTable("{$tenant}__professions")) {
                $this->warn("Skipping tenant $tenant as required tables do not exist.");
                continue;
            }

            $professions = DB::table("{$tenant}__identity_profession")
                ->join("{$tenant}__professions", "{$tenant}__identity_profession.profession_id", '=', "{$tenant}__professions.id")
                ->get(["{$tenant}__identity_profession.id", "{$tenant}__professions.name"]);

            foreach ($professions as $profession) {
                $globalProfession = DB::table('global_professions')->where('name', $profession->name)->first();

                if ($globalProfession) {
                    DB::table("{$tenant}__identity_profession")
                        ->where('id', $profession->id)
                        ->update(['global_profession_id' => $globalProfession->id]);
                }
            }

            $categories = DB::table("{$tenant}__identity_profession_category")
                ->join("{$tenant}__profession_categories", "{$tenant}__identity_profession_category.profession_category_id", '=', "{$tenant}__profession_categories.id")
                ->get(["{$tenant}__identity_profession_category.id", "{$tenant}__profession_categories.name"]);

            foreach ($categories as $category) {
                $globalCategory = DB::table('global_profession_categories')->where('name', $category->name)->first();

                if ($globalCategory) {
                    DB::table("{$tenant}__identity_profession_category")
                        ->where('id', $category->id)
                        ->update(['global_profession_category_id' => $globalCategory->id]);
                }
            }
        }

        $this->info('Global profession and category IDs backfilled for all tenants.');
    }
}
