<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMediaTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection('mysql_media')->create("videos", function(Blueprint $table){
           $table->increments("id")->comment("ID");
           $table->text("cat")->comment("分区");
           $table->dateTime("time")->nullable()->comment("上传时间");
           $table->text("avid")->comment("av号");
           $table->text("uploader")->comment("UP主");
           $table->integer("group_id")->default(-1)->comment("组号");
        });
        Schema::connection("mysql_alumni")->table("user_auth", function (Blueprint $table){
            $table->integer("privacy")->nullable()->comment("隐私设置");
            $table->json("student_info")->nullable()->comment("学生认知信息");
            $table->boolean("student_auth")->default(false)->comment("学生认证通过");
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
        Schema::connection('mysql_media')->dropIfExists('videos');
        Schema::connection("mysql_alumni")->table("user_auth", function (Blueprint $table){
            $table->dropColumn("privacy");
            $table->dropColumn("student_info");
            $table->dropColumn("student_auth");
        });
    }
}
