<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds 'additional_name' column to all tenant-scoped places tables.
     * This column stores an optional additional name for the place as a simple string.
     */
    public function up(): void
    {
        // Get all tenant prefixes from the tenants table
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $tableName = $tenant->table_prefix . '__places';

            // Check if the table exists before modifying it
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    // Add additional_name column after name column
                    $table->string('additional_name')->nullable()->after('name');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all tenant prefixes from the tenants table
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $tableName = $tenant->table_prefix . '__places';

            // Check if the table exists before modifying it
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('additional_name');
                });
            }
        }
    }
};
