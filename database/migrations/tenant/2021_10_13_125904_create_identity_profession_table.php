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
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('global_profession_id'); // Reference global professions
            $table->integer('position')->nullable();
    
            // Foreign key constraints
            $table->foreign('identity_id')
                ->references('id')
                ->on('identities')
                ->onDelete('cascade');
            
            $table->foreign('global_profession_id')
                ->references('id')
                ->on('global_professions') // Reference the global professions table
                ->onDelete('set null');
    
            // Unique constraint to avoid duplicate profession assignments
            $table->unique(['identity_id', 'global_profession_id']);
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
