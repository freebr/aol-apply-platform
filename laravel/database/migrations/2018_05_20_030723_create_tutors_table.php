<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTutorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		return;
        Schema::create('tutors', function (Blueprint $table) {
            $table->increments('id');
			$table->integer('account_id')->unsigned();
			$table->string('account', 50);
			$table->string('name', 50);
			$table->integer('gender')->unsigned();
			$table->string('department', 50)->nullable();
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
        //Schema::dropIfExists('tutors');
    }
}
