<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCaptchaDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_user')->create('user_session', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unique();
            $table->string("phrase");
            $table->string("ip");
            $table->dateTime("valid_before");
            $table->index('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_user')->dropIfExists('user_session');
    }
}
