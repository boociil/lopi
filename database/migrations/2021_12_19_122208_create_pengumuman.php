<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePengumuman extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('siakip2_pengumuman', function (Blueprint $table) {
            $table->string('id',36)->primary();
            $table->string('tipe', 36); // satuan_kerja_tahun, tim, pegawai
            $table->string('pemilik', 100)->nullable();
            $table->string('parent_id', 36)->nullable();
            $table->string('content', 3072);
            $table->smallInteger('tahun')->unsigned();

            $table->timestamps();
            $table->softDeletes();
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
        Schema::dropIfExists('siakip2_pengumuman');
    }
}
