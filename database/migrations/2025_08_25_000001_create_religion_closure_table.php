<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('religion_closure', function (Blueprint $table) {
            $table->unsignedBigInteger('ancestor_id');
            $table->unsignedBigInteger('descendant_id');
            $table->unsignedInteger('depth'); // 0=self
            $table->primary(['ancestor_id', 'descendant_id']);
            $table->index(['descendant_id', 'depth']);
            $table->index(['ancestor_id', 'depth']);
            $table->foreign('ancestor_id')->references('id')->on('religions')->onDelete('cascade');
            $table->foreign('descendant_id')->references('id')->on('religions')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('religion_closure');
    }
};
