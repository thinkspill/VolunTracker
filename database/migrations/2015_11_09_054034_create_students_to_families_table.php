<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStudentsToFamiliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yrcs_students_to_families', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('family_id');
            $table->unsignedInteger('student_id');
            $table->foreign('family_id')->references('id')->on('yrcs_families');
            $table->foreign('student_id')->references('id')->on('yrcs_students');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
