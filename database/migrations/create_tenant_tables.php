<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
         $tenants = DB::table('tenants')->get();
         $migrationsCount = DB::table('migrations')->count();

        // If tenants exists ask for new tenant only if migrations are already done
         if (Schema::hasTable('tenants') && DB::table('tenants')->count() > 0 && $migrationsCount > 0) {
             // Ask for new tenant name
             $newTenantName = readline("Enter new tenant name: ");
             if($newTenantName){
                $prefix = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $newTenantName)).'__';

                 DB::table('tenants')->insert([
                     'name' => $newTenantName,
                     'table_prefix' => $prefix,
                     'created_at' => now(),
                     'updated_at' => now()
                ]);

            }
         }

        foreach ($tenants as $tenant) {
            $prefix = $tenant->table_prefix . '__';

            if (!Schema::hasTable($prefix . 'identities')) {
                Schema::create($prefix . 'identities', function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                    $table->string('name');
                    $table->string('surname')->nullable();
                    $table->string('forename')->nullable();
                    $table->string('general_name_modifier')->nullable();
                    $table->longText('alternative_names')->nullable();
                    $table->longText('related_names')->nullable();
                    $table->string('type');
                    $table->string('nationality')->nullable();
                    $table->string('gender')->nullable();
                    $table->string('birth_year')->nullable();
                    $table->string('death_year')->nullable();
                    $table->longText('related_identity_resources')->nullable();
                    $table->string('viaf_id')->nullable();
                    $table->text('note')->nullable();
                });
            }

            if (!Schema::hasTable($prefix . 'identity_letter')) {
                Schema::create($prefix . 'identity_letter', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('identity_id');
                    $table->unsignedBigInteger('letter_id');
                    $table->string('role', 100)->nullable();
                    $table->integer('position')->nullable();
                    $table->text('marked')->nullable();
                     $table->text('salutation')->nullable();
                    $table->unique(['identity_id', 'letter_id', 'role'], 'identity_letter_identity_id_letter_id_role_unique');
                    $table->foreign('identity_id')->references('id')->on($prefix . 'identities')->onDelete('cascade');
                    $table->foreign('letter_id')->references('id')->on($prefix . 'letters')->onDelete('cascade');
                });
            }


             if (!Schema::hasTable($prefix . 'identity_profession')) {
                Schema::create($prefix . 'identity_profession', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('identity_id');
                    $table->unsignedBigInteger('profession_id');
                      $table->unsignedBigInteger('global_profession_id')->nullable();
                    $table->integer('position')->nullable();
                    $table->unique(['identity_id', 'profession_id'], 'identity_profession_identity_id_profession_id_unique');
                    $table->foreign('identity_id')->references('id')->on($prefix . 'identities')->onDelete('cascade');
                    $table->foreign('profession_id')->references('id')->on($prefix . 'professions')->onDelete('cascade');
                    $table->foreign('global_profession_id')->references('id')->on('global_professions')->onDelete('SET NULL');
                });
             }


            if (!Schema::hasTable($prefix . 'identity_profession_category')) {
                    Schema::create($prefix . 'identity_profession_category', function (Blueprint $table) {
                        $table->id();
                        $table->unsignedBigInteger('identity_id');
                        $table->unsignedBigInteger('profession_category_id');
                        $table->integer('position')->nullable();
                         $table->unique(['identity_id', 'profession_category_id'], 'ipc');
                        $table->foreign('identity_id')->references('id')->on($prefix . 'identities')->onDelete('cascade');
                        $table->foreign('profession_category_id')->references('id')->on($prefix . 'profession_categories')->onDelete('cascade');
                    });
             }

            if (!Schema::hasTable($prefix . 'keywords')) {
                Schema::create($prefix . 'keywords', function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                    $table->longText('name');
                    $table->unsignedBigInteger('keyword_category_id')->nullable();
                    $table->foreign('keyword_category_id')->references('id')->on($prefix . 'keyword_categories');
                });
            }

            if (!Schema::hasTable($prefix . 'keyword_categories')) {
                 Schema::create($prefix . 'keyword_categories', function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                    $table->longText('name');
                 });
            }

            if (!Schema::hasTable($prefix . 'keyword_letter')) {
                    Schema::create($prefix . 'keyword_letter', function (Blueprint $table) {
                        $table->id();
                        $table->unsignedBigInteger('keyword_id');
                        $table->unsignedBigInteger('letter_id');
                         $table->timestamps();
                        $table->unique(['keyword_id', 'letter_id'], 'keyword_letter_keyword_id_letter_id_unique');
                        $table->foreign('keyword_id')->references('id')->on($prefix . 'keywords')->onDelete('cascade');
                        $table->foreign('letter_id')->references('id')->on($prefix . 'letters')->onDelete('cascade');
                    });
                }

            if (!Schema::hasTable($prefix . 'letters')) {
                Schema::create($prefix . 'letters', function (Blueprint $table) {
                    $table->id();
                    $table->uuid('uuid');
                    $table->timestamps();
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

            if (!Schema::hasTable($prefix . 'letter_place')) {
                Schema::create($prefix . 'letter_place', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('letter_id');
                    $table->unsignedBigInteger('place_id');
                    $table->string('role', 100);
                    $table->integer('position')->nullable();
                     $table->text('marked')->nullable();
                    $table->unique(['letter_id', 'place_id', 'role'], 'letter_place_letter_id_place_id_role_unique');
                    $table->foreign('letter_id')->references('id')->on($prefix . 'letters')->onDelete('cascade');
                    $table->foreign('place_id')->references('id')->on($prefix . 'places')->onDelete('cascade');
                });
            }

            if (!Schema::hasTable($prefix . 'letter_user')) {
                Schema::create($prefix . 'letter_user', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('letter_id');
                    $table->unsignedBigInteger('user_id');
                    $table->unique(['letter_id', 'user_id'], 'letter_user_letter_id_user_id_unique');
                    $table->foreign('letter_id')->references('id')->on($prefix . 'letters')->onDelete('cascade');
                    $table->foreign('user_id')->references('id')->on($prefix . 'users')->onDelete('cascade');
                });
            }

            if (!Schema::hasTable($prefix . 'locations')) {
                Schema::create($prefix . 'locations', function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                    $table->string('name');
                    $table->string('type');
                 });
            }

            if (!Schema::hasTable($prefix . 'media')) {
                Schema::create($prefix . 'media', function (Blueprint $table) {
                    $table->id();
                    $table->string('model_type');
                    $table->unsignedBigInteger('model_id');
                    $table->string('collection_name');
                    $table->string('name');
                    $table->string('file_name');
                    $table->string('mime_type')->nullable();
                    $table->string('disk');
                    $table->unsignedBigInteger('size');
                    $table->longText('manipulations');
                    $table->longText('custom_properties');
                    $table->longText('responsive_images');
                    $table->unsignedInteger('order_column')->nullable();
                    $table->timestamps();
                    $table->index(['model_type', 'model_id']);
                });
            }

            if (!Schema::hasTable($prefix . 'places')) {
                 Schema::create($prefix . 'places', function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                    $table->string('name');
                    $table->string('country')->nullable();
                    $table->string('division')->nullable();
                    $table->text('note')->nullable();
                    $table->double('latitude')->nullable();
                    $table->double('longitude')->nullable();
                    $table->integer('geoname_id')->nullable();
                     $table->json('alternative_names')->nullable();
                 });
            }

            if (!Schema::hasTable($prefix . 'professions')) {
                Schema::create($prefix . 'professions', function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                    $table->longText('name');
                    $table->unsignedBigInteger('profession_category_id')->nullable();
                    $table->foreign('profession_category_id')->references('id')->on($prefix . 'profession_categories');
                 });
            }

             if (!Schema::hasTable($prefix . 'profession_categories')) {
                 Schema::create($prefix . 'profession_categories', function (Blueprint $table) {
                        $table->id();
                        $table->timestamps();
                        $table->longText('name');
                 });
            }
             if (!Schema::hasTable($prefix . 'duplicates')) {
                Schema::create($prefix . 'duplicates', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('letter1_id');
                    $table->unsignedBigInteger('letter2_id');
                    $table->string('letter1_prefix');
                    $table->string('letter2_prefix');
                    $table->decimal('similarity', 5, 3);
                    $table->timestamps();
                });
            }
        }
    }
     public function down(): void
    {
       //
    }
};
