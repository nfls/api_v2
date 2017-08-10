<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAppLoginImg extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection('mysql_user')->create('app_startup_pics', function (Blueprint $table) {
            $table->increments("id")->comment("数据ID")->unique();
            $table->dateTime("update_time")->comment("更新时间");
            $table->text("text")->nullable()->comment("说明文本");
            $table->integer("有效用户组")->defualt(-1)->comment("适用用户组");
            $table->dateTime("valid_after")->nullable()->comment("开始有效时间");
            $table->dateTime("invalid_after")->nullable()->comment("结束有效时间");
            $table->text("url")->comment("图片地址");
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
        Schema::connection('mysql_user')->dropIfExists('app_startup_pics');
    }
}
