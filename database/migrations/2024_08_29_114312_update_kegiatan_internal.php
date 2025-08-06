<?php

use App\Model\Kegiatan_Internal\Kegiatan_Internal_Kegiatan;
use App\Model\Umum\Tim;
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
        Schema::table('siakip2_kegiatan_internal_kegiatan', function (Blueprint $table) {
            $table->string('pelaksana_satuan_kerja_tahun_id', 36)->nullable()->after('pemberi_username');
            $table->foreign('pelaksana_satuan_kerja_tahun_id', 'keg_internal_fk_pelaksana_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');

            $table->string('monitoring_kegiatan_berkaitan_id', 36)->nullable()->after('pelaksana_tim_id');
            $table->foreign('monitoring_kegiatan_berkaitan_id', 'keg_internal_fk_monitoring_kegiatan_berkaitan_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });

        $all_tim = Tim::all();
        $all_tim_dict = [];
        foreach($all_tim as $item_tim){
            $all_tim_dict[$item_tim->id] = $item_tim;
        }

        $kegiatan_internal = Kegiatan_Internal_Kegiatan::all();
        foreach($kegiatan_internal as $item_kegiatan_internal){
            $item_tim = $all_tim_dict[$item_kegiatan_internal->pelaksana_tim_id];
            $item_kegiatan_internal->pelaksana_satuan_kerja_tahun_id = $item_tim->satuan_kerja_tahun_id;
            $item_kegiatan_internal->save();
        }


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
