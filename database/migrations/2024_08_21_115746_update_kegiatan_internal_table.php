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
        Schema::rename('siakip2_kegiatan_prov_berkas', 'siakip2_kegiatan_internal_berkas');
        Schema::table('siakip2_kegiatan_internal_berkas', function (Blueprint $table) {
            $table->renameColumn('kegiatan_prov_master_id', 'kegiatan_internal_master_id');
            $table->renameColumn('kegiatan_prov_kegiatan_id', 'kegiatan_internal_kegiatan_id');

            $table->renameIndex('keg_prov_berkas_fk_username_pengirim', 'keg_internal_berkas_fk_username_pengirim');
            $table->renameIndex('keg_prov_berkas_fk_username_approval', 'keg_internal_berkas_fk_username_approval');
            $table->renameIndex('keg_prov_berkas_fk_file_id', 'keg_internal_berkas_fk_file_id');

            $table->renameIndex('keg_prov_berkas_fk_kegiatan_prov_master_id', 'keg_internal_berkas_fk_kegiatan_internal_master_id');
            $table->renameIndex('keg_prov_berkas_fk_kegiatan_prov_kegiatan_id', 'keg_internal_berkas_fk_kegiatan_internal_kegiatan_id');
        });


        Schema::rename('siakip2_kegiatan_prov_kegiatan', 'siakip2_kegiatan_internal_kegiatan');
        Schema::table('siakip2_kegiatan_internal_kegiatan', function (Blueprint $table) {
            $table->renameColumn('kegiatan_prov_master_id', 'kegiatan_internal_master_id');

            $table->renameIndex('keg_prov_fk_kegiatan_prov_master_id', 'keg_internal_fk_kegiatan_internal__master_id');
            $table->renameIndex('keg_prov_fk_pemberi_satuan_kerja_tahun_id', 'keg_internal_fk_pemberi_satuan_kerja_tahun_id');
            $table->renameIndex('keg_prov_fk_pemberi_fungsi_id', 'keg_internal_fk_pemberi_fungsi_id');
            $table->renameIndex('keg_prov_fk_pemberi_tim_id', 'keg_internal_fk_pemberi_tim_id');
            $table->renameIndex('keg_prov_fk_pemberi_username', 'keg_internal_fk_pemberi_username');
            $table->renameIndex('keg_prov_fk_pelaksana_tim_id', 'keg_internal_fk_pelaksana_tim_id');
        });



        Schema::rename('siakip2_kegiatan_prov_master', 'siakip2_kegiatan_internal_master');


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
