<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('page_locks', function (Blueprint $table) {
            $table->unsignedBigInteger('locked_by_tenant_id')->nullable()->after('locked_by_user_name');
            $table->index('locked_by_tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('page_locks', function (Blueprint $table) {
            $table->dropIndex(['locked_by_tenant_id']);
            $table->dropColumn('locked_by_tenant_id');
        });
    }
};
