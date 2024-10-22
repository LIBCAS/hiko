<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentityProfessionPivotTable extends Migration
{
    public function up()
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;

        Schema::create($tenantPrefix . '__identity_profession', function (Blueprint $table) use ($tenantPrefix) {
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('profession_id');
            $table->integer('position')->nullable();

            $table->foreign('identity_id')
                ->references('id')
                ->on($tenantPrefix . '__identities')
                ->onDelete('cascade');

            $table->foreign('profession_id')
                ->references('id')
                ->on($tenantPrefix . '__professions')
                ->onDelete('cascade');

            $table->primary(['identity_id', 'profession_id']);
        });
    }

    public function down()
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;

        Schema::dropIfExists($tenantPrefix . '__identity_profession');
    }
}
