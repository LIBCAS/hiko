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
        // Note: Interactive user input in migrations is not standard practice.
        // It's recommended to handle such operations via Artisan commands or seeders.

        // Proceeding without interactive input for the sake of migration integrity.
        // If interactive input is essential, consider moving this logic to an Artisan command.

        // Example: Automatically run migrations without user interaction
        $this->runMigrationProcess();
    }

    private function runMigrationProcess(): void
    {
        // Define the migration type programmatically or via configuration
        // For demonstration, we'll assume running all migrations

        $this->createGlobalTables();
        $this->createEssentialTables();
    }

    private function createGlobalTables(): void
    {
        // Global Identities Table
        if (!Schema::hasTable('global_identities')) {
            Schema::create('global_identities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Global Keywords Table
        if (!Schema::hasTable('global_keywords')) {
            Schema::create('global_keywords', function (Blueprint $table) {
                $table->id();
                $table->json('name');
                $table->timestamps();
            });
        }

        // Global Keyword Categories Table
        if (!Schema::hasTable('global_keyword_categories')) {
            Schema::create('global_keyword_categories', function (Blueprint $table) {
                $table->id();
                $table->json('name');
                $table->timestamps();
            });
        }

        // Global Keyword Letter Pivot Table
        if (!Schema::hasTable('global_keyword_letter')) {
            Schema::create('global_keyword_letter', function (Blueprint $table) {
                $table->unsignedBigInteger('keyword_id');
                $table->unsignedBigInteger('letter_id');
                $table->timestamps();

                $table->primary(['keyword_id', 'letter_id']);
                $table->foreign('keyword_id')->references('id')->on('global_keywords')->onDelete('cascade');
                // Ensure 'letters' table exists before referencing
                $table->foreign('letter_id')->references('id')->on('letters')->onDelete('cascade');
            });
        }

        // Global Profession Categories Table
        if (!Schema::hasTable('global_profession_categories')) {
            Schema::create('global_profession_categories', function (Blueprint $table) {
                $table->id();
                $table->longText('name');
                $table->timestamps();
            });
        }

        // Global Professions Table
        if (!Schema::hasTable('global_professions')) {
            Schema::create('global_professions', function (Blueprint $table) {
                $table->id();
                $table->longText('name');
                $table->unsignedBigInteger('profession_category_id')->nullable();
                $table->foreign('profession_category_id')->references('id')->on('global_profession_categories')->onDelete('set null');
                $table->timestamps();
            });
        }

        // Global Identity Keyword Pivot Table
        if (!Schema::hasTable('global_identity_keyword')) {
            Schema::create('global_identity_keyword', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('identity_id');
                $table->unsignedBigInteger('keyword_id');
                $table->timestamps();

                $table->unique(['identity_id', 'keyword_id'], 'global_identity_keyword_unique');
                $table->foreign('identity_id')->references('id')->on('global_identities')->onDelete('cascade');
                $table->foreign('keyword_id')->references('id')->on('global_keywords')->onDelete('cascade');
            });
        }

        // Global Identity Profession Pivot Table
        if (!Schema::hasTable('global_identity_profession')) {
            Schema::create('global_identity_profession', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('identity_id');
                $table->unsignedBigInteger('profession_id');
                $table->integer('position')->nullable();
                $table->timestamps();

                $table->unique(['identity_id', 'profession_id'], 'global_identity_profession_unique');
                $table->foreign('identity_id')->references('id')->on('global_identities')->onDelete('cascade');
                $table->foreign('profession_id')->references('id')->on('global_professions')->onDelete('cascade');
            });
        }

        // Identity Profession Table (Non-Global)
        if (!Schema::hasTable('identity_profession')) {
            Schema::create('identity_profession', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('identity_id');
                $table->unsignedBigInteger('profession_id');
                $table->integer('position')->nullable();
                $table->timestamps();

                $table->unique(['identity_id', 'profession_id'], 'identity_profession_unique');
                $table->foreign('identity_id')->references('id')->on('global_identities')->onDelete('cascade');
                $table->foreign('profession_id')->references('id')->on('global_professions')->onDelete('cascade');
            });
        }
    }

    private function createEssentialTables(): void
    {
        // Users Table
        // if (!Schema::hasTable('users')) {
        //     Schema::create('users', function (Blueprint $table) {
        //         $table->id();
        //         $table->string('name');
        //         $table->string('email')->unique();
        //         $table->string('password');
        //         $table->string('role')->nullable();
        //         $table->timestamp('email_verified_at')->nullable();
        //         $table->rememberToken();
        //         $table->unsignedBigInteger('tenant_id')->nullable();
        //         $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
        //         $table->timestamps();
        //     });

        //     // Insert default admin user if no migrations have been run
        //     if (DB::table('migrations')->count() === 0) {
        //         DB::table('users')->insert([
        //             'name' => 'Admin User',
        //             'email' => 'admin@example.com',
        //             'password' => Hash::make('password'),
        //             'role' => 'admin',
        //             'created_at' => now(),
        //             'updated_at' => now(),
        //         ]);
        //     }
        // }

        // Tenants Table
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('table_prefix')->unique();
                $table->string('metadata_default_locale')->default('en');
                $table->json('data')->nullable();
                $table->timestamps();
            });

            // Insert default demo tenant if no migrations have been run
            if (DB::table('migrations')->count() === 0) {
                DB::table('tenants')->insert([
                    'name' => 'Demo Tenant',
                    'table_prefix' => 'demo__',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Password Resets Table
        if (!Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function (Blueprint $table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Failed Jobs Table
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // Personal Access Tokens Table
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        // Sessions Table
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('payload');
                $table->integer('last_activity')->index();
            });
        }

        // Jobs Table
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->tinyInteger('attempts')->unsigned();
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        // Domains Table
        if (!Schema::hasTable('domains')) {
            Schema::create('domains', function (Blueprint $table) {
                $table->id();
                $table->string('domain')->unique();
                $table->unsignedBigInteger('tenant_id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade')->onUpdate('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Drop Tenant-Specific Tables
        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $tenant) {
            $prefix = $tenant->table_prefix;

            Schema::dropIfExists("{$prefix}duplicates");
            Schema::dropIfExists("{$prefix}identity_letter");
            Schema::dropIfExists("{$prefix}identity_profession");
            Schema::dropIfExists("{$prefix}identity_profession_category");
            Schema::dropIfExists("{$prefix}keyword_letter");
            Schema::dropIfExists("{$prefix}keywords");
            Schema::dropIfExists("{$prefix}keyword_categories");
            Schema::dropIfExists("{$prefix}letter_place");
            Schema::dropIfExists("{$prefix}letter_user");
            Schema::dropIfExists("{$prefix}locations");
            Schema::dropIfExists("{$prefix}media");
            Schema::dropIfExists("{$prefix}places");
            Schema::dropIfExists("{$prefix}professions");
            Schema::dropIfExists("{$prefix}profession_categories");
            Schema::dropIfExists("{$prefix}letters");
            Schema::dropIfExists("{$prefix}identities");
        }

        // Drop Global Tables
        Schema::dropIfExists('global_identity_profession');
        Schema::dropIfExists('global_identity_keyword');
        Schema::dropIfExists('global_keywords');
        Schema::dropIfExists('global_keyword_categories');
        Schema::dropIfExists('global_keyword_letter');
        Schema::dropIfExists('global_professions');
        Schema::dropIfExists('global_profession_categories');
        Schema::dropIfExists('identity_profession');

        // Drop Essential Tables
        Schema::dropIfExists('domains');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('password_resets');
        // Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('global_identities');
    }
};
