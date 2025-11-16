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

            $table->string('pembuat_username', 100)->nullable();
            $table->string('pemilik_tipe_str', 30);// 0: global, 1:satuan_kerja_tahun, 2:tim, 3
            $table->string('pemilik_type')->nullable();
            $table->string('pemilik_id',100)->nullable();

            $table->string('topic');
            $table->text('description')->nullable();

            $table->enum('tipe', ['sekali', 'berulang'])->default('sekali');
            $table->date('tanggal')->nullable();
            $table->date('berulang_tanggal_awal')->nullable();
            $table->date('berulang_tanggal_akhir')->nullable();
            $table->time('waktu_awal');
            $table->time('waktu_akhir');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');

            $table->string('url_daftar_hadir', 2048)->nullable();
            $table->string('url_notulensi', 2048)->nullable();
            $table->string('url_dokumentasi', 2048)->nullable();
            $table->string('url_undangan', 2048)->nullable();

            $table->string('zoom_id', 36)->nullable();
            $table->string('ruang_id', 36)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';
        });

        Schema::create('siakip2_pinjam_mobil', function (Blueprint $table) {
            $table->string('id', 36)->primary();

            $table->string('kegiatan');

            $table->enum('tipe', ['sekali', 'berulang'])->default('sekali');
            $table->date('tanggal')->nullable();
            $table->date('berulang_tanggal_awal')->nullable();
            $table->date('berulang_tanggal_akhir')->nullable();
            $table->time('waktu_awal');
            $table->time('waktu_akhir');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');

            $table->string('pembuat_username', 100);
            $table->string('confirm_username', 100)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('pembuat_username', 'mobil_fk_pembuat_username')->references('username')->on('siakip2_users');
            $table->foreign('confirm_username', 'mobil_fk_confirm_username')->references('username')->on('siakip2_users');
        });


        Schema::create('siakip2_pinjam_ruang', function (Blueprint $table) {
            $table->string('id', 36)->primary();

            $table->string('kegiatan');
            $table->string('ruang', 100);

            $table->enum('tipe', ['sekali', 'berulang'])->default('sekali');
            $table->date('tanggal')->nullable();
            $table->date('berulang_tanggal_awal')->nullable();
            $table->date('berulang_tanggal_akhir')->nullable();

            $table->time('waktu_awal');
            $table->time('waktu_akhir');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');

            $table->string('pembuat_username', 100);
            $table->string('confirm_username', 100)->nullable();

            $table->string('jadwal_id', 36)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('pembuat_username', 'ruang_fk_pembuat_username')->references('username')->on('siakip2_users');
            $table->foreign('confirm_username', 'ruang_fk_confirm_username')->references('username')->on('siakip2_users');
            $table->foreign('jadwal_id', 'ruang_fk_jadwal_id')->references('id')->on('siakip2_jadwal');
        });

         Schema::create('siakip2_pinjam_zoom', function (Blueprint $table) {
            //peserta ruang mana tanggal, tautan daftar hadrir, notulensi, tautan dokumentasi, tautan undangan
            $table->string('id', 36)->primary();
            $table->string('topic', 2000);
            $table->string('password', 10);

            $table->enum('tipe', ['sekali', 'berulang'])->default('sekali');
            $table->date('tanggal')->nullable();
            $table->date('berulang_tanggal_awal')->nullable();
            $table->date('berulang_tanggal_akhir')->nullable();

            $table->time('waktu_awal');
            $table->time('waktu_akhir');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');

            $table->string('pembuat_username', 100);
            $table->string('confirm_username', 100)->nullable();

            $table->string('zoom_akun', 100)->nullable();
            $table->unsignedBigInteger('zoom_meeting_id')->nullable();
            $table->string('zoom_start_url', 2000)->nullable();
            $table->string('zoom_join_url', 2000)->nullable();

            $table->string('jadwal_id', 36)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('pembuat_username', 'zoom_fk_pembuat_username')->references('username')->on('siakip2_users');
            $table->foreign('confirm_username', 'zoom_fk_confirm_username')->references('username')->on('siakip2_users');
            $table->foreign('jadwal_id', 'zoom_fk_jadwal_id')->references('id')->on('siakip2_jadwal');
        });

        Schema::table('siakip2_jadwal', function (Blueprint $table) {
            $table->foreign('pembuat_username', 'jadwal_fk_pembuat_username')->references('username')->on('siakip2_users');
            $table->foreign('zoom_id', 'jadwal_fk_zoom_id')->references('id')->on('siakip2_pinjam_zoom');
            $table->foreign('ruang_id', 'jadwal_fk_ruang_id')->references('id')->on('siakip2_pinjam_ruang');
        });


        Schema::create('siakip2_jadwal_peserta', function (Blueprint $table) {
            //peserta ruang mana tanggal, tautan daftar hadrir, notulensi, tautan dokumentasi, tautan undangan
            $table->string('id', 36)->primary();
            $table->string('jadwal_id', 36);
            $table->string('tipe', 30);// 0: global, 1:satuan_kerja_tahun, 2:tim, 3
            $table->string('peserta_type')->nullable();
            $table->string('peserta_id')->nullable();

            $table->engine = 'InnoDB';

            $table->foreign('jadwal_id', 'peserta_fk_jadwal_id')->references('id')->on('siakip2_jadwal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siakip2_jadwal_peserta');
        Schema::dropIfExists('siakip2_jadwal');
        Schema::dropIfExists('siakip2_zoom');
        Schema::dropIfExists('siakip2_ruang');
    }
};
