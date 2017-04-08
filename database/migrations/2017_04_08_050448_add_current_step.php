<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCurrentStep extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_alumni')->table('user_auth', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_step')->comment("当前步骤")->default(1);
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
            $table->dropColumn('current_step');
        });
        //
    }
}
