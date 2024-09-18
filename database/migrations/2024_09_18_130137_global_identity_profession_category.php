<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GlobalProfessionCategories extends Migration
{
    public function up()
    {
        Schema::create('global_profession_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('global_profession_categories');
    }
}
