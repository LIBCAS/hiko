<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentityProfessionCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identity_profession_category', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unique(['identity_id', 'profession_category_id'], 'ipc');
            $table->foreignId('identity_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('profession_category_id')
                ->constrained()
                ->onDelete('cascade');
            $table->integer('position')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('identity_profession_categories');
    }
}
