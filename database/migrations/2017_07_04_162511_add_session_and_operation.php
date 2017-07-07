<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSessionAndOperation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_user')->table('user_session', function (Blueprint $table) {
            $table->string("session");
            $table->string("operation");
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
        Schema::connection('mysql_alumni')->table('user_session', function (Blueprint $table) {
            $table->dropColumn("session");
            $table->dropColumn("operation");
        });
        //
    }
}
