<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
			$table->string('content',1000);
			$table->integer('aolapply_id')->unsigned();
			$table->integer('author_id')->unsigned();
			// 是否审核通过
			$table->boolean('is_pass')->nullable();
			$table->integer('commentable_id')->unsigned();
			$table->string('commentable_type');
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
        Schema::dropIfExists('comments');
    }
}
