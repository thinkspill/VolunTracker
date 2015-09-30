<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateYrcsGuardiansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yrcs_guardians', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->string('first');
            $table->string('last');
            $table->string('relationship');
            $table->string('family_hash_id');
            $table->unique(['first', 'last', 'family_hash_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('yrcs_guardians');
    }
}
