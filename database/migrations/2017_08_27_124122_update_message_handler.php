<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateMessageHandler extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection('mysql_alumni')->table('clubs', function (Blueprint $table) {
            $table->increments("id")->comment("ID");
            $table->text("name")->comment("社团名");
            $table->integer("added_by")->comment("创建者");
            $table->text("comment")->comment("备注")->nullable();
            $table->boolean("isEnabled")->comment("是否启用")->default(false);
            $table->json("members")->comment("成员")->nullable();
        });
        Schema::connection('mysql_alumni')->table('universities', function (Blueprint $table) {
            $table->increments("id")->comment("ID");
            $table->text("name")->comment("大学名称");
            $table->text("country")->comment("国家");
            $table->integer("added_by")->comment("创建者");
            $table->text("comment")->comment("备注")->nullable();
            $table->boolean("isEnabled")->comment("是否启用")->default(false);
        });
        Schema::connection('mysql_user')->table("user_logs", function(Blueprint $table){
            $table->bigIncrements("id")->comment("ID");
            $table->unsignedTinyInteger("level")->comment("等级");
            $table->timestamp("time")->comment("时间");
            $table->text("ip")->comment("触发IP");
            $table->integer("userid")->comment("触发用户");
            $table->text("info")->comment("信息");
        });
        Schema::connection('mysql_user')->table("user_messages", function(Blueprint $table){
            $table->bigIncrements("id")->comment("ID");
            $table->integer("startuser")->comment("发起人");
            $table->integer("touser")->comment("接收人");
            $table->json("message")->comment("具体信息");
            $table->boolean("isNew")->comment("是否有新消息")->default(true);
            $table->boolean("isVisiable")->comment("是否可见")->default(true);
        });
        Schema::connection('mysql_user')->dropIfExists('app_startup_notice');
        Schema::connection('mysql_user')->table('user_list', function (Blueprint $table) {
            $table->dropColumn("ss_account");
            $table->dropColumn("share_account");
            $table->smallInteger("rename_cards")->default(0);
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
        Schema::connection('mysql_alumni')->dropIfExists('clubs');
        Schema::connection('mysql_alumni')->dropIfExists('universities');
        Schema::connection('mysql_alumni')->dropIfExists('user_logs');
        Schema::connection('mysql_alumni')->dropIfExists('user_messages');
        Schema::connection('mysql_user')->create('app_startup_notice', function (Blueprint $table) {
            $table->increments("id")->comment("数据ID")->unique();
            $table->text("title")->nullable()->comment("标题");
            $table->text("text")->nullable()->comment("文本");
        });
        Schema::connection('mysql_user')->table('user_list', function (Blueprint $table) {
            $table->integer("ss_account")->default(-1);
            $table->integer("share_account")->default(-1);
            $table->dropColumn("rename_cards");
        });
    }
}
