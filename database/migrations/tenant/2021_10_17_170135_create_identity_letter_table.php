<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentityLetterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identity_letter', function (Blueprint $table) {
            $table->id();
            $table->unique(['identity_id', 'letter_id', 'role']);
            $table->foreignId('identity_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('letter_id')
                ->constrained()
                ->onDelete('cascade');
            $table->string('role', 100)->nullable();
            $table->integer('position')->nullable();
            $table->text('marked')->nullable();
            $table->text('salutation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('identity_letter');
    }
}
