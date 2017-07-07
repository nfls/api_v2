<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIapDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_user')->create('user_purchase', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unique();
            $table->mediumText("receipt");
            $table->json("authorize_data");
            $table->text("environment");
            $table->integer("price");
            $table->integer("user_id")->default(-1);
            $table->index('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_user')->dropIfExists('user_session');
    }
}
