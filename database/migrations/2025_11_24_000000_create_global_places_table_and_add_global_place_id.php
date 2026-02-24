<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create global_places table
        Schema::create('global_places', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->text('additional_name')->nullable();
            $table->string('country')->nullable();
            $table->string('division')->nullable();
            $table->text('note')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->json('alternative_names')->nullable();
            $table->integer('geoname_id')->nullable();

            // Index for faster lookups during merging
            $table->index(['name', 'country', 'latitude', 'longitude'], 'global_places_merge_lookup');
        });

        // Add global_place_id column and make place_id nullable in all tenant letter_place pivot tables
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $prefix = $tenant->table_prefix;
            $pivotTable = "{$prefix}__letter_place";

            if (Schema::hasTable($pivotTable)) {
                // Drop the foreign key constraint on place_id first
                $foreignKeyName = $this->getPlaceIdForeignKeyName($pivotTable, $prefix);
                if ($foreignKeyName) {
                    Schema::table($pivotTable, function (Blueprint $table) use ($foreignKeyName) {
                        $table->dropForeign($foreignKeyName);
                    });
                }

                // Now modify place_id to be nullable and add global_place_id
                Schema::table($pivotTable, function (Blueprint $table) {
                    $table->unsignedBigInteger('place_id')->nullable()->change();
                    $table->unsignedBigInteger('global_place_id')->nullable()->after('place_id');
                });

                // Add foreign key constraints
                Schema::table($pivotTable, function (Blueprint $table) use ($prefix) {
                    // Re-add the place_id foreign key with nullable support
                    $table->foreign('place_id')
                        ->references('id')
                        ->on("{$prefix}__places")
                        ->onDelete('cascade');

                    // Add the global_place_id foreign key
                    $table->foreign('global_place_id')
                        ->references('id')
                        ->on('global_places')
                        ->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Get the foreign key constraint name for place_id.
     */
    protected function getPlaceIdForeignKeyName(string $table, string $prefix): ?string
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = 'place_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table]);

        return $constraints[0]->CONSTRAINT_NAME ?? null;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove global_place_id from all tenant letter_place pivot tables
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $prefix = $tenant->table_prefix;
            $pivotTable = "{$prefix}__letter_place";

            if (Schema::hasTable($pivotTable)) {
                Schema::table($pivotTable, function (Blueprint $table) {
                    $table->dropIndex('fk_global_place');
                    $table->dropColumn('global_place_id');
                });
            }
        }

        // Drop global_places table
        Schema::dropIfExists('global_places');
    }
};
