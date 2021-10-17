<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLetterPlaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('letter_place', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unique(['letter_id', 'place_id', 'role']);
            $table->foreignId('letter_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('place_id')
                ->constrained()
                ->onDelete('cascade');
            $table->string('role', 100);
            $table->integer('position')->nullable();
            $table->text('marked')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('letter_place');
    }
}
