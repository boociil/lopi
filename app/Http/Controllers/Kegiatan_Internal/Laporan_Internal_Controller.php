<?php

namespace App\Http\Controllers\Kegiatan_Internal;

use App\Http\Controllers\Controller;
use App\Model\Kegiatan_Internal\Kegiatan_Internal_Kegiatan;
use App\Model\Kegiatan_Internal\Kegiatan_Internal_Master;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Fungsi;
use App\Model\Umum\Peran;
use App\Model\Umum\Tim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Laporan_Internal_Controller extends Controller
{

    public function master_daftar(Request $request)
    {
        $peran_level_internal = Peran::where('pemilik_username', Auth::user()->username)
            ->where(function ($q0) {
                $q0->orWhere(function ($q1) {
                    $q1->where('tipe_peran', 'global');
                    $q1->where('peran', 'admin');
                });
                $q0->orWhere(function ($q1) {
                    $q1->where('tipe_peran', 'satuan_kerja_tahun');
                    $q1->where('tahun', session('tahun_active'));
                });
            })
        ->get();


        $admin_global = false;
        $satuan_kerja_tahun_id_arr = [];
        foreach($peran_level_internal as $item_peran){
            if($item_peran->tipe_peran == 'global'  && $item_peran->peran == 'admin'){
                $admin_global = true;
            }else if($item_peran->tipe_peran == 'satuan_kerja_tahun'){
                array_push($satuan_kerja_tahun_id_arr, $item_peran->parent_id);
            }
        }

        $satuan_kerja_tahun_ = [];
        if($admin_global){
            $satuan_kerja_tahun_ = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->get();
        }else{
            $satuan_kerja_tahun_ = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->whereIn('id', $satuan_kerja_tahun_id_arr)
                ->get();
        }

        $kegiatan_internal_master = Kegiatan_Internal_Master::where('tahun', session('tahun_active'))->get()->toArray();

        for($i=0; $i<count($kegiatan_internal_master); $i++){
            $kegiatan_internal_master[$i]['nama'] = Master_Internal_Controller::$bulan_nama[$kegiatan_internal_master[$i]['bulan'] - 1].' '.session('tahun_active');
        }

        return ['satuan_kerja_tahun' => $satuan_kerja_tahun_, 'kegiatan_internal_master' => $kegiatan_internal_master];
    }

    public function master_daftar_for_api(Request $request)
    {
        $satuan_kerja_tahun_ = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
            ->whereIn('kode_satuan_kerja', session('kode_kab'))
            ->get();


        $kegiatan_internal_master = Kegiatan_Internal_Master::where('tahun', session('tahun_active'))->get()->toArray();

        for($i=0; $i<count($kegiatan_internal_master); $i++){
            $kegiatan_internal_master[$i]['nama'] = Master_Internal_Controller::$bulan_nama[$kegiatan_internal_master[$i]['bulan'] - 1].' '.session('tahun_active');
        }

        return ['satuan_kerja_tahun' => $satuan_kerja_tahun_, 'kegiatan_internal_master' => $kegiatan_internal_master];
    }


    public function get_data(Request $request)
    {
        if( !($request->satuan_kerja_tahun_id_selected && $request->kegiatan_internal_master_id) ){
            abort(400, 'data tidak lengkap');
        }
        // $fungsi = Fungsi::where('satuan_kerja_tahun_id', $request->satuan_kerja_tahun_id_selected)
        //         ->where('tahun', session('tahun_active'))
        //         ->get();

        $tim_internal = Tim::where('satuan_kerja_tahun_id', $request->satuan_kerja_tahun_id_selected)
                ->where('tahun', session('tahun_active'))
                ->get();

        $kegiatan_internal = Kegiatan_Internal_Kegiatan::where('pemberi_satuan_kerja_tahun_id', $request->satuan_kerja_tahun_id_selected)
            ->where('kegiatan_internal_master_id', $request->kegiatan_internal_master_id)
            ->where('tahun', session('tahun_active'))
            ->get();

        return ['tim_internal' => $tim_internal, 'kegiatan_internal' => $kegiatan_internal];
    }

    public function get_data_tahun(Request $request)
    {
        // $fungsi = Fungsi::where('tahun', session('tahun_active'))
        //         ->get();

        $tim_internal = Tim::where('tahun', session('tahun_active'))
                ->get();

        $kegiatan_internal = Kegiatan_Internal_Kegiatan::where('tahun', session('tahun_active'))
            ->get();

        return ['tim_internal' => $tim_internal, 'kegiatan_internal' => $kegiatan_internal];
    }

}
