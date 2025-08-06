<?php

namespace App\Http\Controllers\Kegiatan_Internal;

use App\Model\Kegiatan_Internal\Kegiatan_Internal_Master;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;

class Master_Internal_Controller extends Controller
{
    private $kegiatan_internal_master_id, $kegiatan_internal_master;
    public static $bulan_nama = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September',
            'Oktober', 'November', 'Desember'];

    public function load_data_from_param($request)
    {
        $this->kegiatan_internal_master_id = $request->route('kegiatan_internal_master_id');
        if($this->kegiatan_internal_master_id){
            $this->kegiatan_internal_master = Kegiatan_Internal_Master::where('id',  $this->kegiatan_internal_master_id)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
    }

    public function daftar(Request $request)
    {
        return Kegiatan_Internal_Master::where('tahun', session('tahun_active'))->get();
    }

    public function tambah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $baru = Kegiatan_Internal_Master::helper_tambah($request);
            DB::commit();
            return $baru;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function lihat(Request $request)
    {
        $this->load_data_from_param($request);
        return $this->kegiatan_internal_master;
    }

    public function ubah(Request $request)
    {
        $this->load_data_from_param($request);
        DB::beginTransaction();
        try
        {
            $this->kegiatan_internal_master->helper_ubah($request);
            DB::commit();
            return $this->kegiatan_internal_master;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function hapus(Request $request)
    {
        $this->load_data_from_param($request);
        DB::beginTransaction();
        try
        {
            $this->kegiatan_internal_master->helper_hapus();
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

}
