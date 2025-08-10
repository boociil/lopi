<?php

namespace App\Http\Controllers\Umum;

use App\Http\Controllers\Controller;
use App\Model\Kegiatan_Internal\Kegiatan_Internal_Kegiatan;
use App\Model\Kegiatan_Internal\Kegiatan_Internal_Master;
use App\Model\Monitoring_Kegiatan\Monitoring_Kegiatan;
use App\Model\Monitoring_Kegiatan\Monitoring_Master;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use App\Model\Umum\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class Beranda_Controller extends Controller
{

    private $username, $user;
    public static $bulan_nama = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September',
            'Oktober', 'November', 'Desember'];

    public function load_pertama($request)
    {
        $this->username = $request->route('username');
        if($this->username){
            $this->user = User::where('username', $this->username)
                ->first();
        }
    }

    public function get_bulan(Request $request){
        $list_ada = [];
        $master_monitoring = Monitoring_Master::where('tahun', session('tahun_active'))->get();
        $master_kegiatan_prov = Kegiatan_Internal_Master::where('tahun', session('tahun_active'))->get();

        foreach($master_monitoring as $item){
            if(!in_array($item->bulan, $list_ada)){
                array_push($list_ada, $item->bulan);
            }
        }

        foreach($master_kegiatan_prov as $item){
            if(!in_array($item->bulan, $list_ada)){
                array_push($list_ada, $item->bulan);
            }
        }

        sort($list_ada);

        $return_data = [];
        foreach($list_ada as $item){
            array_push($return_data, ['bulan' => $item, 'tulisan' => 'Bulan '.Beranda_Controller::$bulan_nama[$item-1]]);
        }
        return $return_data;
    }

    public function monitoring_kegiatan(Request $request)
    {
        if(!$request->bulan){
            abort(404, "Bulan tidak ada");
            return;
        }
        $peran_model_arr = Peran::where(function ($query){
            $query->orWhere("tipe_peran", 'satuan_kerja_tahun');
            $query->orWhere("tipe_peran", 'tim');
        })
        ->where('pemilik_username', Auth::user()->username)
        ->where('tahun', session('tahun_active'))
        ->get();

        $satuan_kerja_id_arr = [];
        $tim_id_arr = [];
        foreach($peran_model_arr as $peran_item){
            if($peran_item->tipe_peran == 'satuan_kerja_tahun'){
                array_push($satuan_kerja_id_arr, $peran_item->parent_id);
            }else if($peran_item->tipe_peran == 'tim'){
                array_push($tim_id_arr, $peran_item->parent_id);
            }
        }
        return [
            'satuan_kerja_tahun' => Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))->select('id', 'nama')->get(),
            'tim' => Tim::where('tahun', session('tahun_active'))->select('id', 'nama')->get(),
            'monitoring' => Monitoring_Kegiatan::with('pelaksana_target_kosong')
                    ->where('bulan', $request->bulan)
                    ->where('tahun', session('tahun_active'))
                    ->orderBy('tanggal_akhir')
                    ->get(),
            'kegiatan_prov' => Kegiatan_Internal_Kegiatan::where('bulan', $request->bulan)
                    ->where('tahun', session('tahun_active'))
                    ->where(function($query) use ($satuan_kerja_id_arr, $tim_id_arr) {
                        $query->whereIn('pemberi_satuan_kerja_tahun_id', $satuan_kerja_id_arr);
                        $query->whereIn('pemberi_tim_id', $tim_id_arr);
                        $query->whereIn('pelaksana_satuan_kerja_tahun_id', $satuan_kerja_id_arr);
                        $query->whereIn('pelaksana_tim_id', $tim_id_arr);
                    })
                    ->orderBy('tanggal_akhir')
                    ->get()];

    }

    public function redirect_func(Request $request)
    {
        return redirect('/beranda');
    }

    public function beranda(Request $request)
    {
        return Inertia::render('beranda', [
            "peran"=> fn () => Peran::get_my_peran(),
        ]);
    }

}
