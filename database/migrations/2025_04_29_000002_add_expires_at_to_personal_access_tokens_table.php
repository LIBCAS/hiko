<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// This migration is for adding the 'expires_at' column to the 'personal_access_tokens' table.
// It is assumed that the '__personal_access_tokens' table already exists in the tenant database.
return new class extends Migration
{
    public function up()
    {
        Schema::table(tenant()->table('personal_access_tokens'), function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('last_used_at');
        });
    }

    public function down()
    {
        Schema::table(tenant()->table('personal_access_tokens'), function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
