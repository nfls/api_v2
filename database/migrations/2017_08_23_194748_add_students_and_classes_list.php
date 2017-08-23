<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStudentsAndClassesList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection('mysql_alumni')->create('students', function (Blueprint $table) {
            $table->increments("id")->index()->comment("ID")->unique();
            $table->text("name")->comment("姓名");
            $table->integer("class_id")->comment("班级ID");
            $table->boolean("used")->default(false)->comment("是否使用");
            $table->text("comment")->nullable()->comment("备注");
        });
        Schema::connection('mysql_alumni')->create('classes', function (Blueprint $table) {
            $table->increments("id")->index()->unique()->comment("ID")->unique();
            $table->text("type")->comment("班级类型");
            $table->integer("year")->comment("届");
            $table->integer("class")->comment("班");
            $table->text("comment")->nullable()->comment("备注");
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
        Schema::connection('mysql_alumni')->dropIfExists('students');
        Schema::connection('mysql_alumni')->dropIfExists('classes');
    }
}
