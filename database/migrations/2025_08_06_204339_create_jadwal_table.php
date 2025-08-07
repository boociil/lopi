<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('siakip2_jadwal', function (Blueprint $table) {
            //peserta ruang mana tanggal, tautan daftar hadrir, notulensi, tautan dokumentasi, tautan undangan
            $table->string('id', 36)->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('event_type', 100)->nullable(); // e.g. 'holiday', 'meeting'
            $table->string('public', 1);// 0: tidak, 1:ya
            $table->string('ruang', 100)->nullable();
            $table->string('url_daftar_hadir', 2048)->nullable();
            $table->string('url_notulensi', 2048)->nullable();
            $table->string('url_dokumentasi', 2048)->nullable();
            $table->string('url_undangan', 2048); // Example with a length of 2048 characters)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';
        });
        Schema::create('siakip2_jadwal_peserta', function (Blueprint $table) {
            //peserta ruang mana tanggal, tautan daftar hadrir, notulensi, tautan dokumentasi, tautan undangan
            $table->string('id', 36)->primary();
            $table->string('jadwal_id', 36);
            $table->string('tambah_sendiri', 1);// 0: tidak, 1:ya
            $table->string('tipe', 30);// 0: global, 1:satuan_kerja_tahun, 2:tim, 3
            $table->string('peserta_type')->nullable();
            $table->string('peserta_id')->nullable();

            $table->foreign('jadwal_id', 'jadwal_fk_jadwal_id')->references('id')->on('siakip2_jadwal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siakip2_jadwal_peserta');
        Schema::dropIfExists('siakip2_jadwal');
    }
};
