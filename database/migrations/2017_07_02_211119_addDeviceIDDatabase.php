<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeviceIDDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_user')->create('user_device', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unique();
            $table->string("device_id");
            $table->string("device_model");
            $table->mediumInteger("user_id");
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
        Schema::connection('mysql_user')->dropIfExists('user_device');
    }
}
