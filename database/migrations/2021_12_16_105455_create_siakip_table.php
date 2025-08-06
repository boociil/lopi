<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiakipTable extends Migration
{
    // /**
    //  * Run the migrations.
    //  *
    //  * @return void
    //  */
    // public function up()
    // {
    //     Schema::create('siakip2_tujuan', function (Blueprint $table) {
    //         $table->string('id',36)->primary();
    //         $table->smallInteger('urutan')->unsigned()->default(0);
    //         $table->string('urutan_tulis', 12)->nullable();
    //         $table->string('nama', 1024);
    //         $table->smallInteger('tahun')->unsigned();

    //         $table->timestamps();
    //         $table->softDeletes();
    //         $table->engine = 'InnoDB';
    //     });

    //     Schema::create('siakip2_sasaran', function (Blueprint $table) {
    //         $table->string('id',36)->primary();
    //         $table->string('tujuan_id', 36)->nullable();
    //         $table->smallInteger('urutan')->unsigned()->default(0);
    //         $table->string('urutan_tulis', 12)->nullable();
    //         $table->string('nama', 1024);
    //         $table->smallInteger('tahun')->unsigned();

    //         $table->timestamps();
    //         $table->softDeletes();
    //         $table->engine = 'InnoDB';

    //         $table->foreign('tujuan_id', '2_sasaran_fk_tujuan_id')->references('id')->on('siakip2_tujuan');
    //     });

    //     Schema::create('siakip2_iku', function (Blueprint $table) {
    //         $table->string('id',36)->primary();

    //         $table->string('satuan_kerja_tahun_id', 36);
    //         $table->smallInteger('tahun')->unsigned();

    //         $table->string('tujuan_id', 36)->nullable();
    //         $table->string('sasaran_id', 36)->nullable();

    //         $table->smallInteger('urutan')->unsigned()->default(0);
    //         $table->string('urutan_tulis', 12)->nullable();

    //         $table->string('tipe_iku', 36)->default("UTAMA");
    //         $table->tinyInteger('is_indikator')->default(0);// 0: tidak 1:ya
    //         $table->string('parent_id', 36)->nullable();
    //         $table->string('text', 1024);

    //         $table->string('metode_cascading', 50);//direct_cascading, non-direct_cascading, direktif_pimpinan

    //         $table->string('satuan', 256);// 0: belum  1: jumlah 2: persentase jumlah

    //         $table->tinyInteger('active')->unsigned()->default(1);//0:tidak 1:ya

    //         $table->string('pengaturan', 1024);// isi_target, isi_realisasi, formula, keterangan_target, kondisi_capaian, tipe_taget,tipe_number, panjang

    //         //    $table->string('isi_target', 50);// isi_sendiri, cascading, formula, tidak_ada
    //         //     $table->string('isi_realisasi', 50);// isi_sendiri, cascading, formula, tidak_ada
    //         //     $table->string('nilai_formula', 512)->nullable();

    //         //     $table->string('keterangan_target', 30)->default("non_max");// max, non_max
    //         //     $table->string('kondisi_capaian', 30)->default("normal");// normal, khusus
    //         //     $table->string('tipe_taget_number',128); //{tipe_taget:single, range, tipe_number:number,double, panjang:2}

    //         $table->string('tipe_evaluasi',25); //bulan, triwulan, semester, tahun
    //         //bulan
    //         $table->string('target_bul_01',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_01',128)->nullable()->default(null);

    //         $table->string('target_bul_02',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_02',128)->nullable()->default(null);

    //         $table->string('target_bul_03',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_03',128)->nullable()->default(null);

    //         $table->string('target_bul_04',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_04',128)->nullable()->default(null);

    //         $table->string('target_bul_05',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_05',128)->nullable()->default(null);

    //         $table->string('target_bul_06',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_06',128)->nullable()->default(null);

    //         $table->string('target_bul_07',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_07',128)->nullable()->default(null);

    //         $table->string('target_bul_08',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_08',128)->nullable()->default(null);

    //         $table->string('target_bul_09',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_09',128)->nullable()->default(null);

    //         $table->string('target_bul_10',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_10',128)->nullable()->default(null);

    //         $table->string('target_bul_11',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_11',128)->nullable()->default(null);

    //         $table->string('target_bul_12',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_12',128)->nullable()->default(null);

    //         //triwulan
    //         $table->string('target_tri_01',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_01',128)->nullable()->default(null);

    //         $table->string('target_tri_02',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_02',128)->nullable()->default(null);

    //         $table->string('target_tri_03',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_03',128)->nullable()->default(null);

    //         $table->string('target_tri_04',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_04',128)->nullable()->default(null);

    //         //semester
    //         $table->string('target_sem_01',128)->nullable()->default(null);
    //         $table->string('realisasi_sem_01',128)->nullable()->default(null);

    //         $table->string('target_sem_02',128)->nullable()->default(null);
    //         $table->string('realisasi_sem_02',128)->nullable()->default(null);

    //         //tahun
    //         $table->string('target_tahun',128)->nullable()->default(null);
    //         $table->string('realisasi_tahun',128)->nullable()->default(null);

    //         $table->timestamps();
    //         $table->softDeletes();
    //         $table->engine = 'InnoDB';

    //         $table->foreign('satuan_kerja_tahun_id', '2_iku_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

    //         $table->foreign('tujuan_id', '2_iku_fk_tujuan_id')->references('id')->on('siakip2_tujuan');
    //         $table->foreign('sasaran_id', '2_iku_fk_sasaran_id')->references('id')->on('siakip2_sasaran');
    //     });

    //     Schema::create('siakip2_rencana_kerja_tim', function (Blueprint $table) {
    //         $table->string('id',36)->primary();

    //         $table->string('satuan_kerja_tahun_id', 36);
    //         $table->string('tim_id', 36)->nullable();
    //         $table->smallInteger('tahun')->unsigned();

    //         $table->string('tujuan_id', 36)->nullable();
    //         $table->string('sasaran_id', 36)->nullable();
    //         $table->string('iku_id', 36)->nullable();

    //         $table->smallInteger('urutan')->unsigned()->default(0);
    //         $table->string('urutan_tulis', 12)->nullable();

    //         $table->string('perspektif_tipe', 50)->nullable();// 0: biasa, 1: Perspektif penerima layanan, 2: Perspektif proses bisnis, 3:  Perspektif penguatan internal, 4:  Perspektif anggaran

    //         $table->string('kriteria', 30)->nullable();// 0: utama 1: tambahan
    //         $table->string('text', 1024)->nullable();

    //         $table->tinyInteger('active')->unsigned()->default(0);//0:tidak 1:ya

    //         $table->string('tipe_evaluasi',25); //bulan, triwulan, semester, tahun
    //         //bulan
    //         $table->string('capaian',3072)->nullable()->default(null);

    //         $table->timestamps();
    //         $table->softDeletes();
    //         $table->engine = 'InnoDB';

    //         $table->foreign('satuan_kerja_tahun_id', '2_rencana_kerja_tim_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

    //         $table->foreign('tujuan_id', '2_rencana_kerja_tim_fk_tujuan_id')->references('id')->on('siakip2_tujuan');
    //         $table->foreign('sasaran_id', '2_rencana_kerja_tim_fk_sasaran_id')->references('id')->on('siakip2_sasaran');
    //         $table->foreign('iku_id', '2_rencana_kerja_tim_fk_iku_id')->references('id')->on('siakip2_iku');

    //         $table->foreign('tim_id', '2_rencana_kerja_tim_fk_tim_id')->references('id')->on('siakip2_tim');
    //     });

    //     Schema::create('siakip2_iki_tim', function (Blueprint $table) {
    //         $table->string('id',36)->primary();

    //         $table->string('satuan_kerja_tahun_id', 36);
    //         $table->string('tim_id', 36)->nullable();
    //         $table->smallInteger('tahun')->unsigned();

    //         $table->string('tujuan_id', 36)->nullable();
    //         $table->string('sasaran_id', 36)->nullable();
    //         $table->string('iku_id', 36)->nullable();

    //         $table->string('rencana_kerja_tim_id', 36)->nullable();

    //         $table->smallInteger('urutan')->unsigned()->default(0);
    //         $table->string('urutan_tulis', 12)->nullable();

    //         $table->string('kriteria', 36)->default("utama");
    //         $table->tinyInteger('is_indikator')->default(0);// 0: tidak 1:ya
    //         $table->string('parent_id', 36)->nullable();
    //         $table->string('text', 1024);

    //         $table->string('metode_cascading', 50);//direct_cascading, non-direct_cascading, direktif_pimpinan

    //         $table->string('aspek', 30)->nullable();//kuantitas, kualitas, waktu, biaya

    //         $table->string('satuan', 256);// 0: belum  1: jumlah 2: persentase jumlah

    //         $table->string('pengaturan', 1024);// isi_target, isi_realisasi, nilai_formula, keterangan_target, kondisi_capaian, tipe_taget,tipe_numbe, panjang

    //         $table->tinyInteger('active')->unsigned()->default(1);//0:tidak 1:ya
    //         $table->tinyInteger('effect_cascading')->unsigned()->default(0);//0:tidak 1:ya
    //         $table->string('effect_cascading_id',36)->nullable();//0:tidak 1:ya

    //         $table->string('tipe_evaluasi',25); //bulan, triwulan, semester, tahun
    //         //bulan
    //         $table->string('target_bul_01',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_01',128)->nullable()->default(null);

    //         $table->string('target_bul_02',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_02',128)->nullable()->default(null);

    //         $table->string('target_bul_03',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_03',128)->nullable()->default(null);

    //         $table->string('target_bul_04',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_04',128)->nullable()->default(null);

    //         $table->string('target_bul_05',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_05',128)->nullable()->default(null);

    //         $table->string('target_bul_06',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_06',128)->nullable()->default(null);

    //         $table->string('target_bul_07',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_07',128)->nullable()->default(null);

    //         $table->string('target_bul_08',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_08',128)->nullable()->default(null);

    //         $table->string('target_bul_09',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_09',128)->nullable()->default(null);

    //         $table->string('target_bul_10',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_10',128)->nullable()->default(null);

    //         $table->string('target_bul_11',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_11',128)->nullable()->default(null);

    //         $table->string('target_bul_12',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_12',128)->nullable()->default(null);

    //         //triwulan
    //         $table->string('target_tri_01',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_01',128)->nullable()->default(null);

    //         $table->string('target_tri_02',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_02',128)->nullable()->default(null);

    //         $table->string('target_tri_03',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_03',128)->nullable()->default(null);

    //         $table->string('target_tri_04',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_04',128)->nullable()->default(null);

    //         //semester
    //         $table->string('target_sem_01',128)->nullable()->default(null);
    //         $table->string('realisasi_sem_01',128)->nullable()->default(null);

    //         $table->string('target_sem_02',128)->nullable()->default(null);
    //         $table->string('realisasi_sem_02',128)->nullable()->default(null);

    //         //tahun
    //         $table->string('target_tahun',128)->nullable()->default(null);
    //         $table->string('realisasi_tahun',128)->nullable()->default(null);

    //         $table->timestamps();
    //         $table->softDeletes();
    //         $table->engine = 'InnoDB';

    //         $table->foreign('satuan_kerja_tahun_id', '2_iki_tim_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

    //         $table->foreign('tujuan_id', '2_iki_tim_fk_tujuan_id')->references('id')->on('siakip2_tujuan');
    //         $table->foreign('sasaran_id', '2_iki_tim_fk_sasaran_id')->references('id')->on('siakip2_sasaran');
    //         $table->foreign('iku_id', '2_iki_tim_fk_iku_id')->references('id')->on('siakip2_iku');
    //         $table->foreign('rencana_kerja_tim_id', '2_iki_tim_fk_rencana_kerja_tim_id')->references('id')->on('siakip2_rencana_kerja_tim');

    //         $table->foreign('tim_id', '2_iki_tim_fk_tim_id')->references('id')->on('siakip2_tim');
    //     });

    //     Schema::create('siakip2_rencana_kerja_pegawai', function (Blueprint $table) {
    //         $table->string('id',36)->primary();

    //         $table->string('satuan_kerja_tahun_id', 36)->nullable();
    //         $table->string('tim_id', 36)->nullable();
    //         $table->string('username', 100);
    //         $table->smallInteger('tahun')->unsigned();

    //         $table->string('tujuan_id', 36)->nullable();
    //         $table->string('sasaran_id', 36)->nullable();
    //         $table->string('iku_id', 36)->nullable();

    //         $table->string('rencana_kerja_tim_id', 36)->nullable();
    //         $table->string('iki_tim_id', 36)->nullable();

    //         $table->tinyInteger('active')->unsigned()->default(0);//0:tidak 1:ya

    //         $table->string('hubungan', 25)->default('normal'); //normal, tim, satuan_kerja_tahun
    //         $table->string('hubungan_id', 36)->nullable(); //normal, iki_team, iku
    //         $table->tinyInteger('hubungan_active')->unsigned()->default(1);//0:tidak 1:ya

    //         $table->smallInteger('urutan')->unsigned()->default(0);
    //         $table->string('urutan_tulis', 12)->nullable();

    //         $table->string('kriteria', 30)->nullable();// 0: utama 1: tambahan
    //         $table->string('text', 1024)->nullable();

    //         $table->string('tipe_evaluasi',25); //bulan, triwulan, semester, tahun
    //         //bulan
    //         $table->string('capaian',3072)->nullable()->default(null);

    //         $table->timestamps();
    //         $table->softDeletes();
    //         $table->engine = 'InnoDB';

    //         $table->foreign('satuan_kerja_tahun_id', '2_rencana_kerja_pegawai_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

    //         $table->foreign('tujuan_id', '2_rencana_kerja_pegawai_fk_tujuan_id')->references('id')->on('siakip2_tujuan');
    //         $table->foreign('sasaran_id', '2_rencana_kerja_pegawai_fk_sasaran_id')->references('id')->on('siakip2_sasaran');
    //         $table->foreign('iku_id', '2_rencana_kerja_pegawai_fk_iku_id')->references('id')->on('siakip2_iku');
    //         $table->foreign('rencana_kerja_tim_id', '2_rencana_kerja_pegawai_fk_rencana_kerja_tim_id')->references('id')->on('siakip2_rencana_kerja_tim');
    //         $table->foreign('iki_tim_id', '2_rencana_kerja_pegawai_fk_iki_tim_id')->references('id')->on('siakip2_iki_tim');

    //         $table->foreign('username', '2_rencana_kerja_pegawai_fk_username')->references('username')->on('siakip2_users');
    //         $table->foreign('tim_id', '2_rencana_kerja_pegawai_fk_tim_id')->references('id')->on('siakip2_tim');
    //     });

    //     Schema::create('siakip2_iki_pegawai', function (Blueprint $table) {
    //         $table->string('id',36)->primary();

    //         $table->string('satuan_kerja_tahun_id', 36)->nullable();
    //         $table->string('tim_id', 36)->nullable();
    //         $table->string('username', 100);
    //         $table->smallInteger('tahun')->unsigned();

    //         $table->string('tujuan_id', 36)->nullable();
    //         $table->string('sasaran_id', 36)->nullable();
    //         $table->string('iku_id', 36)->nullable();

    //         $table->string('rencana_kerja_tim_id', 36)->nullable();
    //         $table->string('rencana_kerja_pegawai_id', 36)->nullable();
    //         $table->string('iki_tim_id', 36)->nullable();

    //         $table->string('hubungan', 25)->default('normal'); //normal, iki_team, iku
    //         $table->string('hubungan_id', 36)->nullable(); //normal, iki_team, iku
    //         $table->tinyInteger('hubungan_active')->unsigned()->default(1);//0:tidak 1:ya

    //         $table->smallInteger('urutan')->unsigned()->default(0);
    //         $table->string('urutan_tulis', 12)->nullable();

    //         $table->string('kriteria', 36)->default("UTAMA");
    //         $table->tinyInteger('is_indikator')->default(0);// 0: tidak 1:ya
    //         $table->string('parent_id', 36)->nullable();
    //         $table->string('text', 1024);

    //         $table->string('aspek', 30)->nullable();//kuantitas, kualitas, waktu, biaya

    //         $table->string('satuan', 256);// 0: belum  1: jumlah 2: persentase jumlah

    //         $table->string('pengaturan', 1024);// isi_target, isi_realisasi, nilai_formula, keterangan_target, kondisi_capaian, tipe_taget,tipe_numbe, panjang

    //         $table->tinyInteger('active')->unsigned()->default(1);//0:tidak 1:ya
    //         $table->tinyInteger('effect_cascading')->unsigned()->default(0);//0:tidak 1:ya
    //         $table->string('effect_cascading_id',36)->nullable();//0:tidak 1:ya

    //         $table->string('tipe_evaluasi',25); //bulan, triwulan, semester, tahun
    //         //bulan
    //         $table->string('target_bul_01',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_01',128)->nullable()->default(null);

    //         $table->string('target_bul_02',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_02',128)->nullable()->default(null);

    //         $table->string('target_bul_03',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_03',128)->nullable()->default(null);

    //         $table->string('target_bul_04',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_04',128)->nullable()->default(null);

    //         $table->string('target_bul_05',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_05',128)->nullable()->default(null);

    //         $table->string('target_bul_06',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_06',128)->nullable()->default(null);

    //         $table->string('target_bul_07',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_07',128)->nullable()->default(null);

    //         $table->string('target_bul_08',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_08',128)->nullable()->default(null);

    //         $table->string('target_bul_09',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_09',128)->nullable()->default(null);

    //         $table->string('target_bul_10',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_10',128)->nullable()->default(null);

    //         $table->string('target_bul_11',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_11',128)->nullable()->default(null);

    //         $table->string('target_bul_12',128)->nullable()->default(null);
    //         $table->string('realisasi_bul_12',128)->nullable()->default(null);

    //         //triwulan
    //         $table->string('target_tri_01',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_01',128)->nullable()->default(null);

    //         $table->string('target_tri_02',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_02',128)->nullable()->default(null);

    //         $table->string('target_tri_03',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_03',128)->nullable()->default(null);

    //         $table->string('target_tri_04',128)->nullable()->default(null);
    //         $table->string('realisasi_tri_04',128)->nullable()->default(null);

    //         //semester
    //         $table->string('target_sem_01',128)->nullable()->default(null);
    //         $table->string('realisasi_sem_01',128)->nullable()->default(null);

    //         $table->string('target_sem_02',128)->nullable()->default(null);
    //         $table->string('realisasi_sem_02',128)->nullable()->default(null);

    //         //tahun
    //         $table->string('target_tahun',128)->nullable()->default(null);
    //         $table->string('realisasi_tahun',128)->nullable()->default(null);

    //         $table->timestamps();
    //         $table->softDeletes();
    //         $table->engine = 'InnoDB';

    //         $table->foreign('tujuan_id', '2_iki_pegawai_fk_tujuan_id')->references('id')->on('siakip2_tujuan');
    //         $table->foreign('sasaran_id', '2_iki_pegawai_fk_sasaran_id')->references('id')->on('siakip2_sasaran');
    //         $table->foreign('iku_id', '2_iki_pegawai_fk_iku_id')->references('id')->on('siakip2_iku');
    //         $table->foreign('rencana_kerja_tim_id', '2_iki_pegawai_fk_rencana_kerja_tim_id')->references('id')->on('siakip2_rencana_kerja_tim');
    //         $table->foreign('rencana_kerja_pegawai_id', '2_iki_pegawai_fk_rencana_kerja_pegawai_id')->references('id')->on('siakip2_rencana_kerja_pegawai');
    //         $table->foreign('iki_tim_id', '2_iki_pegawai_fk_iki_tim_id')->references('id')->on('siakip2_iki_tim');

    //         $table->foreign('satuan_kerja_tahun_id', '2_iki_pegawai_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
    //         $table->foreign('tim_id', '2_iki_pegawai_fk_tim_id')->references('id')->on('siakip2_tim');
    //         $table->foreign('username', '2_iki_pegawai_fk_username')->references('username')->on('siakip2_users');
    //     });

    //     Schema::create('siakip2_kinerja_berkas', function (Blueprint $table) {
    //         $table->string('id',36)->primary();

    //         $table->smallInteger('tahun')->unsigned();
    //         $table->string('tipe', 36); // satuan_kerja_tahun, tim, pegawai
    //         $table->string('pemilik', 100)->nullable();
    //         $table->string('parent_id', 36)->nullable();
    //         $table->string('waktu_kode', 36)->nullable();

    //         $table->string('username_pengirim', 100)->nullable();
    //         $table->string('username_approval', 100)->nullable();

    //         $table->string('type_berkas', 30);
    //         // 0 url, 1 ganti_status, 2 text, 3 file, 4 perbaikan_target, 4 perbaikan_realisasi 5.pengajuan_penyelesaian , laporan
    //         $table->tinyInteger('is_bukti')->unsigned()->default(0);
    //         $table->tinyInteger('is_umpan_balik')->unsigned()->default(0);
    //         $table->tinyInteger('is_output')->unsigned()->default(0);
    //         $table->tinyInteger('status_approval')->unsigned()->default(0);//0: tidak 1: reject 2: approve
    //         $table->tinyInteger('status_expired')->unsigned()->default(0); //0: tidak 1: iya

    //         $table->string('text', 1024)->nullable();
    //         $table->string('nilai_baru', 256)->nullable();
    //         $table->string('file_id', 36)->nullable();

    //         $table->timestamps();
    //         $table->softDeletes();
    //         $table->engine = 'InnoDB';

    //         $table->foreign('username_pengirim', '2_eval_berkas_fk_username_pengirim')->references('username')->on('siakip2_users');
    //         $table->foreign('username_approval', '2_eval_berkas_fk_username_approval')->references('username')->on('siakip2_users');
    //         $table->foreign('file_id', '2_eval_berkas_fk_file_id')->references('id')->on('siakip2_url');
    //     });

    //     Schema::create('siakip2_evaluasi_kinerja', function (Blueprint $table) {
    //         $table->string('id',36)->primary();

    //         $table->smallInteger('tahun')->unsigned();
    //         $table->string('tipe', 36); // satuan_kerja_tahun, tim, pegawai
    //         $table->string('satuan_kerja_tahun_id', 36)->nullable();
    //         $table->string('tim_id', 36)->nullable();
    //         $table->string('username', 100)->nullable();

    //         $table->string('list_indikator', 2048)->nullable(); //json
    //         $table->string('kinerja_capaian', 255)->nullable(); //json
    //         $table->string('eksp_kinerja', 255)->nullable(); //json

    //         $table->tinyInteger('prilaku_tipe'); //0: tidak dinilai 1: dinilai
    //         $table->string('prilaku_target', 255)->nullable(); //json
    //         $table->string('prilaku_realisasi', 255)->nullable(); //json
    //         $table->string('prilaku_eksp', 255)->nullable(); //json
    //         $table->double('nilai', 5, 2)->nullable();

    //         $table->timestamps();
    //         $table->softDeletes();
    //         $table->engine = 'InnoDB';

    //         $table->foreign('satuan_kerja_tahun_id', '2_evaluasi_kinerja_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
    //         $table->foreign('tim_id', '2_evaluasi_kinerja_fk_tim_id')->references('id')->on('siakip2_tim');
    //         $table->foreign('username', '2_evaluasi_kinerja_fk_username')->references('username')->on('siakip2_users');
    //     });

    //     Schema::create('siakip2_skp', function (Blueprint $table) {
    //         $table->string('id',36)->primary();

    //         $table->smallInteger('tahun')->unsigned();
    //         $table->string('satuan_kerja_tahun_id', 36);
    //         $table->string('username', 100)->nullable();

    //         $table->string('tipe_skp', 30);// 0: biasa 1:tim 2:pimpinan
    //         $table->tinyInteger('bobot')->unsigned()->default(1);
    //         $table->tinyInteger('active')->unsigned(); //0: bukan 1: Iya

    //         $table->string('t_username', 10)->nullable();
    //         $table->string('r_username', 10)->nullable();
    //         $table->date('t_ttd')->nullable();
    //         $table->date('r_ttd')->nullable();

    //         $table->text('lock_value')->nullable();
    //         $table->string('nilai', 256)->nullable();

    //         $table->timestamps();
    //         $table->softDeletes();
    //         $table->engine = 'InnoDB';

    //         $table->foreign('username', '2_skp_fk_username')->references('username')->on('siakip2_users');
    //         $table->foreign('satuan_kerja_tahun_id', '2_skp_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

    //         $table->foreign('t_username', '2_skp_fk_t_username')->references('username')->on('siakip2_users');
    //         $table->foreign('r_username', '2_skp_fk_r_username')->references('username')->on('siakip2_users');
    //     });
    // }

    // /**
    //  * Reverse the migrations.
    //  *
    //  * @return void
    //  */
    // public function down()
    // {
    //     Schema::dropIfExists('siakip2_iki_pegawai');
    //     Schema::dropIfExists('siakip2_rencana_kerja_pegawai');
    //     Schema::dropIfExists('siakip2_iki_tim');
    //     Schema::dropIfExists('siakip2_rencana_kerja_tim');
    //     Schema::dropIfExists('siakip2_skp');
    //     Schema::dropIfExists('siakip2_iku');
    //     Schema::dropIfExists('siakip2_perjanjian_kinerja');
    //     Schema::dropIfExists('siakip2_sasaran');
    //     Schema::dropIfExists('siakip2_tujuan');
    // }
}
