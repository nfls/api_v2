<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistoryTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection('mysql_user')->create('weather_history', function (Blueprint $table) {
            $table->bigIncrements("id")->comment("数据ID")->unique();
            $table->unsignedInteger("station_id")->comment("气象站ID");
            $table->dateTime("update_time")->comment("更新时间");
            $table->text("update_ip")->comment("更新IP");
            $table->unsignedInteger("configuration_id")->comment("数据格式ID");
            $table->json("data")->comment("数据");
            $table->index('id');
        });
        Schema::connection('mysql_user')->create('weather_configuration', function (Blueprint $table) {
            $table->increments("id")->comment("配置ID")->unique();
            $table->unsignedBigInteger("operator")->comment("操作员");
            $table->json("configuration")->comment("配置JSON");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_user')->dropIfExists('weather_history');
        Schema::connection('mysql_user')->dropIfExists('weather_configuration');
    }
}
