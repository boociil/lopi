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

        Schema::create('siakip2_jadwal_peserta', function (Blueprint $table) {
            //peserta ruang mana tanggal, tautan daftar hadrir, notulensi, tautan dokumentasi, tautan undangan
            $table->string('id', 36)->primary();
            $table->string('jadwal_id', 36);
            $table->string('username', 100);

            $table->engine = 'InnoDB';

            $table->foreign('jadwal_id', 'peserta_fk_jadwal_id')->references('id')->on('siakip2_jadwal');
            $table->foreign('username', 'jadwal_peserta_fk_username')->references('username')->on('siakip2_users');
        });

        Schema::create('siakip2_petugas_jadwal', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petugas_jadwal');
    }
};
