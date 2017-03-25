<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDirectoryInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_alumni')->table('user_auth', function (Blueprint $table) {
            $table->json('directory_info')->comment("校友录索引，仅包含初高中6位数班级号")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_alumni')->table('user_auth', function (Blueprint $table) {
            $table->dropColumn('directory_info');
        });
    }
}
