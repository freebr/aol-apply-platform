<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAolappliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aolapplies', function (Blueprint $table) {
            $table->increments('id');
			// 申请状态
			// 1=待导师审核
			// 2=待课程组审核
			// 3=导师审核不通过
			// 4=导师审核通过，待课程组审核
			// 5=全部课程组审核不通过
			// 6=有课程组审核通过
			$table->integer('status')->unsigned()->default(0);
			$table->integer('stu_id')->unsigned();
			$table->string('stu_name', 100);
			$table->string('stu_no', 50);
			$table->integer('gender')->unsigned();
			$table->integer('tutor_id')->unsigned();
			$table->string('school', 50)->default('工商管理学院');
			$table->string('speciality');
			$table->string('mobile', 11);
			$table->string('id_card_no', 18);
			$table->string('bank_account', 50);
			
			$table->integer('work_at')->unsigned();
            
			$table->text('memo')->nullable();
			
			$table->softDeletes();
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
        Schema::dropIfExists('aolapplies');
    }
}
