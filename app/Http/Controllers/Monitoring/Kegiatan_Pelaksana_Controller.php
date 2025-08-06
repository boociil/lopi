<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Model\Monitoring_Kegiatan\Monitoring_Kegiatan;
use App\Model\Monitoring_Kegiatan\Monitoring_Kegiatan_Pelaksana;
use App\Model\Monitoring_Kegiatan\Monitoring_Master;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use Illuminate\Http\Request;

class Kegiatan_Pelaksana_Controller extends Controller
{
    private $monitoring_master, $monitoring_kegiatan, $pemberi_satuan_kerja_tahun, $pemberi_tim;
    private $monitoring_kegiatan_pelaksana_id, $monitoring_kegiatan_pelaksana;

    public function load_pertama($request)
    {
        $this->monitoring_kegiatan_pelaksana_id = $request->route('monitoring_kegiatan_pelaksana_id');
        if($this->monitoring_kegiatan_pelaksana_id){
            $this->monitoring_kegiatan_pelaksana = Monitoring_Kegiatan_Pelaksana::where('id',  $this->monitoring_kegiatan_pelaksana_id)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
        $this->monitoring_master = Monitoring_Master::where('id', $this->monitoring_kegiatan_pelaksana->monitoring_master_id)
            ->where('tahun', session('tahun_active'))
            ->first();
        $this->monitoring_kegiatan = Monitoring_Kegiatan::where('id', $this->monitoring_kegiatan_pelaksana->monitoring_kegiatan_id)
            ->where('tahun', session('tahun_active'))
            ->first();
        $this->pemberi_satuan_kerja_tahun = Satuan_Kerja_Tahun::where('id', $this->monitoring_kegiatan->pemberi_satuan_kerja_tahun_id)
            ->where('tahun', session('tahun_active'))
            ->first();
        if(!$this->monitoring_kegiatan_pelaksana || !$this->monitoring_master || !$this->monitoring_kegiatan || !$this->pemberi_satuan_kerja_tahun ){
            abort(404, 'Id tidak ditemukan');
        }
        if($this->monitoring_kegiatan->pemberi_tim_id){
            $this->pemberi_tim = Tim::where('satuan_kerja_tahun_id', $this->monitoring_kegiatan->pemberi_satuan_kerja_tahun_id)
                ->where('id', $this->monitoring_kegiatan->pemberi_tim_id)
                ->where('tahun', session('tahun_active'))
                ->first();
            if(!$this->pemberi_tim ){
                abort(404, 'Id tidak ditemukan');
            }
        }
    }

    public function lihat(Request $request)
    {
        $this->load_pertama($request);
        return ['monitoring_master'=> $this->monitoring_master,
            'satuan_kerja_tahun' => $this->pemberi_satuan_kerja_tahun,
            'tim' => $this->pemberi_tim,
            'monitoring_kegiatan' => $this->monitoring_kegiatan,
            'monitoring_kegiatan_pelaksana' => $this->monitoring_kegiatan_pelaksana];
    }

    // public function ubah(Request $request)
    // {
    //     $this->load_pertama($request);
    //     DB::beginTransaction();
    //     try
    //     {

    //         DB::commit();
    //         return $this->monitoring_master;
    //     }catch(Exception $ex){
    //         DB::rollBack();
    //         Log::error($ex);
    //         return abort(500);
    //     }
    // }

    // public function hapus(Request $request)
    // {
    //     $this->load_pertama($request);
    //     DB::beginTransaction();
    //     try
    //     {

    //         DB::commit();
    //         return 1;
    //     }catch(Exception $ex){
    //         DB::rollBack();
    //         Log::error($ex);
    //         return abort(500);
    //     }
    // }

}
