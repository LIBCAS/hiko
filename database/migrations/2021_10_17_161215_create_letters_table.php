<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->timestamps();
            $table->integer('date_year')->nullable();
            $table->integer('date_month')->nullable();
            $table->integer('date_day')->nullable();
            $table->text('date_marked')->nullable();
            $table->boolean('date_uncertain')->default(0);
            $table->boolean('date_approximate')->default(0);
            $table->boolean('date_inferred')->default(0);
            $table->boolean('date_is_range')->default(0);
            $table->mediumText('date_note')->nullable();
            $table->date('date_computed')->nullable();
            $table->integer('range_year')->nullable();
            $table->integer('range_month')->nullable();
            $table->integer('range_day')->nullable();
            $table->boolean('author_inferred')->default(0);
            $table->boolean('author_uncertain')->default(0);
            $table->mediumText('author_note')->nullable();
            $table->boolean('recipient_inferred')->default(0);
            $table->boolean('recipient_uncertain')->default(0);
            $table->mediumText('recipient_note')->nullable();
            $table->boolean('destination_inferred')->default(0);
            $table->boolean('destination_uncertain')->default(0);
            $table->mediumText('destination_note')->nullable();
            $table->boolean('origin_inferred')->default(0);
            $table->boolean('origin_uncertain')->default(0);
            $table->mediumText('origin_note')->nullable();
            $table->mediumText('people_mentioned_note')->nullable();
            $table->json('copies')->nullable();
            $table->json('related_resources')->nullable();
            $table->json('abstract')->nullable();
            $table->text('explicit')->nullable();
            $table->text('incipit')->nullable();
            $table->longText('content')->nullable();
            $table->longText('history')->nullable();
            $table->text('copyright')->nullable();
            $table->text('languages')->nullable();
            $table->longText('notes_private')->nullable();
            $table->longText('notes_public')->nullable();
            $table->text('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('letters');
    }
}
