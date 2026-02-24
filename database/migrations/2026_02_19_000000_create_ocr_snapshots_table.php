<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ocr_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('tenant_prefix')->nullable();
            $table->unsignedBigInteger('letter_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->string('status', 30)->default('success');
            $table->json('source_files')->nullable();
            $table->longText('recognized_text')->nullable();
            $table->json('metadata')->nullable();
            $table->json('mapped_fields')->nullable();
            $table->text('request_prompt')->nullable();
            $table->text('raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->unsignedBigInteger('applied_by_user_id')->nullable();
            $table->string('apply_mode', 30)->nullable();
            $table->json('applied_field_keys')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'letter_id', 'created_at'], 'ocr_snapshots_tenant_letter_created_idx');
            $table->index(['provider', 'model', 'created_at'], 'ocr_snapshots_model_created_idx');
            $table->index(['status', 'created_at'], 'ocr_snapshots_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_snapshots');
    }
};
