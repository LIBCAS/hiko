<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('forename')->nullable();
            $table->json('alternative_names')->nullable();
            $table->string('type');
            $table->string('nationality')->nullable();
            $table->string('gender')->nullable();
            $table->string('birth_year')->nullable();
            $table->string('death_year')->nullable();
            $table->string('viaf_id')->nullable();
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('identities');
    }
}
