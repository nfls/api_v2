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
            Schema::connection('mysql-alumni')->create('user_auth', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->unique();
                $table->json('auth_info')->nullable();
                $table->json('primary_school')->nullable();
                $table->json('junior_school')->nullable();
                $table->json('senior_school')->nullable();
                $table->json('college')->nullable();
                $table->json('working_info')->nullable();
                $table->json('personal_info')->nullable();
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
        Schema::connection('mysql-alumni')->dropIfExists('user_auth');
    }
}
