<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWeatherDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_user')->create('weather_station', function (Blueprint $table) {
            $table->increments('id')->unique();
            $table->text("name")->comment("名称");
            $table->double("latitude")->comment("纬度");
            $table->double("longitude")->comment("经度");
            $table->double("altitude")->comment("海拔高度");
            $table->mediumInteger("added_by")->comment("添加用户");
            $table->text("update_key")->comment("更新密钥");
            $table->smallInteger("data_structure")->nullable()->comment("传感器及数据结构ID");
            $table->boolean("isEnabled")->default(true)->comment("是否启用");
            $table->boolean("isVisible")->default(true)->comment("是否可见");
            $table->dateTime("lastupdate")->nullable()->comment("最近一次更新");
            $table->text("lastupdate_ip")->nullable()->comment("最近一次更新IP");
            $table->json("data")->nullable()->comment("最新数据");
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
        Schema::connection('mysql_user')->dropIfExists('weather_station');
    }
}
