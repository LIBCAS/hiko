<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $prefix = $tenant->table_prefix . '__';
            $tableName = $prefix . 'manifestations';

            // Safety check
            if (Schema::hasTable($tableName)) continue;

            Schema::create($tableName, function (Blueprint $table) use ($prefix) {
                $table->id();
                $table->unsignedBigInteger('letter_id');

                // Local Locations
                $table->unsignedBigInteger('repository_id')->nullable();
                $table->unsignedBigInteger('archive_id')->nullable();
                $table->unsignedBigInteger('collection_id')->nullable();

                // Global Locations (Reserved for next phase)
                $table->unsignedBigInteger('global_repository_id')->nullable();
                $table->unsignedBigInteger('global_archive_id')->nullable();
                $table->unsignedBigInteger('global_collection_id')->nullable();

                // Scalar fields
                $table->string('signature')->nullable();
                $table->string('type')->nullable(); // document type
                $table->string('preservation')->nullable();
                $table->string('copy')->nullable(); // mode of production
                $table->string('l_number')->nullable();
                $table->text('manifestation_notes')->nullable();
                $table->text('location_note')->nullable();

                $table->timestamps();

                // Constraints
                $table->foreign('letter_id')
                      ->references('id')
                      ->on($prefix . 'letters')
                      ->onDelete('cascade');

                $table->foreign('repository_id')->references('id')->on($prefix . 'locations')->onDelete('set null');
                $table->foreign('archive_id')->references('id')->on($prefix . 'locations')->onDelete('set null');
                $table->foreign('collection_id')->references('id')->on($prefix . 'locations')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $tenant) {
            Schema::dropIfExists($tenant->table_prefix . '__manifestations');
        }
    }
};
