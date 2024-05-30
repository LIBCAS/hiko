<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKeywordLetterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('keyword_letter', function (Blueprint $table) {
            $table->id();
            $table->unique(['keyword_id', 'letter_id']);
            $table->foreignId('keyword_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('letter_id')
                ->constrained()
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('keyword_letter');
    }
}
