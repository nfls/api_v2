<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RecordEditAndSubmitTimeAndStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection('mysql_alumni')->table('user_auth', function (Blueprint $table) {
            $table->dateTime('submit_time')->comment("提交时间")->nullable();
            $table->dateTime('edit_time')->comment("最近一次修改时间")->nullable();
            $table->dateTime('status_change_time')->comment("审核时间")->nullable();
            $table->integer('operator')->comment("审核员")->nullable();
            $table->boolean('status')->comment("状态")->default(false);
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
        Schema::connection('mysql_alumni')->table('user_auth', function (Blueprint $table) {
            $table->dropColumn('submit_time');
            $table->dropColumn('edit_time');
            $table->dropColumn('status_change_time');
            $table->dropColumn('operator');
            $table->dropColumn('status');
        });
    }
}
