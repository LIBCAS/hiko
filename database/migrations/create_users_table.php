<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
                $table->string('role')->nullable();
                $table->timestamp('deactivated_at')->nullable();
                $table->unsignedInteger('tenant_id')->nullable();
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('SET NULL');
            });
          if(DB::table('migrations')->count() == 0){
              DB::table('users')->insert([
                   'name' => 'Admin User',
                   'email' => 'admin@example.com',
                   'password' => Hash::make('password'),
                   'role' => 'admin',
                   'created_at' => now(),
                   'updated_at' => now(),
              ]);
              if(Schema::hasTable('tenants') && DB::table('tenants')->count() == 0){
                   DB::table('tenants')->insert([
                        'name' => 'Demo Tenant',
                        'table_prefix' => 'demo',
                        'created_at' => now(),
                        'updated_at' => now()
                  ]);
              }
            }
        } else {
            if (!Schema::hasColumn('users', 'tenant_id')) {
                 Schema::table('users', function (Blueprint $table) {
                    $table->unsignedInteger('tenant_id')->nullable();
                    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('SET NULL');
                 });
            }
              if (!Schema::hasColumn('users', 'role')) {
                 Schema::table('users', function (Blueprint $table) {
                    $table->string('role')->nullable();
                 });
              }
        }
    }
    public function down(): void {}
};
