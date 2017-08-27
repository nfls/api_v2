<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUniversityDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection('mysql_alumni')->table('universities', function (Blueprint $table) {
            $table->text("shortName")->comment("简写")->nullable();
            $table->text("chineseName")->comment("中文名")->nullable();
            $table->text("chineseShortName")->comment("中文名简写")->nullable();
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
        Schema::connection('mysql_alumni')->table('universities', function (Blueprint $table) {
            $table->dropColumn("shortName");
            $table->dropColumn("chineseName");
            $table->dropColumn("chineseShortName");
        });
    }
}
