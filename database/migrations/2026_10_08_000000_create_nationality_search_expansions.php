<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nationality_search_expansions', function (Blueprint $table) {
            $table->foreignId('source_nationality_id')->constrained('nationalities')->cascadeOnDelete();
            $table->foreignId('target_nationality_id')->constrained('nationalities')->cascadeOnDelete();
            $table->primary(['source_nationality_id', 'target_nationality_id'], 'nationality_expansion_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nationality_search_expansions');
    }
};
