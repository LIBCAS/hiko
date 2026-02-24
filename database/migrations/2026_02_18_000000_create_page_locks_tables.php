<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('page_locks', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('tenant_prefix')->nullable();
            $table->string('resource_type', 100);
            $table->string('resource_id', 100)->nullable();
            $table->string('resource_fingerprint', 255)->unique();
            $table->unsignedBigInteger('locked_by_user_id');
            $table->string('locked_by_user_email')->nullable();
            $table->string('locked_by_user_name')->nullable();
            $table->timestamp('locked_at');
            $table->timestamp('heartbeat_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['scope', 'tenant_id', 'resource_type']);
        });

        Schema::create('page_lock_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('tenant_prefix')->nullable();
            $table->string('resource_type', 100);
            $table->string('resource_id', 100)->nullable();
            $table->string('resource_fingerprint', 255)->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_name')->nullable();
            $table->string('event', 50);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_lock_audit_logs');
        Schema::dropIfExists('page_locks');
    }
};

