<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentityProfessionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identity_profession', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unique(['identity_id', 'profession_id']);
            $table->foreignId('identity_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('profession_id')
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
        Schema::dropIfExists('identity_profession');
    }
}
