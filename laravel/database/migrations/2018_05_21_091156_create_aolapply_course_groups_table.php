<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAolapplyCourseGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aolapply_course_groups', function (Blueprint $table) {
            $table->integer('aolapply_id')->unsigned();
			$table->integer('course_group_id')->unsigned();
			$table->integer('course_group_teacher_id')->unsigned();
			$table->integer('course_group_class_count')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aolapply_course_groups');
    }
}
