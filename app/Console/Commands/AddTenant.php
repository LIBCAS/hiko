<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AddTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new tenant and create its associated tables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Prompt for tenant name
        $tenantName = $this->ask('Enter new tenant name');

        // Validate tenant name
        if (empty($tenantName)) {
            $this->error('Tenant name cannot be empty.');
            return 1;
        }

        // Generate a unique prefix based on tenant name
        $prefix = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tenantName)) . '__';

        // Check if the prefix already exists
        if (DB::table('tenants')->where('table_prefix', $prefix)->exists()) {
            $this->error("Tenant prefix '{$prefix}' already exists.");
            return 1;
        }

        Log::debug("Starting tenant addition for '{$tenantName}' with prefix '{$prefix}'.");

        try {
            // **Step 1: Insert Tenant Within a Transaction**
            DB::beginTransaction();
            $tenantId = DB::table('tenants')->insertGetId([
                'name' => $tenantName,
                'table_prefix' => $prefix,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();

            $this->info("Tenant '{$tenantName}' added successfully with prefix '{$prefix}'.");
            Log::debug("Inserted tenant with ID {$tenantId}.");

            // **Step 2: Create Tenant-Specific Tables Outside the Transaction**
            try {
                $this->createTenantTables($prefix);
                $this->info('Tenant-specific tables created successfully.');
                Log::debug("Tenant-specific tables created for prefix '{$prefix}'.");
            } catch (Exception $e) {
                // **Step 3: Handle Table Creation Failure**
                // Rollback tenant insertion if table creation fails
                DB::table('tenants')->where('id', $tenantId)->delete();
                Log::error("Table creation failed for tenant '{$tenantName}': " . $e->getMessage());
                throw $e; // Re-throw to be caught by the outer catch
            }

            Log::debug("Tenant addition completed successfully for '{$tenantName}'.");

            return 0;
        } catch (Exception $e) {
            // **Step 4: Robust Error Handling**
            // Check if a transaction is active before attempting to rollback
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
                Log::debug("Rolled back transaction due to error.");
            }

            // Log the error for debugging
            Log::error("Failed to add tenant '{$tenantName}': " . $e->getMessage());

            // Display error message to the user
            $this->error('An error occurred while adding the tenant: ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * Create tenant-specific tables with the given prefix.
     *
     * @param string $prefix
     * @return void
     */
    private function createTenantTables(string $prefix): void
    {
        // **Order of Table Creation to Satisfy Foreign Key Dependencies**

        // 1. Users Table (Create before any tables that reference it)
        if (!Schema::hasTable("{$prefix}users")) {
            Schema::create("{$prefix}users", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('remember_token', 100)->nullable();
                $table->string('role')->nullable();
                $table->timestamp('deactivated_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Password Resets Table
        if (!Schema::hasTable("{$prefix}password_resets")) {
            Schema::create("{$prefix}password_resets", function (Blueprint $table) use ($prefix) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // 3. Personal Access Tokens Table
        if (!Schema::hasTable("{$prefix}personal_access_tokens")) {
            Schema::create("{$prefix}personal_access_tokens", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Sessions Table
        if (!Schema::hasTable("{$prefix}sessions")) {
            Schema::create("{$prefix}sessions", function (Blueprint $table) use ($prefix) {
                $table->string('id')->primary();
                $table->bigInteger('user_id')->nullable()->unsigned();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('payload');
                $table->integer('last_activity');
            });
        }

        // 5. Migrations Table
        if (!Schema::hasTable("{$prefix}migrations")) {
            Schema::create("{$prefix}migrations", function (Blueprint $table) use ($prefix) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
        }

        // 6. Profession Categories Table
        if (!Schema::hasTable("{$prefix}profession_categories")) {
            Schema::create("{$prefix}profession_categories", function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->longText('name');
                $table->timestamps();
            });
        }

        // 7. Professions Table
        if (!Schema::hasTable("{$prefix}professions")) {
            Schema::create("{$prefix}professions", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->longText('name');
                $table->unsignedBigInteger('profession_category_id')->nullable();
                $table->timestamps();

                $table->foreign('profession_category_id')
                      ->references('id')
                      ->on("{$prefix}profession_categories")
                      ->onDelete('set null');
            });
        }

        // 8. Keyword Categories Table
        if (!Schema::hasTable("{$prefix}keyword_categories")) {
            Schema::create("{$prefix}keyword_categories", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->longText('name');
                $table->timestamps();
            });
        }

        // 9. Keywords Table
        if (!Schema::hasTable("{$prefix}keywords")) {
            Schema::create("{$prefix}keywords", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->longText('name');
                $table->unsignedBigInteger('keyword_category_id')->nullable();
                $table->timestamps();

                $table->foreign('keyword_category_id')
                      ->references('id')
                      ->on("{$prefix}keyword_categories")
                      ->onDelete('set null');
            });
        }

        // 10. Places Table
        if (!Schema::hasTable("{$prefix}places")) {
            Schema::create("{$prefix}places", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('country')->nullable();
                $table->string('division')->nullable();
                $table->text('note')->nullable();
                $table->double('latitude')->nullable();
                $table->double('longitude')->nullable();
                $table->integer('geoname_id')->nullable();
                $table->json('alternative_names')->nullable();
                $table->timestamps();
            });
        }

        // 11. Locations Table
        if (!Schema::hasTable("{$prefix}locations")) {
            Schema::create("{$prefix}locations", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('type');
                $table->timestamps();
            });
        }

        // 12. Letters Table
        if (!Schema::hasTable("{$prefix}letters")) {
            Schema::create("{$prefix}letters", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->timestamps();
                // Add other necessary columns as per your application requirements
                $table->integer('date_year')->nullable();
                $table->integer('date_month')->nullable();
                $table->integer('date_day')->nullable();
                $table->text('date_marked')->nullable();
                $table->boolean('date_uncertain')->default(false);
                $table->boolean('date_approximate')->default(false);
                $table->boolean('date_inferred')->default(false);
                $table->boolean('date_is_range')->default(false);
                $table->mediumText('date_note')->nullable();
                $table->date('date_computed')->nullable();
                $table->integer('range_year')->nullable();
                $table->integer('range_month')->nullable();
                $table->integer('range_day')->nullable();
                $table->boolean('author_inferred')->default(false);
                $table->boolean('author_uncertain')->default(false);
                $table->mediumText('author_note')->nullable();
                $table->boolean('recipient_inferred')->default(false);
                $table->boolean('recipient_uncertain')->default(false);
                $table->mediumText('recipient_note')->nullable();
                $table->boolean('destination_inferred')->default(false);
                $table->boolean('destination_uncertain')->default(false);
                $table->mediumText('destination_note')->nullable();
                $table->boolean('origin_inferred')->default(false);
                $table->boolean('origin_uncertain')->default(false);
                $table->mediumText('origin_note')->nullable();
                $table->mediumText('people_mentioned_note')->nullable();
                $table->longText('copies')->nullable();
                $table->longText('related_resources')->nullable();
                $table->longText('abstract')->nullable();
                $table->text('explicit')->nullable();
                $table->text('incipit')->nullable();
                $table->longText('content')->nullable();
                $table->longText('content_stripped')->nullable();
                $table->longText('history')->nullable();
                $table->text('copyright')->nullable();
                $table->text('languages')->nullable();
                $table->longText('notes_private')->nullable();
                $table->longText('notes_public')->nullable();
                $table->text('status')->nullable();
            });
        }

        // 13. Identities Table
        if (!Schema::hasTable("{$prefix}identities")) {
            Schema::create("{$prefix}identities", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('surname')->nullable();
                $table->string('forename')->nullable();
                $table->string('general_name_modifier')->nullable();
                $table->longText('alternative_names')->collation('utf8mb4_bin')->nullable();
                $table->string('type');
                $table->string('nationality')->nullable();
                $table->string('gender')->nullable();
                $table->string('birth_year')->nullable();
                $table->string('death_year')->nullable();
                $table->longText('related_names')->collation('utf8mb4_bin')->nullable();
                $table->longText('related_identity_resources')->collation('utf8mb4_bin')->nullable();
                $table->string('viaf_id')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        // 14. Identity Profession Table
        if (!Schema::hasTable("{$prefix}identity_profession")) {
            Schema::create("{$prefix}identity_profession", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('identity_id');
                $table->unsignedBigInteger('profession_id')->nullable();
                $table->unsignedBigInteger('global_profession_id')->nullable();
                $table->integer('position')->nullable();
                $table->timestamps();

                $table->unique(['identity_id', 'profession_id'], "{$prefix}identity_profession_unique");

                $table->foreign('identity_id')
                      ->references('id')
                      ->on("{$prefix}identities")
                      ->onDelete('cascade');

                $table->foreign('profession_id')
                      ->references('id')
                      ->on("{$prefix}professions")
                      ->onDelete('cascade');

                $table->foreign('global_profession_id')
                      ->references('id')
                      ->on('global_professions')
                      ->onDelete('set null');
            });
        }

        // 15. Identity Letter Pivot Table
        if (!Schema::hasTable("{$prefix}identity_letter")) {
            Schema::create("{$prefix}identity_letter", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('identity_id');
                $table->unsignedBigInteger('letter_id');
                $table->string('role', 100)->nullable();
                $table->integer('position')->nullable();
                $table->text('marked')->nullable();
                $table->text('salutation')->nullable();
                $table->timestamps();

                $table->unique(['identity_id', 'letter_id', 'role'], "{$prefix}identity_letter_unique");

                $table->foreign('identity_id')
                      ->references('id')
                      ->on("{$prefix}identities")
                      ->onDelete('cascade');

                $table->foreign('letter_id')
                      ->references('id')
                      ->on("{$prefix}letters")
                      ->onDelete('cascade');
            });
        }

        // 16. Keyword Letter Pivot Table
        if (!Schema::hasTable("{$prefix}keyword_letter")) {
            Schema::create("{$prefix}keyword_letter", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('keyword_id');
                $table->unsignedBigInteger('letter_id');
                $table->timestamps();

                $table->unique(['keyword_id', 'letter_id'], "{$prefix}keyword_letter_unique");

                $table->foreign('keyword_id')
                      ->references('id')
                      ->on("{$prefix}keywords")
                      ->onDelete('cascade');

                $table->foreign('letter_id')
                      ->references('id')
                      ->on("{$prefix}letters")
                      ->onDelete('cascade');
            });
        }

        // 17. Letter Place Pivot Table
        if (!Schema::hasTable("{$prefix}letter_place")) {
            Schema::create("{$prefix}letter_place", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('letter_id');
                $table->unsignedBigInteger('place_id');
                $table->string('role', 100);
                $table->integer('position')->nullable();
                $table->text('marked')->nullable();
                $table->timestamps();

                $table->unique(['letter_id', 'place_id', 'role'], "{$prefix}letter_place_unique");

                $table->foreign('letter_id')
                      ->references('id')
                      ->on("{$prefix}letters")
                      ->onDelete('cascade');

                $table->foreign('place_id')
                      ->references('id')
                      ->on("{$prefix}places")
                      ->onDelete('cascade');
            });
        }

        // 18. Letter User Pivot Table
        if (!Schema::hasTable("{$prefix}letter_user")) {
            Schema::create("{$prefix}letter_user", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('letter_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->unique(['letter_id', 'user_id'], "{$prefix}letter_user_unique");

                $table->foreign('letter_id')
                      ->references('id')
                      ->on("{$prefix}letters")
                      ->onDelete('cascade');

                $table->foreign('user_id')
                      ->references('id')
                      ->on("{$prefix}users")
                      ->onDelete('cascade');
            });
        }

        // 19. Media Table
        if (!Schema::hasTable("{$prefix}media")) {
            Schema::create("{$prefix}media", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->string('collection_name');
                $table->string('name');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->string('disk');
                $table->unsignedBigInteger('size');
                $table->longText('manipulations')->collation('utf8mb4_bin');
                $table->longText('custom_properties')->collation('utf8mb4_bin');
                $table->longText('responsive_images')->collation('utf8mb4_bin');
                $table->unsignedInteger('order_column')->nullable();
                $table->timestamps();

                $table->index(['model_type', 'model_id'], "{$prefix}media_model_index");
            });
        }

        // 20. Duplicates Table
        if (!Schema::hasTable("{$prefix}duplicates")) {
            Schema::create("{$prefix}duplicates", function (Blueprint $table) use ($prefix) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('letter1_id');
                $table->unsignedBigInteger('letter2_id');
                $table->string('letter1_prefix');
                $table->string('letter2_prefix');
                $table->decimal('similarity', 5, 3);
                $table->timestamps();

                $table->foreign('letter1_id')
                      ->references('id')
                      ->on("{$prefix}letters")
                      ->onDelete('cascade');

                $table->foreign('letter2_id')
                      ->references('id')
                      ->on("{$prefix}letters")
                      ->onDelete('cascade');
            });
        }
    }
}
