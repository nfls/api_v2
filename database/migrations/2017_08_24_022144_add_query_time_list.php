<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQueryTimeList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_alumni')->table('user_auth', function (Blueprint $table) {
            $table->json("query_time")->comment("查询请求时间");
        });
        Schema::connection('mysql_user')->table('user_list', function (Blueprint $table) {
            $table->text("2fa")->comment("二次认证密钥");
            $table->bigInteger("permissions")->comment("权限");
        });
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_alumni')->table('user_auth', function (Blueprint $table) {
            $table->dropColumn("query_time");
        });
        Schema::connection('mysql_user')->table('user_list', function (Blueprint $table) {
            $table->dropColumn("2fa");
            $table->dropColumn("permissions");
        });
        //
    }
}