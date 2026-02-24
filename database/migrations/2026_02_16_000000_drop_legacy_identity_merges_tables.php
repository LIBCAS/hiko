<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $databaseName = DB::getDatabaseName();

        $tables = DB::select(
            'SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE ?',
            [$databaseName, '%__identity_merges']
        );

        foreach ($tables as $table) {
            Schema::dropIfExists($table->TABLE_NAME);
        }
    }

    public function down(): void
    {
        // Legacy table intentionally not recreated.
    }
};

