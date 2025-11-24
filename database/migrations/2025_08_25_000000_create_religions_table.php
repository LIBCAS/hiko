<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('religions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug', 120)->unique();
            $table->string('name', 255);
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            // Denormalized path for search/display (updated by service)
            $table->string('path_text', 1024)->nullable()->index();
            $table->string('lower_path_text', 1024)->nullable()->index();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('religions');
    }
};
