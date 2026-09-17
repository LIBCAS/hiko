<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\NationalitySchema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('nationalities')) DB::statement(NationalitySchema::catalogue());
        $tables = DB::select("SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'");
        foreach ($tables as $table) {
            if ($table->name !== 'global_identities' && !str_ends_with($table->name, '__identities')) continue;
            if (!Schema::hasTable(NationalitySchema::pivotName($table->name))) DB::statement(NationalitySchema::pivot($table->name));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Nationality data requires an explicit reviewed rollback; tables are retained.');
    }
};
