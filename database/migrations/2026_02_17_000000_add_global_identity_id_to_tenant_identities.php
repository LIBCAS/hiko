<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $table = $tenant->table_prefix . '__identities';
            $fkName = $tenant->table_prefix . '_identities_global_identity_fk';

            if (!Schema::hasTable($table)) {
                continue;
            }

            if (!Schema::hasColumn($table, 'global_identity_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('global_identity_id')->nullable()->after('viaf_id');
                });
            }

            $exists = DB::select(
                "SELECT CONSTRAINT_NAME
                 FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                   AND CONSTRAINT_NAME = ?",
                [$table, $fkName]
            );

            if (empty($exists)) {
                Schema::table($table, function (Blueprint $blueprint) use ($fkName) {
                    $blueprint->foreign('global_identity_id', $fkName)
                        ->references('id')
                        ->on('global_identities')
                        ->onDelete('set null');
                });
            }
        }
    }

    public function down(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $table = $tenant->table_prefix . '__identities';
            $fkName = $tenant->table_prefix . '_identities_global_identity_fk';

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'global_identity_id')) {
                continue;
            }

            $exists = DB::select(
                "SELECT CONSTRAINT_NAME
                 FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                   AND CONSTRAINT_NAME = ?",
                [$table, $fkName]
            );

            Schema::table($table, function (Blueprint $blueprint) use ($fkName, $exists) {
                if (!empty($exists)) {
                    $blueprint->dropForeign($fkName);
                }
                $blueprint->dropColumn('global_identity_id');
            });
        }
    }
};

