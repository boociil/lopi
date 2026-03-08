<?php

use App\Model\Lopi\Appreciate_Master;
use App\Model\Lopi\Jadwal;
use App\Model\Lopi\Pinjam_Mobil;
use App\Model\Lopi\Pinjam_Zoom;
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
        //appreciate
        Schema::table('siakip2_appreciate_master', function (Blueprint $table) {
            $table->string('satuan_kerja_tahun_id', 36)->nullable();

            $table->foreign('satuan_kerja_tahun_id', 'appreciate_master_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });

        Appreciate_Master::query()->update(['satuan_kerja_tahun_id' => '0224eb80-e470-11f0-904b-1315527b7f2d']);

        //jadwal
        Schema::table('siakip2_pinjam_mobil', function (Blueprint $table) {
            $table->string('satuan_kerja_tahun_id', 36)->nullable();

            $table->foreign('satuan_kerja_tahun_id', 'mobil_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });
        Pinjam_Mobil::query()->update(['satuan_kerja_tahun_id' => '0224eb80-e470-11f0-904b-1315527b7f2d']);

        Schema::table('siakip2_pinjam_zoom', function (Blueprint $table) {
            $table->string('satuan_kerja_tahun_id', 36)->nullable();

            $table->foreign('satuan_kerja_tahun_id', 'zoom_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });
        Pinjam_Zoom::query()->update(['satuan_kerja_tahun_id' => '0224eb80-e470-11f0-904b-1315527b7f2d']);

        Schema::table('siakip2_jadwal', function (Blueprint $table) {
            $table->string('satuan_kerja_tahun_id', 36)->nullable();

            $table->foreign('satuan_kerja_tahun_id', 'jadwal_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });
        Jadwal::query()->update(['satuan_kerja_tahun_id' => '0224eb80-e470-11f0-904b-1315527b7f2d']);


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
