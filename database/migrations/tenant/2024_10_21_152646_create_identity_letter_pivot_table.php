<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentityLetterPivotTable extends Migration
{
    public function up()
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;

        Schema::create($tenantPrefix . '__identity_letter', function (Blueprint $table) use ($tenantPrefix) {
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('letter_id');
            $table->integer('position')->nullable();
            $table->string('role')->nullable();
            $table->boolean('marked')->default(false);
            $table->string('salutation')->nullable();

            $table->foreign('identity_id')
                ->references('id')
                ->on($tenantPrefix . '__identities')
                ->onDelete('cascade');

            $table->foreign('letter_id')
                ->references('id')
                ->on($tenantPrefix . '__letters')
                ->onDelete('cascade');

            $table->primary(['identity_id', 'letter_id']);
        });
    }

    public function down()
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;

        Schema::dropIfExists($tenantPrefix . '__identity_letter');
    }
}
