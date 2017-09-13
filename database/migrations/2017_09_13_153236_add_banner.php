<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBanner extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection("mysql_user")->table("system_message", function (Blueprint $table){
            $table->integer("zone")->comment("区域");
            $table->text("push")->nullable()->comment("推送消息");
            $table->text("pic")->nullable()->comment("图片地址");
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
        Schema::connection("mysql_user")->table("system_message", function (Blueprint $table){
            $table->removeColumn("zone");
            $table->removeColumn("push");
            $table->removeColumn("pic");
        });
    }
}
