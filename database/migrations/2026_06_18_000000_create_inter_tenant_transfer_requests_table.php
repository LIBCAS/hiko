<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inter_tenant_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('source_tenant_id');
            $table->unsignedInteger('target_tenant_id');
            $table->string('entity_type')->default('letters');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->string('requested_by_name');
            $table->string('requested_by_email')->nullable();
            $table->unsignedBigInteger('decided_by_user_id')->nullable();
            $table->string('decided_by_name')->nullable();
            $table->string('decided_by_email')->nullable();
            $table->json('source_record_ids');
            $table->json('filters')->nullable();
            $table->json('mappings')->nullable();
            $table->json('result')->nullable();
            $table->longText('final_snapshot')->nullable();
            $table->text('decision_reason')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('source_tenant_id')->references('id')->on('tenants');
            $table->foreign('target_tenant_id')->references('id')->on('tenants');
            $table->index(['target_tenant_id', 'status', 'created_at'], 'transfer_target_status_idx');
            $table->index(['source_tenant_id', 'status', 'created_at'], 'transfer_source_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inter_tenant_transfer_requests');
    }
};
