<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create global_identities table
        Schema::create('global_identities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('forename')->nullable();
            $table->string('general_name_modifier')->nullable();
            $table->json('alternative_names')->nullable();
            $table->json('related_names')->nullable();
            $table->string('type'); // person, institution
            $table->string('nationality')->nullable();
            $table->string('gender')->nullable();
            $table->string('birth_year')->nullable();
            $table->string('death_year')->nullable();
            $table->json('related_identity_resources')->nullable();
            $table->string('viaf_id')->nullable();
            $table->text('note')->nullable();

            // Indexes for searching
            $table->index(['name', 'type']);
        });

        // Create global_identity_profession pivot table
        Schema::create('global_identity_profession', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('global_identity_id');
            $table->unsignedBigInteger('global_profession_id');
            $table->integer('position')->nullable();
            $table->timestamps();

            $table->foreign('global_identity_id')
                ->references('id')
                ->on('global_identities')
                ->onDelete('cascade');

            $table->foreign('global_profession_id')
                ->references('id')
                ->on('global_professions')
                ->onDelete('cascade');

            $table->unique(['global_identity_id', 'global_profession_id'], 'gip_unique');
        });

        // Update tenant identity_letter tables
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $prefix = $tenant->table_prefix;
            $pivotTable = "{$prefix}__identity_letter";

            if (Schema::hasTable($pivotTable)) {
                // Drop existing FK for identity_id to modify the column
                $fkName = $this->getForeignKeyName($pivotTable, 'identity_id');
                if ($fkName) {
                    Schema::table($pivotTable, function (Blueprint $table) use ($fkName) {
                        $table->dropForeign($fkName);
                    });
                }

                Schema::table($pivotTable, function (Blueprint $table) {
                    // Make identity_id nullable (for records using global identity)
                    $table->unsignedBigInteger('identity_id')->nullable()->change();
                    // Add global_identity_id
                    $table->unsignedBigInteger('global_identity_id')->nullable()->after('identity_id');
                });

                Schema::table($pivotTable, function (Blueprint $table) use ($prefix) {
                    // Restore identity_id FK
                    $table->foreign('identity_id')
                        ->references('id')
                        ->on("{$prefix}__identities")
                        ->onDelete('cascade');

                    // Add global_identity_id FK
                    $table->foreign('global_identity_id')
                        ->references('id')
                        ->on('global_identities')
                        ->onDelete('cascade');
                });
            }
        }
    }

    protected function getForeignKeyName(string $table, string $column): ?string
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);

        return $constraints[0]->CONSTRAINT_NAME ?? null;
    }

    public function down(): void
    {
        // Revert tenant changes
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $prefix = $tenant->table_prefix;
            $pivotTable = "{$prefix}__identity_letter";

            if (Schema::hasTable($pivotTable)) {
                // Remove global ID and foreign key
                $fkName = $this->getForeignKeyName($pivotTable, 'global_identity_id');
                if ($fkName) {
                    Schema::table($pivotTable, function (Blueprint $table) use ($fkName) {
                        $table->dropForeign($fkName);
                    });
                }

                Schema::table($pivotTable, function (Blueprint $table) {
                    $table->dropColumn('global_identity_id');
                });

                // Note: We cannot easily make identity_id NOT NULL again without cleaning data first
                // skipping strict revert of nullable status to avoid data loss errors in down()
            }
        }

        Schema::dropIfExists('global_identity_profession');
        Schema::dropIfExists('global_identities');
    }
};
