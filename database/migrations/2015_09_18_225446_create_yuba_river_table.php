<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateYubaRiverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yuba_river', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->string('student_last');
            $table->string('student_first');
            $table->string('parent_last');
            $table->string('parent_first');
            $table->string('grade');
            $table->string('relationship');
            $table->string('city');

            $table->unique(['parent_last', 'parent_first', 'student_first']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('yuba_river');
    }
}
