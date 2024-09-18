<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('global_identity_profession', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('profession_id');
            $table->integer('position')->nullable();
            $table->timestamps();

            $table->unique(['identity_id', 'profession_id']);

            $table->foreign('profession_id')
                ->references('id')
                ->on('global_professions')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('global_identity_profession');
    }
};
