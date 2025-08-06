<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCkpTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('siakip2_ckp', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('username', 100);
        //     $table->tinyInteger('bulan')->unsigned()->default(0);
        //     $table->smallInteger('tahun')->unsigned();
        //     // $table->tinyInteger('status'); // 0: submit target 1: submit realisasi 2: submit target 3: submit realisasi
        //     $table->string('ttd_t_username', 100)->nullable();
        //     $table->string('ttd_r_username', 100)->nullable();
        //     $table->date('ckp_t_ttd')->nullable();
        //     $table->date('ckp_r_ttd')->nullable();
        //     $table->string('nilai', 128)->nullable();
        //     $table->string('satuan_kerja_tahun_id', 36);

        //     $table->timestamps();
        //     $table->softDeletes();

        //     $table->unique(['username', 'bulan', 'tahun', 'deleted_at'], '2_ckp_unique');
        //     $table->foreign('username', '2_ckp_fk_username')->references('username')->on('siakip2_users');
        //     $table->foreign('ttd_t_username', '2_ckp_fk_ttd_t_username')->references('username')->on('siakip2_users');
        //     $table->foreign('ttd_r_username', '2_ckp_fk_ttd_r_username')->references('username')->on('siakip2_users');
        //     $table->foreign('satuan_kerja_tahun_id', '2_ckp_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        //     $table->engine = 'InnoDB';
        // });
        // Schema::create('siakip2_ckp_item', function (Blueprint $table) {
        //     $table->string('id',36)->primary();
        //     $table->string('ckp_id', 36);
        //     $table->string('username', 100);
        //     $table->tinyInteger('bulan')->unsigned()->default(0);
        //     $table->smallInteger('tahun')->unsigned();
        //     $table->smallInteger('urutan')->unsigned()->default(0);
        //     $table->string('to_do_id', 36)->nullable();
        //     $table->string('nama', 1024);
        //     $table->string('satuan', 255);
        //     $table->string('target', 128)->nullable();
        //     $table->string('realisasi', 128)->nullable();
        //     $table->string('kualitas', 128)->nullable();
        //     $table->string('kode_butir', 128)->nullable();
        //     $table->string('angka_kredit', 128)->nullable();
        //     $table->tinyInteger('status_t')->unsigned()->default(0);  // 0: tidak 1: ya
        //     $table->tinyInteger('status_r')->unsigned()->default(0);  // 0: tidak 1: ya
        //     $table->timestamps();
        //     $table->softDeletes();

        //     $table->foreign('ckp_id', '2_ckp_item_fk_ckp_id')->references('id')->on('siakip2_ckp');
        //     $table->foreign('to_do_id', '2_ckp_item_fk_to_do_id')->references('id')->on('siakip2_to_do');
        //     //$table->foreign('matriks_item_pekerjaan_bulan_orang_id', 'ckp_item_fk_item_pekerjaan_bulan_orang_id')->references('id')->on('matriks_item_pekerjaan_bulan_orang');
        //     $table->engine = 'InnoDB';
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('siakip2_ckp_item');
        // Schema::dropIfExists('siakip2_ckp');
    }
}
