<?php

namespace App\Http\Controllers\Monitoring;

use App\Model\Monitoring_Kegiatan\Monitoring_Master;
use App\Http\Controllers\Controller;
use App\Model\Monitoring_Kegiatan\Monitoring_Kegiatan;
use App\Model\Umum\Satuan_Kerja_Tahun;
use Illuminate\Http\Request;

class Laporan_Controller extends Controller
{

    public function master_daftar(Request $request)
    {
        $satuan_kerja_tahun_type_prov = Satuan_Kerja_Tahun::where(function ($query) {
                    $query->where('type', 'prov')
                        ->orWhere('type', 'pusat');
                })
            ->where('tahun', session('tahun_active'))
            ->get();

        $monitoring_master = Monitoring_Master::where('tahun', session('tahun_active'))->get();

        $return_data = [];
        foreach($satuan_kerja_tahun_type_prov as $item_satuan_kerja_tahun){
            foreach($monitoring_master as $item_monitoring_master){
                $item_data = ['id' => $item_monitoring_master->id.'__'.$item_satuan_kerja_tahun->id,
                    'nama' => 'Bulan '.Master_Controller::$bulan_nama[$item_monitoring_master->bulan - 1].' '.$item_satuan_kerja_tahun->nama,
                    'master' => $item_monitoring_master,
                    'satuan_kerja_tahun' => $item_satuan_kerja_tahun];
                array_push($return_data, $item_data);
            }
        }
        return $return_data;
    }

    public function get_data(Request $request)
    {
        if( !($request->satuan_kerja_tahun_id_selected && $request->monitoring_master_id) ){
            abort(400, 'data tidak lengkap');
        }
        $satuan_kerja_tahun_child = Satuan_Kerja_Tahun::where('parent_id', $request->satuan_kerja_tahun_id_selected)
            ->where('tahun', session('tahun_active'))
            ->get();
        $monitoring_kegiatan = Monitoring_Kegiatan::with('pelaksana')
            ->where('pemberi_satuan_kerja_tahun_id', $request->satuan_kerja_tahun_id_selected)
            ->where('monitoring_master_id', $request->monitoring_master_id)
            ->where('tahun', session('tahun_active'))
            ->get();

        return ['satuan_kerja_tahun_child' => $satuan_kerja_tahun_child, 'monitoring_kegiatan' => $monitoring_kegiatan];
    }

    public function get_data_tahun(Request $request)
    {
        $satuan_kerja_tahun_child = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
            ->get();
        $monitoring_kegiatan = Monitoring_Kegiatan::with('pelaksana')
            ->where('tahun', session('tahun_active'))
            ->get();

        return ['satuan_kerja_tahun_child' => $satuan_kerja_tahun_child, 'monitoring_kegiatan' => $monitoring_kegiatan];
    }

}
