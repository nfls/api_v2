<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFaceRegoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_user')->create('user_face', function (Blueprint $table) {
            $table->increments('id')->unique();
            $table->integer("belonged_to");
            $table->string("file_name");
            $table->string("recog_name")->nullable();
            $table->double("exec_time")->nullable();
            $table->string("feedback")->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_user')->dropIfExists('user_face');
    }
}
