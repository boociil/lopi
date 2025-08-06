<?php

use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
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
        // $satuan_kerja_tahun_kab = Satuan_Kerja_Tahun::where('type', 'kab')->get();
        // foreach($satuan_kerja_tahun_kab as $item_kab_tahun){
        //     $all_peran = Peran::where('parent_id', $item_kab_tahun->id)->get();
        //     $username_anggota_kab = [];
        //     foreach($all_peran as $item_peran){
        //         if($item_peran->peran == 'anggota'){
        //             array_push($username_anggota_kab, $item_peran->pemilik_username);
        //         }
        //     }

        //     foreach($all_peran as $item_peran){
        //         if(!in_array($item_peran->pemilik_username, $username_anggota_kab)){
        //             $item_peran->peran = 'anggota';
        //             $item_peran->save();
        //         }else{
        //             $item_peran->delete();
        //         }
        //     }
        // }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
