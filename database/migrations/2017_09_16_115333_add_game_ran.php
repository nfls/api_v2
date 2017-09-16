<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGameRan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection("mysql_user")->table("user_list", function (Blueprint $table) {
            $table->bigInteger("score")->default(0)->comment("游戏分数");
            $table->time("lastPlayed")->nullable()->comment("上次游戏时间");
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
        Schema::connection("mysql_user")->table("user_list", function (Blueprint $table) {
            $table->dropColumn("score");
            $table->dropColumn("lastPlayed");
        });
    }
}
