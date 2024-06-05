<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateLetterDuplicatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $tableName = $tenant->table_prefix . '__duplicates';

            if (!Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('letter1_id');
                    $table->unsignedBigInteger('letter2_id');
                    $table->string('letter1_prefix');
                    $table->string('letter2_prefix');
                    $table->decimal('similarity', 5, 3);
                    $table->timestamps();
                    $table->index(['letter1_id', 'letter2_id']);
                });
            } else {
                // Add indexes to existing table if they don't already exist
                Schema::table($tableName, function (Blueprint $table) {
                    if (!Schema::hasColumn($tableName, 'letter1_id')) {
                        $table->index(['letter1_id', 'letter2_id']);
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $tableName = $tenant->table_prefix . '__duplicates';

            if (Schema::hasTable($tableName)) {
                Schema::dropIfExists($tableName);
            }
        }
    }
}
