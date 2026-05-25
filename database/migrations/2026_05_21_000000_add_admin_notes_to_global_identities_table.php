<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('global_identities', 'admin_notes')) {
            return;
        }

        Schema::table('global_identities', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('global_identities', 'admin_notes')) {
            return;
        }

        Schema::table('global_identities', function (Blueprint $table) {
            $table->dropColumn('admin_notes');
        });
    }
};
