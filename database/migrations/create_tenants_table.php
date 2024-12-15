<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('table_prefix')->unique();
                $table->integer('main_character')->nullable();
                $table->string('metadata_default_locale')->default('en');
                $table->string('version')->nullable();
                $table->boolean('show_watermark')->default(false);
                $table->string('public_url')->nullable();
                $table->timestamps();
                $table->json('data')->nullable();
            });
         } else {
            if (!Schema::hasColumn('tenants', 'data')) {
                 Schema::table('tenants', function (Blueprint $table) {
                     $table->json('data')->nullable();
                 });
             }
          }
    }
    public function down(): void {}
};
