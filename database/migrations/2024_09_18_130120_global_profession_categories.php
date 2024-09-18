<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GlobalIdentityProfessionCategory extends Migration
{
    public function up()
    {
        Schema::create('global_identity_profession_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('profession_category_id');
            $table->integer('position')->nullable();
            $table->timestamps();

            $table->unique(['identity_id', 'profession_category_id']);

            $table->foreign('profession_category_id')
                ->references('id')
                ->on('global_profession_categories')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('global_identity_profession_category');
    }
}
