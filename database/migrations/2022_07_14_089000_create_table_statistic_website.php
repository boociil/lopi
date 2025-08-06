<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableStatisticWebsite extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('siakip2_statistik', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('method', 12)->nullable();
            $table->string('username', 100)->nullable();
            $table->string('ip_address',50);
            $table->string('path', 512);
            $table->timestamps();
            $table->engine = 'InnoDB';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('siakip2_statistik');
    }
}
