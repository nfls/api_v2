<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGalleryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::connection('mysql_alumni')->create('album', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->unique();
                $table->text('name')->nullable()->comment("拍摄时间");
                $table->date('date')->nullable()->comment("拍摄日期");
                $table->date('author')->nullable()->comment("拍摄作者");
                $table->date('license')->nullable()->comment("授权协议");
                $table->date('intro')->nullable()->comment("照片介绍");
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
        Schema::connection('mysql_alumni')->dropIfExists('album');
    }
}
