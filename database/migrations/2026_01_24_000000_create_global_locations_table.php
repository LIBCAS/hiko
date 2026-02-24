<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('global_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // repository, collection, archive
            $table->timestamps();

            $table->index(['name', 'type']);
        });

        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $tenant) {
            $prefix = $tenant->table_prefix . '__';
            $tableName = $prefix . 'manifestations';

            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreign('global_repository_id')->references('id')->on('global_locations')->onDelete('set null');
                    $table->foreign('global_archive_id')->references('id')->on('global_locations')->onDelete('set null');
                    $table->foreign('global_collection_id')->references('id')->on('global_locations')->onDelete('set null');
                });
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('global_locations');
    }
};
