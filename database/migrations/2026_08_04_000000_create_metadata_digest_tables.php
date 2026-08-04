<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('metadata_digest_runs')) {
            Schema::create('metadata_digest_runs', function (Blueprint $table) {
                $table->id();
                $table->string('trigger', 20);
                $table->string('deduplication_key')->nullable()->unique();
                $table->timestamp('period_start');
                $table->timestamp('period_end');
                $table->string('status', 20)->default('pending');
                $table->unsignedInteger('requested_from_tenant_id')->nullable();
                $table->unsignedBigInteger('requested_by_user_id')->nullable();
                $table->string('requested_by_name')->nullable();
                $table->string('requested_by_email')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->foreign('requested_from_tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->nullOnDelete();
                $table->index(['trigger', 'period_start', 'period_end'], 'digest_run_period_idx');
            });
        }

        if (!Schema::hasTable('metadata_digest_deliveries')) {
            Schema::create('metadata_digest_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('metadata_digest_run_id')
                    ->constrained('metadata_digest_runs')
                    ->cascadeOnDelete();
                $table->string('recipient_email');
                $table->string('normalized_email');
                $table->string('recipient_name')->nullable();
                $table->string('locale', 5)->default('cs');
                $table->json('tenant_ids');
                $table->string('status', 20)->default('pending');
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->timestamp('available_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->json('totals')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->unique(
                    ['metadata_digest_run_id', 'normalized_email'],
                    'digest_delivery_run_email_unique'
                );
                $table->index(['status', 'available_at'], 'digest_delivery_ready_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata_digest_deliveries');
        Schema::dropIfExists('metadata_digest_runs');
    }
};
