<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('global_identities')){
         Schema::create('global_identities', function (Blueprint $table) {
            $table->id();
             $table->string('name');
            $table->timestamps();
        });
       }

      if(!Schema::hasTable('global_identity_keyword')){
          Schema::create('global_identity_keyword', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('keyword_id');
            $table->timestamps();
             $table->unique(['identity_id', 'keyword_id'], 'global_identity_keyword_identity_id_keyword_id_unique');
            $table->foreign('identity_id')->references('id')->on('global_identities')->onDelete('cascade');
            $table->foreign('keyword_id')->references('id')->on('global_keywords')->onDelete('cascade');
        });
     }

     if(!Schema::hasTable('global_identity_profession')){
        Schema::create('global_identity_profession', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('profession_id');
             $table->integer('position')->nullable();
              $table->timestamps();
            $table->unique(['identity_id', 'profession_id'], 'global_identity_profession_identity_id_profession_id_unique');
            $table->foreign('identity_id')->references('id')->on('global_identities')->onDelete('cascade');
            $table->foreign('profession_id')->references('id')->on('global_professions')->onDelete('cascade');
        });
     }

    if(!Schema::hasTable('global_keywords')){
        Schema::create('global_keywords', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->unsignedBigInteger('keyword_category_id')->nullable();
             $table->foreign('keyword_category_id')->references('id')->on('global_keyword_categories')->onDelete('SET NULL');
            $table->timestamps();
        });
    }
     if(!Schema::hasTable('global_keyword_categories')){
         Schema::create('global_keyword_categories', function (Blueprint $table) {
            $table->id();
             $table->json('name');
            $table->timestamps();
        });
    }
    if(!Schema::hasTable('global_keyword_letter')){
         Schema::create('global_keyword_letter', function (Blueprint $table) {
           $table->integer('keyword_id');
           $table->integer('letter_id');
           $table->datetime('created_at');
           $table->datetime('updated_at');
            $table->primary(['keyword_id', 'letter_id']);
        });
    }

    if(!Schema::hasTable('global_professions')){
        Schema::create('global_professions', function (Blueprint $table) {
            $table->id();
             $table->longText('name');
            $table->unsignedBigInteger('profession_category_id')->nullable();
              $table->foreign('profession_category_id')->references('id')->on('global_profession_categories')->onDelete('SET NULL');
            $table->timestamps();
        });
    }

    if(!Schema::hasTable('global_profession_categories')){
        Schema::create('global_profession_categories', function (Blueprint $table) {
            $table->id();
             $table->longText('name');
            $table->timestamps();
        });
    }

          if(!Schema::hasTable('identity_profession')){
           Schema::create('identity_profession', function (Blueprint $table) {
              $table->id();
              $table->unsignedBigInteger('identity_id');
              $table->unsignedBigInteger('profession_id');
               $table->integer('position')->nullable();
              $table->unique(['identity_id', 'profession_id'], 'identity_profession_identity_id_profession_id_unique');
          });
      }
    }

    public function down(): void
    {
         //
    }
};
