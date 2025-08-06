<?php

namespace App\Http\Controllers\Umum;

use App\Http\Controllers\Controller;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Peran;
use App\Model\Umum\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Profil_Controller extends Controller
{
    public function __construct(Request $request)
    {

    }

    public function init_reload(Request $request)
    {
        $satuan_kerja_tahun_prov_id  = Satuan_Kerja_Tahun::where('type', 'prov')->get()->pluck('id')->toArray();
        return ['user' => User::where('username', Auth::user()->username)->first() ,
            'all_peran' => Peran::where(function($query){
                    $query->where('tahun', session('tahun_active'))
                        ->orWhere('tahun', null);
                })->where('pemilik_username', Auth::user()->username)->get(),
            "all_user" => User::select('username', 'nama')->get(),
            "tahun_array" => explode(",", env("TAHUN_ARRAY")),
            "tahun_active" => session('tahun_active'),
            "tanggal_now" => Carbon::today()->format('Y-m-d'),
            'satuan_kerja_tahun_prov_id' => $satuan_kerja_tahun_prov_id,
            "VERSI_BACKEND" => env("VERSI_BACKEND"),
            "VERSI_FRONTEND" => env("VERSI_FRONTEND"),
        ];
    }

    // public function data_lihat(Request $request)
    // {
    //     return Auth::user();
    // }

    // public function data_ubah(Request $request)
    // {
    //     DB::beginTransaction();
    //     try
    //     {
    //         $user = Auth::user();
    //         $user->helper_ubah_profil($request);
    //         DB::commit();
    //         return $this->init_reload($request);
    //     }catch(Exception $ex){
    //         DB::rollBack();
    //         return abort(500, $ex->getMessage());
    //     }
    // }

    public function tahun_ubah(Request $request, $tahun)
    {
        try{
            $tahun_array = explode(",", env("TAHUN_ARRAY"));
            if(in_array($tahun, $tahun_array)){
                $request->session()->put('tahun_active', $tahun);
                return 1;
            }else{
                return abort(404);
            }
        }catch(Exception $e){
            return abort(404);
        }
    }

    public function berkas_ku(Request $request)
    {
        $peran_asli = Peran::where('pemilik_username', Auth::user()->username)
                    ->where('tahun', session('tahun_active'))
                    ->get();

        $satuan_kerja_tahun_id = [];
        $fungsi_id = [];
        $tim_id = [];

        foreach($peran_asli as $item){
            if($item->tipe_peran == 'satuan_kerja_tahun' && ($item->peran =='pimpinan' || $item->peran =='admin')){
                array_push($satuan_kerja_tahun_id, $item->parent_id);
            }else if($item->tipe_peran == 'fungsi' && $item->peran =='pj'){
                array_push($fungsi_id, $item->parent_id);
            }else if($item->tipe_peran == 'tim' && ($item->peran =='ketua' || $item->peran =='admin')){
                array_push($tim_id, $item->parent_id);
            }
        }

        $query_hasil_kegiatan_prov = DB::table('siakip2_kegiatan_internal_berkas')
            ->join('siakip2_kegiatan_internal_kegiatan', 'siakip2_kegiatan_internal_berkas.kegiatan_internal_kegiatan_id', '=', 'siakip2_kegiatan_internal_kegiatan.id')
            ->where(function ($query) use ($satuan_kerja_tahun_id, $fungsi_id, $tim_id){
                    $query->whereIn('siakip2_kegiatan_internal_kegiatan.pemberi_satuan_kerja_tahun_id', $satuan_kerja_tahun_id)
                        ->orWhereIn('siakip2_kegiatan_internal_kegiatan.pemberi_fungsi_id', $fungsi_id)
                        ->orWhereIn('siakip2_kegiatan_internal_kegiatan.pemberi_tim_id', $tim_id);
            })->whereRaw('DATE(siakip2_kegiatan_internal_berkas.updated_at) > DATE_SUB(CURDATE(), INTERVAL  7 day)')
            ->whereNull('siakip2_kegiatan_internal_berkas.deleted_at');

        if($request->kegiatan_prov_last_updated_at != null){
            $query_hasil_kegiatan_prov->where('siakip2_kegiatan_internal_berkas.updated_at', '>', $request->kegiatan_prov_last_updated_at);
        }

        $query_hasil_kegiatan_prov->select('siakip2_kegiatan_internal_berkas.*', 'siakip2_kegiatan_internal_kegiatan.nama');

        $query_hasil_monitoring2 = DB::table('siakip2_monitoring_kegiatan_pelaksana_berkas')
            ->join('siakip2_monitoring_kegiatan_pelaksana', 'siakip2_monitoring_kegiatan_pelaksana_berkas.monitoring_kegiatan_pelaksana_id', '=', 'siakip2_monitoring_kegiatan_pelaksana.id')
            ->join('siakip2_monitoring_kegiatan', 'siakip2_monitoring_kegiatan_pelaksana_berkas.monitoring_kegiatan_id', '=', 'siakip2_monitoring_kegiatan.id')
            ->where(function ($query) use ($satuan_kerja_tahun_id, $tim_id){
                    $query->where(function($q) use ($satuan_kerja_tahun_id, $tim_id){
                            $q->whereIn('siakip2_monitoring_kegiatan_pelaksana.pelaksana_satuan_kerja_tahun_id', $satuan_kerja_tahun_id)
                                ->orWhereIn('siakip2_monitoring_kegiatan_pelaksana.pelaksana_tim_id', $tim_id);
                        })->orWhere(function($q) use ($satuan_kerja_tahun_id, $tim_id){
                            $q->whereIn('siakip2_monitoring_kegiatan.pemberi_satuan_kerja_tahun_id', $satuan_kerja_tahun_id)
                                ->orWhereIn('siakip2_monitoring_kegiatan.pemberi_tim_id', $tim_id);
                        });
            })->whereRaw('DATE(siakip2_monitoring_kegiatan_pelaksana_berkas.updated_at) > DATE_SUB(CURDATE(), INTERVAL  7 day)')
            ->whereNull('siakip2_monitoring_kegiatan_pelaksana_berkas.deleted_at');

        if($request->monitoring_last_updated_at != null){
            $query_hasil_monitoring2->where('siakip2_monitoring_kegiatan_pelaksana_berkas.updated_at', '>', $request->monitoring_last_updated_at);
        }

        $query_hasil_monitoring2->select('siakip2_monitoring_kegiatan_pelaksana_berkas.*', 'siakip2_monitoring_kegiatan.nama');

        return ['kegiatan_prov_berkas' => $query_hasil_kegiatan_prov
                        ->orderBy('updated_at', 'desc')
                        ->get(),
                'monitoring_berkas' => $query_hasil_monitoring2
                        ->orderBy('updated_at', 'desc')
                        ->get()];
    }

    public function pengumuman_ku(Request $request)
    {
        $peran_asli = Peran::where('pemilik_username', Auth::user()->username)->get();
        $pemilik_id = [];
        array_push($pemilik_id, Auth::user()->username);
        foreach($peran_asli as $item ){
            if(!in_array($item->parent_id, $pemilik_id)){
                array_push($pemilik_id, $item->parent_id);
            }
        }

        $query_hasil = DB::table('siakip2_pengumuman')->whereIn('pemilik', $pemilik_id)
            ->whereRaw('DATE(updated_at) > DATE_SUB(CURDATE(), INTERVAL  7 day)');

        if($request->last_updated_at){
            $query_hasil->where('updated_at', '>', $request->last_updated_at);
        }
        $query_hasil->whereNull('siakip2_pengumuman.deleted_at');
        return $query_hasil->orderBy('updated_at', 'desc')->get();
    }

    public function redirect_func(Request $request)
    {
        return redirect('/beranda');
    }

}
