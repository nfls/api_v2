<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('user_auth')) {
            Schema::connection('mysql_alumni')->create('user_auth', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->unique();
                $table->json('auth_info')->nullable()->comment("验证信息，包含是否通过，提交/通过时间，操作员");
                $table->json('primary_school')->nullable()->comment("小学信息");
                $table->json('junior_school')->nullable()->comment("初中信息");
                $table->json('senior_school')->nullable()->comment("高中信息");
                $table->json('college')->nullable()->comment("大学信息");
                $table->json('working_info')->nullable()->comment("工作信息");
                $table->json('personal_info')->nullable()->comment("个人信息");
                $table->index('id');
            });
        }
        
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_alumni')->dropIfExists('user_auth');
    }
}
