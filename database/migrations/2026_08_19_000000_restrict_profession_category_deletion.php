<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->changeDeleteRule('global_professions', 'global_profession_categories', 'restrict');

        if (!Schema::hasTable('tenants')) {
            return;
        }

        foreach (DB::table('tenants')->get(['table_prefix']) as $tenant) {
            $this->changeDeleteRule(
                $tenant->table_prefix . '__professions',
                $tenant->table_prefix . '__profession_categories',
                'restrict'
            );
        }
    }

    public function down(): void
    {
        $this->changeDeleteRule('global_professions', 'global_profession_categories', 'set null');

        if (!Schema::hasTable('tenants')) {
            return;
        }

        foreach (DB::table('tenants')->get(['table_prefix']) as $tenant) {
            $this->changeDeleteRule(
                $tenant->table_prefix . '__professions',
                $tenant->table_prefix . '__profession_categories',
                'set null'
            );
        }
    }

    private function changeDeleteRule(string $table, string $categoriesTable, string $deleteRule): void
    {
        if (DB::getDriverName() !== 'mysql'
            || !Schema::hasTable($table)
            || !Schema::hasColumn($table, 'profession_category_id')
            || !Schema::hasTable($categoriesTable)) {
            return;
        }

        $constraintName = $this->foreignKeyName($table);

        if ($constraintName !== null) {
            Schema::table($table, function (Blueprint $blueprint) use ($constraintName): void {
                $blueprint->dropForeign($constraintName);
            });
        } else {
            $constraintName = 'profession_category_' . substr(md5($table), 0, 24) . '_fk';
        }

        Schema::table($table, function (Blueprint $blueprint) use ($categoriesTable, $constraintName, $deleteRule): void {
            $foreign = $blueprint->foreign('profession_category_id', $constraintName)
                ->references('id')
                ->on($categoriesTable);

            if ($deleteRule === 'set null') {
                $foreign->nullOnDelete();
            } else {
                $foreign->restrictOnDelete();
            }
        });
    }

    private function foreignKeyName(string $table): ?string
    {
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = 'profession_category_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$table]
        );

        return $constraints[0]->CONSTRAINT_NAME ?? null;
    }
};
