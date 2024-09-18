<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddGlobalForeignKeysForTenants extends Command
{
    protected $signature = 'migrate:tenants-global-foreign-keys';
    protected $description = 'Add global foreign keys to all tenant identity profession tables';

    public function handle()
    {
        $tenants = DB::table('tenants')->pluck('table_prefix');

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: $tenant");

            $identityProfessionTable = $tenant . '__identity_profession';
            $identityProfessionCategoryTable = $tenant . '__identity_profession_category';

            if (Schema::hasTable($identityProfessionTable)) {
                Schema::table($identityProfessionTable, function ($table) {
                    if (!Schema::hasColumn($table->getTable(), 'global_profession_id')) {
                        $table->unsignedBigInteger('global_profession_id')->nullable();

                        $table->foreign('global_profession_id', 'gp_id_fk')
                              ->references('id')
                              ->on('global_professions')
                              ->onDelete('set null');
                    }
                });
            }

            if (Schema::hasTable($identityProfessionCategoryTable)) {
                Schema::table($identityProfessionCategoryTable, function ($table) {
                    if (!Schema::hasColumn($table->getTable(), 'global_profession_category_id')) {
                        $table->unsignedBigInteger('global_profession_category_id')->nullable();

                        $table->foreign('global_profession_category_id', 'gpc_id_fk')
                              ->references('id')
                              ->on('global_profession_categories')
                              ->onDelete('set null');
                    }
                });
            }
        }

        $this->info('Global foreign keys added to all tenant tables.');
    }
}
