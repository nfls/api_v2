<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMobileNotice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection('mysql_user')->create('app_startup_notice', function (Blueprint $table) {
            $table->increments("id")->comment("数据ID")->unique();
            $table->text("text")->nullable()->comment("文本");
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
        Schema::connection('mysql_user')->dropIfExists('app_startup_notice');
    }
}
