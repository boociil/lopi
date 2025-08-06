<?php

namespace App\Http\Controllers\Monitoring;

use App\Model\Monitoring_Kegiatan\Monitoring_Master;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class Master_Controller extends Controller
{
    private $monitoring_master_id, $monitoring_master;
    public static $bulan_nama = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September',
            'Oktober', 'November', 'Desember'];

    public function load_data_from_param($request)
    {
        $this->monitoring_master_id = $request->route('monitoring_master_id');
        if($this->monitoring_master_id){
            $this->monitoring_master = Monitoring_Master::where('id',  $this->monitoring_master_id)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
    }

    public function daftar(Request $request)
    {
        return Monitoring_Master::where('tahun', session('tahun_active'))->get();
    }

    public function tambah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $baru = Monitoring_Master::helper_tambah($request);
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
        return $this->monitoring_master;
    }

    public function ubah(Request $request)
    {
        $this->load_data_from_param($request);
        DB::beginTransaction();
        try
        {
            $this->monitoring_master->helper_ubah($request);
            DB::commit();
            return $this->monitoring_master;
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
            $this->monitoring_master->helper_hapus();
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

}
