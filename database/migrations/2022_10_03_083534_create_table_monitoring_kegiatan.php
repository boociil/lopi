<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableMonitoringKegiatan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('siakip2_monitoring_master', function (Blueprint $table) {
            $table->string('id', 36)->primary();

            $table->tinyInteger('bulan')->unsigned()->default(0);
            $table->smallInteger('tahun')->unsigned();

            $table->date('target_tanggal_awal')->nullable()->default(null);
            $table->date('target_tanggal_akhir')->nullable()->default(null);

            $table->date('realisasi_tanggal_awal')->nullable()->default(null);
            $table->date('realisasi_tanggal_akhir')->nullable()->default(null);

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';
        });

        Schema::create('siakip2_monitoring_kegiatan', function (Blueprint $table) {
            $table->string('id', 36)->primary();

            $table->string('monitoring_master_id', 36);
            $table->tinyInteger('bulan')->unsigned()->default(0);
            $table->smallInteger('tahun')->unsigned();

            $table->string('pengaturan', 255); // kondisi_capaian : normal, khusus
            $table->string('pemberi_satuan_kerja_tahun_id', 36)->nullable();
            $table->string('pemberi_tim_id', 36)->nullable();

            $table->string('nama', 1024);
            $table->string('satuan', 255)->nullable();

            $table->date('tanggal_awal')->nullable()->default(null);
            $table->date('tanggal_akhir')->nullable()->default(null);

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('monitoring_master_id', 'mon_keg_fk_monitoring_master_id')->references('id')->on('siakip2_monitoring_master');
            $table->foreign('pemberi_tim_id', 'mon_keg_fk_pemberi_tim_id')->references('id')->on('siakip2_tim');
            $table->foreign('pemberi_satuan_kerja_tahun_id', 'mon_keg_fk_pemberi_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });

        Schema::create('siakip2_monitoring_kegiatan_pelaksana', function (Blueprint $table) {
            $table->string('id', 36)->primary();

            $table->string('monitoring_master_id', 36);
            $table->tinyInteger('bulan')->unsigned()->default(0);
            $table->smallInteger('tahun')->unsigned();

            $table->string('monitoring_kegiatan_id', 36);

            $table->string('pelaksana_satuan_kerja_tahun_id', 36)->nullable();
            $table->string('pelaksana_tim_id', 36)->nullable();

            $table->string('status', 36); //belum, pengajuan, tolak, selesai
            $table->date('tanggal_awal')->nullable()->default(null);
            $table->date('tanggal_akhir')->nullable()->default(null);
            $table->date('tanggal_realisasi')->nullable()->default(null);

            $table->string('target', 128)->nullable();
            $table->string('realisasi', 128)->nullable();

            $table->string('nilai_kuantitas', 128)->nullable();
            $table->string('nilai_kualitas', 128)->nullable();
            $table->string('nilai_waktu', 128)->nullable();
            $table->string('nilai', 128)->nullable();

            $table->string('bukti_url', 512)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('monitoring_master_id', 'mon_keg_pel_fk_monitoring_master_id')->references('id')->on('siakip2_monitoring_master');
            $table->foreign('monitoring_kegiatan_id', 'mon_keg_pel_fk_monitoring_kegiatan_id')->references('id')->on('siakip2_monitoring_kegiatan');

            $table->foreign('pelaksana_tim_id', 'mon_keg_pel_berkas_fk_tim_id')->references('id')->on('siakip2_tim');
            $table->foreign('pelaksana_satuan_kerja_tahun_id', 'mon_keg_pel_berkas_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });

        Schema::create('siakip2_monitoring_kegiatan_pelaksana_berkas', function (Blueprint $table) {
            $table->string('id',36)->primary();

            $table->string('monitoring_master_id', 36);
            $table->tinyInteger('bulan')->unsigned()->default(0);
            $table->smallInteger('tahun')->unsigned();

            $table->string('monitoring_kegiatan_id', 36);
            $table->string('monitoring_kegiatan_pelaksana_id', 36);

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

            $table->foreign('username_pengirim', 'mon_keg_pel_berkas_fk_username_pengirim')->references('username')->on('siakip2_users');
            $table->foreign('username_approval', 'mon_keg_pel_berkas_fk_username_approval')->references('username')->on('siakip2_users');
            $table->foreign('file_id', 'mon_keg_pel_berkas_fk_file_id')->references('id')->on('siakip2_url');

            $table->foreign('monitoring_master_id', 'mon_keg_pel_berkas_fk_monitoring_master_id')->references('id')->on('siakip2_monitoring_master');
            $table->foreign('monitoring_kegiatan_id', 'mon_keg_pel_berkas_fk_monitoring_kegiatan_id')->references('id')->on('siakip2_monitoring_kegiatan');
            $table->foreign('monitoring_kegiatan_pelaksana_id', 'mon_keg_pel_berkas_fk_monitoring_kegiatan_pelaksana_id')->references('id')->on('siakip2_monitoring_kegiatan_pelaksana');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('siakip2_monitoring_kegiatan_pelaksana_berkas');
        Schema::dropIfExists('siakip2_monitoring_kegiatan_pelaksana');
        Schema::dropIfExists('siakip2_monitoring_kegiatan');
        Schema::dropIfExists('siakip2_monitoring_master');
    }
}
