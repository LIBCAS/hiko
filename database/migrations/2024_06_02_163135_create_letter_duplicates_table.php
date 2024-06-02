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
                    // $table->foreign('letter1_id')->references('id')->on('letters')->onDelete('cascade');
                    // $table->foreign('letter2_id')->references('id')->on('letters')->onDelete('cascade');
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
