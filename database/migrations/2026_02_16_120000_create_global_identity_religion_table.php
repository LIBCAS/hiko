<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('global_identity_religion')) {
            Schema::create('global_identity_religion', function (Blueprint $table) {
                $table->unsignedBigInteger('global_identity_id');
                $table->unsignedBigInteger('religion_id');
                $table->primary(['global_identity_id', 'religion_id'], 'gir_primary');
                $table->index('religion_id', 'idx_global_identity_religion_religion');

                $table->foreign('global_identity_id', 'gir_global_identity_fk')
                    ->references('id')
                    ->on('global_identities')
                    ->onDelete('cascade');

                $table->foreign('religion_id', 'gir_religion_fk')
                    ->references('id')
                    ->on('religions')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('global_identity_religion');
    }
};

