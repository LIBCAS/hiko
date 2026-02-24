<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('religion_translations', function (Blueprint $table) {
            $table->unsignedBigInteger('religion_id');
            $table->char('locale', 2);                   // 'cs', 'en'
            $table->string('name', 255)->nullable();     // allow NULL for fresh nodes (# placeholders)
            $table->string('slug', 160)->nullable();
            $table->string('path_text', 512)->nullable();
            $table->string('lower_path_text', 512)->nullable();
            $table->timestamps();

            $table->primary(['religion_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->index(['locale', 'lower_path_text']);

            $table->foreign('religion_id')
                ->references('id')->on('religions')
                ->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('religion_translations');
    }
};
