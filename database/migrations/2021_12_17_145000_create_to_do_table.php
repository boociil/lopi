<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateToDoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // Schema::create('siakip2_kegiatan', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('pemilik_satuan_kerja_tahun_id', 36)->nullable();
        //     $table->string('pemilik_tim_id', 36)->nullable();
        //     $table->smallInteger('tahun')->unsigned();

        //     $table->smallInteger('urutan')->unsigned();
        //     $table->string('nama_pendek', 1024);
        //     $table->string('nama', 1024);

        //     $table->string('photo_url_id', 36)->nullable();
        //     $table->string('warna', 256)->nullable(); //json

        //     $table->string('html_awal', 4096)->nullable();
        //     $table->string('html_samping', 4096)->nullable();

        //     $table->timestamps();
        //     $table->softDeletes();
        //     $table->engine = 'InnoDB';

        //     $table->foreign('pemilik_tim_id', '2_kegiatan_fk_tim_id')->references('id')->on('siakip2_tim');
        //     $table->foreign('pemilik_satuan_kerja_tahun_id', '2_kegiatan_fk_pemilik_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        //     $table->foreign('photo_url_id', '2_kegiatan_fk_photo_url_id')->references('id')->on('siakip2_url');
        // });

        // Schema::create('siakip2_sub_kegiatan', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('pemilik_satuan_kerja_tahun_id', 36)->nullable();
        //     $table->string('pemilik_tim_id', 36)->nullable();
        //     $table->string('kegiatan_id', 36);
        //     $table->smallInteger('tahun')->unsigned();

        //     $table->smallInteger('urutan')->unsigned();
        //     $table->string('nama', 1024);
        //     $table->string('warna', 256)->nullable(); //json

        //     $table->timestamps();
        //     $table->softDeletes();
        //     $table->engine = 'InnoDB';

        //     $table->foreign('pemilik_tim_id', '2_sub_kegiatan_fk_tim_id')->references('id')->on('siakip2_tim');
        //     $table->foreign('pemilik_satuan_kerja_tahun_id', '2_sub_kegiatan_fk_pemilik_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        //     $table->foreign('kegiatan_id', '2_sub_kegiatan_fk_kegiatan_id')->references('id')->on('siakip2_kegiatan');
        // });

        // Schema::create('siakip2_sub_halaman', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('halaman_id', 36)->nullable();

        //     $table->smallInteger('urutan')->unsigned();
        //     $table->string('nama', 1024);
        //     $table->string('tipe', 30);//html, peran_hasil, grafik, to_do
        //     $table->string('pengaturan', 1024)->nullable();

        //     $table->string('copy_from_id',36);
        //     $table->timestamps();
        //     $table->softDeletes();
        //     $table->engine = 'InnoDB';

        //     $table->foreign('halaman_id', 'sub_halaman_fk_page_id')->references('id')->on('siakip2_halaman');
        // });

        // Schema::create('siakip2_isi_html', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('halaman_id', 36)->nullable();
        //     $table->string('sub_halaman_id', 36)->nullable();

        //     $table->text('text');

        //     $table->string('copy_from_id',36);
        //     $table->timestamps();
        //     $table->softDeletes();
        //     $table->engine = 'InnoDB';

        //     $table->foreign('halaman_id', 'isi_html_fk_page_id')->references('id')->on('siakip2_halaman');
        // });

        // Schema::create('siakip2_isi_grafik', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('halaman_id', 36)->nullable();
        //     $table->string('sub_halaman_id', 36)->nullable();

        //     $table->string('pengaturan', 1024)->nullable();
        //     $table->string('to_do_id', 36)->nullable();

        //     $table->string('copy_from_id',36);
        //     $table->timestamps();
        //     $table->softDeletes();
        //     $table->engine = 'InnoDB';

        //     $table->foreign('halaman_id', 'isi_grafik_fk_page_id')->references('id')->on('siakip2_halaman');
        // });

        // Schema::create('siakip2_isi_to_do', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('halaman_id', 36)->nullable();
        //     $table->string('sub_halaman_id', 36)->nullable();

        //     $table->string('pengaturan', 1024)->nullable();

        //     $table->string('copy_from_id',36);
        //     $table->timestamps();
        //     $table->softDeletes();

        //     $table->engine = 'InnoDB';
        // });

        // Schema::create('siakip2_folder', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('pemilik_username', 100)->nullable();
        //     $table->smallInteger('tahun')->unsigned();

        //     $table->smallInteger('urutan')->unsigned();
        //     $table->string('nama_pendek', 128);
        //     $table->text('nama', 1024)->nullable();

        //     $table->string('photo_url_id', 36)->nullable();
        //     $table->string('heroicon', 36)->nullable();
        //     $table->string('warna', 256)->nullable(); //json

        //     $table->timestamps();
        //     $table->softDeletes();
        //     $table->engine = 'InnoDB';

        //     $table->foreign('pemilik_username', 'folder_fk_username')->references('username')->on('siakip2_users');
        //     $table->foreign('photo_url_id', 'folder_fk_photo_url_id')->references('id')->on('siakip2_url');
        // });

        // Schema::create('siakip2_to_do', function (Blueprint $table) {
        //     $table->string('id',36)->primary();

        //     $table->string('kegiatan_id', 36)->nullable();
        //     $table->string('sub_kegiatan_id', 36)->nullable();
        //     $table->tinyInteger('bulan')->default(0);
        //     $table->smallInteger('tahun')->unsigned();

        //     $table->string('arr_parent_to_do_id', 400)->nullable();
        //     $table->string('disposisi_to_do_arr', 2048)->nullable();
        //     $table->string('disposisi_to_do_arr_id', 400)->nullable();

        //     $table->string('type', 50); //grup, perorangan

        //     $table->string('pemberi_satuan_kerja_tahun_id', 36)->nullable();
        //     $table->string('pemberi_tim_id', 36)->nullable();
        //     $table->string('pemberi_username', 100)->nullable();

        //     $table->string('satuan_kerja_tahun_id', 36)->nullable();
        //     $table->string('tim_id', 36)->nullable();
        //     $table->string('username', 100)->nullable();

        //     $table->smallInteger('urutan')->unsigned()->default(0);
        //     $table->smallInteger('urutan_penerima')->unsigned()->default(0);
        //     $table->tinyInteger('sembunyikan')->unsigned()->default(0); //1:ya, 0:tidak
        //     $table->tinyInteger('sembunyikan_penerima')->unsigned()->default(0); //1:ya, 0:tidak

        //     $table->string('text', 1024);
        //     $table->string('satuan', 256)->nullable();
        //     $table->string('target', 128)->nullable();
        //     $table->string('realisasi', 128)->nullable();
        //     $table->string('capaian', 128)->nullable();

        //     $table->string('kualitas', 128)->nullable();
        //     $table->string('kode_butir', 128)->nullable();
        //     $table->string('angka_kredit', 128)->nullable();
        //     $table->string('keterangan', 512)->nullable(); // json

        //     $table->string('nilai_grup', 512)->nullable(); // json

        //     $table->tinyInteger('masukkan_kalender')->unsigned()->default(0);//1:masuk, 0:tidak_masuk
        //     $table->string('status', 50)->nullable();//mengajukan, belum_dikerja, dikerjakan, terhenti, selesai
        //     $table->string('status_penerima', 50)->nullable();//mengajukan, belum_dikerja, dikerjakan, terhenti, selesai

        //     $table->date('batas_awal')->nullable()->default(null);
        //     $table->date('batas_akhir')->nullable()->default(null);

        //     $table->tinyInteger('butuh_monitoring')->default(0); //0 tidak, 1:tidak

        //     $table->timestamps();
        //     $table->softDeletes();

        //     $table->foreign('kegiatan_id', '2_to_do_fk_kegiatan_id')->references('id')->on('siakip2_kegiatan');
        //     $table->foreign('sub_kegiatan_id', '2_to_do_fk_sub_kegiatan_id')->references('id')->on('siakip2_sub_kegiatan');

        //     $table->foreign('pemberi_username', '2_to_do_fk_pemberi_username')->references('username')->on('siakip2_users');
        //     $table->foreign('pemberi_tim_id', '2_to_do_fk_pemberi_tim_id')->references('id')->on('siakip2_tim');
        //     $table->foreign('pemberi_satuan_kerja_tahun_id', '2_to_do_fk_pemberi_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

        //     $table->foreign('username', '2_to_do_fk_username')->references('username')->on('siakip2_users');
        //     $table->foreign('tim_id', '2_to_do_fk_tim_id')->references('id')->on('siakip2_tim');
        //     $table->foreign('satuan_kerja_tahun_id', '2_to_do_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

        //     $table->engine = 'InnoDB';

        // });

        // Schema::create('siakip2_item_to_do_pelaksana', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('item_to_do_id', 36)->nullable();

        //     $table->string('pemberi_username', 100)->nullable();
        //     $table->string('pemberi_tim_id', 36)->nullable();
        //     $table->string('pemberi_satuan_kerja_tahun_id', 36)->nullable();

        //     $table->string('pemilik_username', 100)->nullable();
        //     $table->string('pemilik_tim_id', 36)->nullable();
        //     $table->string('pemilik_satuan_kerja_tahun_id', 36)->nullable();

        //     $table->smallInteger('tahun')->unsigned();

        //     $table->smallInteger('target')->unsigned()->default(0);
        //     $table->smallInteger('realisasi')->unsigned()->default(0);
        //     $table->string('satuan', 256)->nullable();

        //     $table->string('tanggal_tulis', 100); //json
        //     $table->date('awal');
        //     $table->date('akhir');


        //     $table->timestamps();
        //     $table->softDeletes();

        //     $table->foreign('pemberi_username', 'item_to_do_pekerja_fk_pemberi_username')->references('username')->on('siakip2_users');
        //     $table->foreign('pemberi_tim_id', 'item_to_do_pekerja_fk_pemberi_tim_id')->references('id')->on('siakip2_tim');
        //     $table->foreign('pemberi_satuan_kerja_tahun_id', 'item_to_do_pekerja_fk_pemberi_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

        //     $table->foreign('pemilik_username', 'item_to_do_pekerja_fk_pemilik_username')->references('username')->on('siakip2_users');
        //     $table->foreign('pemilik_tim_id', 'item_to_do_pekerja_fk_pemilik_tim_id')->references('id')->on('siakip2_tim');
        //     $table->foreign('pemilik_satuan_kerja_tahun_id', 'item_to_do_pekerja_fk_pemilik_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

        //     $table->foreign('item_to_do_id', 'item_to_do_pekerja_fk_item_to_do_id')->references('id')->on('siakip2_item_to_do');
        //     $table->engine = 'InnoDB';

        // });

        // Schema::create('siakip2_to_do_item_hubungan', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('isi_to_do_id',36)->nullable();
        //     $table->string('item_to_do_id', 36)->nullable();
        //     $table->tinyInteger('is_original')->unsigned()->default(1);
        //     $table->smallInteger('tahun')->unsigned();

        //     $table->timestamps();
        //     $table->softDeletes();

        //     $table->foreign('isi_to_do_id', 'to_do_item_hubungan_fk_isi_to_do_id')->references('id')->on('siakip2_isi_to_do');
        //     $table->foreign('item_to_do_id', 'to_do_item_hubungan_fk_item_to_do_id')->references('id')->on('siakip2_item_to_do');
        //     $table->engine = 'InnoDB';
        // });

        // Schema::create('siakip2_to_do_berkas', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->smallInteger('tahun')->unsigned();
        //     $table->string('to_do_id', 36)->nullable();

        //     $table->string('to_do_pemberi_satuan_kerja_tahun_id', 36)->nullable();
        //     $table->string('to_do_pemberi_tim_id', 36)->nullable();
        //     $table->string('to_do_pemberi_username', 100)->nullable();

        //     $table->string('to_do_satuan_kerja_tahun_id', 36)->nullable();
        //     $table->string('to_do_tim_id', 36)->nullable();
        //     $table->string('to_do_username', 100)->nullable();

        //     $table->string('username_pengirim', 100)->nullable();
        //     $table->string('username_approval', 100)->nullable();

        //     $table->string('type_berkas', 30);

        //     $table->string('text', 1024)->nullable();
        //     $table->string('nilai_baru', 256)->nullable();
        //     $table->string('file_id', 36)->nullable();

        //     $table->timestamps();
        //     $table->softDeletes();
        //     $table->engine = 'InnoDB';

        //     $table->foreign('to_do_pemberi_username', '2_to_do_berkas_fk_pemberi_username')->references('username')->on('siakip2_users');
        //     $table->foreign('to_do_pemberi_tim_id', '2_to_do_berkas_fk_pemberi_tim_id')->references('id')->on('siakip2_tim');
        //     $table->foreign('to_do_pemberi_satuan_kerja_tahun_id', '2_to_do_berkas_fk_pemberi_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

        //     $table->foreign('to_do_username', '2_to_do_berkas_fk_username')->references('username')->on('siakip2_users');
        //     $table->foreign('to_do_tim_id', '2_to_do_berkas_fk_tim_id')->references('id')->on('siakip2_tim');
        //     $table->foreign('to_do_satuan_kerja_tahun_id', '2_to_do_berkas_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

        //     $table->foreign('username_pengirim', '2_item_to_do_berkas_fk_username_pengirim')->references('username')->on('siakip2_users');
        //     $table->foreign('username_approval', '2_item_to_do_berkas_fk_username_approval')->references('username')->on('siakip2_users');
        //     $table->foreign('file_id', '2_item_to_do_berkas_fk_file_id')->references('id')->on('siakip2_url');

        //     $table->foreign('to_do_id', '2_to_do_berkas_fk_to_do_id')->references('id')->on('siakip2_to_do');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('siakip2_item_to_do_berkas');
        // Schema::dropIfExists('siakip2_item_to_do_pekerja_tanggal');
        // Schema::dropIfExists('siakip2_item_to_do_pekerja');
        // Schema::dropIfExists('siakip2_item_to_do');
        // Schema::dropIfExists('siakip2_to_do');
    }
}
