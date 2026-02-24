<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merge_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('tenant_prefix')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('entity');
            $table->string('operation');
            $table->string('status');
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_prefix', 'entity', 'operation'], 'merge_audit_logs_scope_idx');
            $table->index(['status', 'created_at'], 'merge_audit_logs_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merge_audit_logs');
    }
};
