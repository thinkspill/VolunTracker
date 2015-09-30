<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateYrcsFamiliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yrcs_families', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->string('family_hash');
            $table->string('family_hash_id');

            $table->unique(['family_hash_id']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('yrcs_families');
    }
}
