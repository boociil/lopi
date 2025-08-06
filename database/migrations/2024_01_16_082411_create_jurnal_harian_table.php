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
        //proyek  tapi bisa lebih keci;
        Schema::create('siakip2_jurnal_harian_proyek', function (Blueprint $table) {
            $table->string('id', 36)->primary();

            $table->smallInteger('tahun')->unsigned();
            $table->string('status', 36); //belum, sementara, selesai
            $table->date('tanggal_awal')->nullable();
            $table->date('tanggal_akhir')->nullable();

            $table->string('satuan_kerja_tahun_id', 36);
            $table->string('fungsi_id', 36)->nullable();
            $table->string('tim_id', 36)->nullable();
            $table->string('pembuat_username', 100)->nullable();

            $table->string('nama', 1024);
            
            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';
            
            $table->foreign('satuan_kerja_tahun_id', 'jh_proyek_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
            $table->foreign('fungsi_id', 'jh_proyek_fk_fungsi_id')->references('id')->on('siakip2_fungsi');
            $table->foreign('tim_id', 'jh_proyek_fk_tim_id')->references('id')->on('siakip2_tim');
            $table->foreign('pembuat_username', 'jh_proyek_fk_pembuat_username')->references('username')->on('siakip2_users');
        });

        Schema::create('siakip2_jurnal_harian_item', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->smallInteger('tahun')->unsigned();
            $table->string('jurnal_harian_proyek_id', 36)->nullable();

            $table->string('type', 1)->default('0'); // 0 : personal 1: Tim 2: fungsi 3: Satuan Kerja Tahun  4: Tambahan
            $table->string('satuan_kerja_tahun_id', 36);
            $table->string('fungsi_id', 36)->nullable();
            $table->string('tim_id', 36)->nullable();
            $table->string('pembuat_username', 100);

            $table->string('pelaksana_username', 100);
            $table->string('pengaturan', 255); // kondisi_capaian : normal, khusus

            $table->string('parent_id', 36)->nullable(); // hanya bole copy jika null
            $table->string('status_pekerjaan', 1)->default(0); //0: belum 1.on progress 2: selesai
            $table->string('status_nilai', 1)->default(0); // status penilaian => 0: belum, 1: pengajuan, 2:tolak, 3:selesai

            $table->string('nama', 1024);
            $table->string('satuan', 255)->nullable();
            $table->string('target', 128)->nullable()->default(0);
            $table->string('realisasi', 128)->nullable()->default(0);

            $table->date('tanggal_deadline')->nullable()->default(null);
            $table->date('tanggal_realisasi')->nullable()->default(null);
            $table->time('jam_realisasi_awal')->nullable()->default(null);
            $table->time('jam_realisasi_akhir')->nullable()->default(null);

            $table->string('nilai_kuantitas', 128)->nullable();
            $table->string('nilai_kualitas', 128)->nullable();
            $table->string('nilai_waktu', 128)->nullable(); // jika personal maka tidak dinilai
            $table->string('nilai', 128)->nullable();

            $table->string('bukti_url', 512)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('jurnal_harian_proyek_id', 'jh_item_fk_jh_proyek_id')->references('id')->on('siakip2_jurnal_harian_proyek');

            $table->foreign('satuan_kerja_tahun_id', 'jh_item_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
            $table->foreign('fungsi_id', 'jh_item_fk_fungsi_id')->references('id')->on('siakip2_fungsi');
            $table->foreign('tim_id', 'jh_item_fk_tim_id')->references('id')->on('siakip2_tim');
            $table->foreign('pembuat_username', 'jh_item_fk_pembuat_username')->references('username')->on('siakip2_users');

            $table->foreign('pelaksana_username', 'jh_item_fk_pelaksana_username')->references('username')->on('siakip2_users');
        });

        Schema::create('siakip2_jurnal_harian_berkas', function (Blueprint $table) {
            $table->string('id',36)->primary();

            $table->smallInteger('tahun')->unsigned();
            $table->string('jurnal_harian_item_id', 36);
            $table->string('username_pengirim', 100)->nullable();
            $table->string('username_approval', 100)->nullable();
            $table->tinyInteger('status_approval')->unsigned()->default(0);
            //0 tidak ada 1 reject 2 approve 3 time out

            $table->string('type_berkas', 30); //text, url, bukti, pengajuan, realisasi

            $table->string('text', 1024)->nullable();
            $table->string('nilai_baru', 512)->nullable();
            $table->string('file_id', 36)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('username_pengirim', 'jh_berkas_fk_username_pengirim')->references('username')->on('siakip2_users');
            $table->foreign('username_approval', 'jh_berkas_fk_username_approval')->references('username')->on('siakip2_users');
            $table->foreign('file_id', 'jh_berkas_fk_file_id')->references('id')->on('siakip2_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siakip2_jurnal_harian_berkas');
        Schema::dropIfExists('siakip2_jurnal_harian_item');
        Schema::dropIfExists('siakip2_jurnal_harian_proyek');
    }
};
