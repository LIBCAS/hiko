<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfessionCategoryProfessionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profession_category_profession', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profession_category_id')
                  ->constrained('profession_categories') // References blekastad__profession_categories due to prefix
                  ->onDelete('cascade');
            $table->foreignId('profession_id')
                  ->constrained('professions') // References blekastad__professions due to prefix
                  ->onDelete('cascade');
            $table->timestamps();
    
            $table->unique(['profession_category_id', 'profession_id']);
        });
    }    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profession_category_profession');
    }
}
