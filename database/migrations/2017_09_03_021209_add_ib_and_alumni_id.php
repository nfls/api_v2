<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIbAndAlumniId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection('mysql_user')->table('user_list', function (Blueprint $table) {
            $table->integer("ib_account")->comment("IB ID")->default(-1);
            $table->integer("alumni_account")->comment("校友会ID")->default(-1);
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
        Schema::connection('mysql_user')->table('user_list', function (Blueprint $table) {
            $table->dropColumn("alumni_account");
            $table->dropColumn("ib_account");
        });
    }
}
