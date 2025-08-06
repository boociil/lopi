<?php

namespace App\Http\Controllers\Jurnal_Harian;

use App\Http\Controllers\Controller;
use App\Model\Jurnal_Harian\Jurnal_Harian_Item;
use App\Model\Jurnal_Harian\Jurnal_Harian_Proyek;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Peran;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Laporan_Jurnal_Harian_Controller extends Controller
{
    public function master_satuan_kerja_tahun(Request $request)
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
                ->where(function($query) use ($satuan_kerja_tahun_id_arr) {
                    $query->orWhereIn('id', $satuan_kerja_tahun_id_arr);
                    $query->orWhereIn('parent_id', $satuan_kerja_tahun_id_arr);
                })
                ->get();
        }

        return ['satuan_kerja_tahun' => $satuan_kerja_tahun_];
    }

    public function master_satuan_kerja_tahun_for_api(Request $request)
    {
        return [
            'satuan_kerja_tahun' => Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->whereIn('kode_satuan_kerja', session('kode_kab'))
                ->get()
        ];
    }

    public function get_data_hari_daftar(Request $request)
    {
        if($request->filter_search){
            return [
                'user_peran' => DB::table('siakip2_peran')
                    ->leftJoin('siakip2_users', 'siakip2_users.username', '=', 'siakip2_peran.pemilik_username')
                    ->where('nama', 'like', '%'.strtolower($request->filter_search).'%')
                    ->where('parent_id', $request->satuan_kerja_tahun_id)
                    ->whereNotIn('siakip2_users.nip_lama', ['340019239'])
                    ->whereNull('siakip2_peran.deleted_at')
                    ->select('siakip2_users.nama', 'siakip2_users.nip_lama', 'siakip2_users.username')
                    ->orderBy('siakip2_users.nip_lama', 'ASC')
                    ->distinct()
                    ->get(),
                'item' => Jurnal_Harian_Item::where('satuan_kerja_tahun_id', $request->satuan_kerja_tahun_id)->where(function($query) use($request) {
                            $query->orWhere('tanggal_realisasi', $request->tanggal);
                            $query->orWhere('tanggal_deadline', $request->tanggal);
                    }) ->get(),
                'tanggal_hari_ini' =>  Carbon::today()->format('Y-m-d'),
            ];
        }else{
            return [
                'user_peran' => DB::table('siakip2_peran')
                    ->leftJoin('siakip2_users', 'siakip2_users.username', '=', 'siakip2_peran.pemilik_username')
                    ->where('parent_id', $request->satuan_kerja_tahun_id)
                    ->whereNotIn('siakip2_users.nip_lama', ['340019239'])
                    ->whereNull('siakip2_peran.deleted_at')
                    ->select('siakip2_users.nama', 'siakip2_users.nip_lama', 'siakip2_users.username')
                    ->orderBy('siakip2_users.nip_lama', 'ASC')
                    ->distinct()
                    ->get(),
                'item' => Jurnal_Harian_Item::where('satuan_kerja_tahun_id', $request->satuan_kerja_tahun_id)->where(function($query) use($request) {
                            $query->orWhere('tanggal_realisasi', $request->tanggal);
                            $query->orWhere('tanggal_deadline', $request->tanggal);
                    }) ->get(),
                'tanggal_hari_ini' =>  Carbon::today()->format('Y-m-d'),
            ];
        }
    }

    public function get_data_bulan_proyek(Request $request)
    {
        $param_bulan = $request->input('bulan_selected');
        $satuan_kerja_tahun_id = $request->input('satuan_kerja_tahun_id');
        if(! ($param_bulan && $satuan_kerja_tahun_id) ){
            return abort(500, 'Terjadi Kesalahan');
        }

        $user_peran = DB::table('siakip2_peran')
                    ->leftJoin('siakip2_users', 'siakip2_users.username', '=', 'siakip2_peran.pemilik_username')
                    ->where('parent_id', $satuan_kerja_tahun_id)
                    ->whereNotIn('siakip2_users.nip_lama', ['340019239'])
                    ->whereNull('siakip2_peran.deleted_at')
                    ->select('siakip2_users.nama', 'siakip2_users.nip_lama', 'siakip2_users.username')
                    ->orderBy('siakip2_users.nip_lama', 'ASC')
                    ->distinct()
                    ->get();

        $username_arr_dict = [];
        foreach($user_peran as $item){
            $username_arr_dict[$item->username] = $item;
        }

        $proyek_get = Jurnal_Harian_Proyek::where('tahun', session('tahun_active'))
                            ->whereNotNull('tim_id')
                            ->select('tim_id', 'id')
                            ->get();
        $proyek_tim_dict = [];
        $tim_proyek_count = [];
        foreach($proyek_get  as $item_proyek){
            if(!array_key_exists($item_proyek->tim_id, $tim_proyek_count)){
                $tim_proyek_count[$item_proyek->tim_id] = 0;
            }
            $tim_proyek_count[$item_proyek->tim_id] += 1;
            $proyek_tim_dict[$item_proyek->id] = $item_proyek->tim_id;
        }

        // Log::info($tim_proyek_count);
        // Log::info($proyek_tim_dict);

        $data_get_ = Peran::where('tahun', session('tahun_active'))
            ->whereIn('tipe_peran', ['tim', 'proyek'])
            ->select('pemilik_username', 'peran', 'tipe_peran', 'parent_id')
            ->orderByRaw("CASE WHEN tipe_peran = 'tim' THEN 0 ELSE 1 END ASC")
            ->orderByRaw("CASE WHEN peran = 'ketua' THEN 0 ELSE 1 END ASC")
            ->distinct()
            ->get();

        // Log::info($data_get_);

        $user_peran_dict = [];
        //format {'username', 'tim': {'as_ketua_id': [], 'ketua': 0, 'anggotta': 0}, 'proyek': {'ketua_tim': 0, 'ketua': 0, 'anggotta': 0}}
        foreach($data_get_ as $item_get){
            if(!array_key_exists($item_get->pemilik_username, $user_peran_dict)){
                $user_peran_dict[$item_get->pemilik_username] = [
                    'username' => $item_get->pemilik_username
                    , 'tim' => ['as_ketua_id'=> [], 'id'=> [], 'ketua'=> 0, 'anggota'=> 0]
                    , 'proyek'=> ['id'=> [], 'ketua_tim'=> 0, 'ketua'=> 0, 'anggota'=> 0]
                ];
            }

            if($item_get->tipe_peran == 'tim' && $item_get->peran == 'ketua'){
                if(!in_array($item_get->parent_id, $user_peran_dict[$item_get->pemilik_username]['tim']['as_ketua_id'])){
                    array_push($user_peran_dict[$item_get->pemilik_username]['tim']['as_ketua_id'], $item_get->parent_id);
                    array_push($user_peran_dict[$item_get->pemilik_username]['tim']['id'], $item_get->parent_id);
                    $user_peran_dict[$item_get->pemilik_username]['tim']['ketua'] += 1;
                }
            }
            if($item_get->tipe_peran == 'tim' && ($item_get->peran == 'admin' || $item_get->peran == 'anggota')){
                if(!in_array($item_get->parent_id, $user_peran_dict[$item_get->pemilik_username]['tim']['id'])){
                    array_push($user_peran_dict[$item_get->pemilik_username]['tim']['id'], $item_get->parent_id);
                    $user_peran_dict[$item_get->pemilik_username]['tim']['anggota'] += 1;
                }
            }

            if($item_get->tipe_peran == 'proyek'){
                // $skip_because_ketua_tim = false;

                // if(array_key_exists($item_get->parent_id, $proyek_tim_dict)){
                //     if(in_array($proyek_tim_dict[$item_get->parent_id], $user_peran_dict[$item_get->pemilik_username]['tim']['as_ketua_id'])){
                //         $skip_because_ketua_tim = true;
                //     }
                // }

                // if(!$skip_because_ketua_tim){
                    if($item_get->tipe_peran == 'proyek' && $item_get->peran == 'ketua'){
                        if(!in_array($item_get->parent_id, $user_peran_dict[$item_get->pemilik_username]['proyek']['id'])){
                            array_push($user_peran_dict[$item_get->pemilik_username]['proyek']['id'], $item_get->parent_id);
                            $user_peran_dict[$item_get->pemilik_username]['proyek']['ketua'] += 1;
                        }
                    }
                    if($item_get->tipe_peran == 'proyek' && ($item_get->peran == 'admin' || $item_get->peran == 'anggota')){
                        if(!in_array($item_get->parent_id, $user_peran_dict[$item_get->pemilik_username]['proyek']['id'])){
                            array_push($user_peran_dict[$item_get->pemilik_username]['proyek']['id'], $item_get->parent_id);
                            $user_peran_dict[$item_get->pemilik_username]['proyek']['anggota'] += 1;
                        }
                    }
                // }
            }
        }

        // Log::info($user_peran_dict);

        $user_peran = [];
        // foreach($user_peran_dict as $key => $value){
        //     if(array_key_exists($key, $username_arr_dict)){
        //         $value__ = $value;
        //         foreach($value__['tim']['as_ketua_id'] as $item_id_as_ketua_tim){
        //             if(array_key_exists($item_id_as_ketua_tim, $tim_proyek_count)){
        //                 $value__['proyek']['ketua_tim'] += $tim_proyek_count[$item_id_as_ketua_tim];
        //             }
        //         }
        //         $value__['user_data'] = $username_arr_dict[$key];
        //         array_push($user_peran, $value__);
        //     }
        // }

        foreach($username_arr_dict as $key => $value){
            if(array_key_exists($key, $user_peran_dict)){
                $value__ = $user_peran_dict[$key];
                foreach($value__['tim']['as_ketua_id'] as $item_id_as_ketua_tim){
                    if(array_key_exists($item_id_as_ketua_tim, $tim_proyek_count)){
                        $value__['proyek']['ketua_tim'] += $tim_proyek_count[$item_id_as_ketua_tim];
                    }
                }
                $value__['user_data'] = $value;
                array_push($user_peran, $value__);
            }else{
                array_push($user_peran,
                    [
                        'user_data' => $value
                        , 'username' => $key
                        , 'tim' => ['as_ketua_id'=> [], 'id'=> [], 'ketua'=> 0, 'anggota'=> 0]
                        , 'proyek'=> ['id'=> [], 'ketua_tim'=> 0, 'ketua'=> 0, 'anggota'=> 0]
                    ]
                );
            }
        }

        return  $user_peran;
    }

    public function get_data_bulan_kegiatan(Request $request)
    {
        $param_bulan = $request->input('bulan_selected');
        $satuan_kerja_tahun_id = $request->input('satuan_kerja_tahun_id');
        if(! ($param_bulan && $satuan_kerja_tahun_id) ){
            return abort(500, 'Terjadi Kesalahan');
        }

        $user_peran = DB::table('siakip2_peran')
                    ->leftJoin('siakip2_users', 'siakip2_users.username', '=', 'siakip2_peran.pemilik_username')
                    ->where('parent_id', $satuan_kerja_tahun_id)
                    ->whereNotIn('siakip2_users.nip_lama', ['340019239'])
                    ->whereNull('siakip2_peran.deleted_at')
                    ->select('siakip2_users.nama', 'siakip2_users.nip_lama', 'siakip2_users.username')
                    ->orderBy('siakip2_users.nip_lama', 'ASC')
                    ->distinct()
                    ->get();

        $username_arr_dict = [];
        foreach($user_peran as $item){
            $username_arr_dict[$item->username] = $item;
        }

        $data_get_ = Jurnal_Harian_Item::whereMonth('tanggal_deadline', $param_bulan)
            ->where('tahun', session('tahun_active'))
            ->select('pelaksana_username', 'tanggal_realisasi', DB::raw('count(*) as total'))
            ->groupBy('pelaksana_username', 'tanggal_realisasi')
            ->get();

        $return_data_dict = [];
        // {'jumlah_tanggal': 0, tanggal: {'01-01-19901': 0}, 'jumlah': 0, 'rata_rata': 0}
        foreach($data_get_ as $item_get){
            if(!array_key_exists($item_get->pelaksana_username, $return_data_dict)){
                $return_data_dict[$item_get->pelaksana_username] = ['jumlah_tanggal' => 0, 'tanggal' => [], 'jumlah' => 0, 'rata_rata' => 0];
            }
            if(!array_key_exists($item_get->tanggal_realisasi, $return_data_dict[$item_get->pelaksana_username]['tanggal'])){
                $return_data_dict[$item_get->pelaksana_username]['tanggal'][$item_get->tanggal_realisasi] = 0;
                $return_data_dict[$item_get->pelaksana_username]['jumlah_tanggal'] += 1;
            }
            $return_data_dict[$item_get->pelaksana_username]['tanggal'][$item_get->tanggal_realisasi] += intval($item_get->total);
            $return_data_dict[$item_get->pelaksana_username]['jumlah'] += intval($item_get->total);
        }

        $return_data = [];
        // foreach($return_data_dict as $key => $value){
        //     if(array_key_exists($key, $username_arr_dict)){
        //         $value__ = $value;
        //         $value__['user_data'] = $username_arr_dict[$key];
        //         $value__['rata_rata'] = round(($value__['jumlah'] / $value__['jumlah_tanggal']), 2);
        //         array_push($return_data, $value__);
        //     }
        // }
        foreach($username_arr_dict as $key => $value){
            if(array_key_exists($key, $return_data_dict)){
                $value__ = $return_data_dict[$key];
                $value__['user_data'] = $value;
                $value__['rata_rata'] = round(($value__['jumlah'] / $value__['jumlah_tanggal']), 2);
                array_push($return_data, $value__);
            }else{
                array_push($return_data,
                    [
                        'user_data' => $value,
                        'jumlah_tanggal' => 0,
                        'tanggal' => [],
                        'jumlah' => 0,
                        'rata_rata' => 0
                    ]
                );
            }
        }

        return $return_data;
    }

    public function get_data_bulan_indeks(Request $request)
    {
        $param_bulan = $request->input('bulan_selected');
        $satuan_kerja_tahun_id = $request->input('satuan_kerja_tahun_id');
        if(! ($param_bulan && $satuan_kerja_tahun_id) ){
            return abort(500, 'Terjadi Kesalahan');
        }

        $user_peran = DB::table('siakip2_peran')
                    ->leftJoin('siakip2_users', 'siakip2_users.username', '=', 'siakip2_peran.pemilik_username')
                    ->where('parent_id', $satuan_kerja_tahun_id)
                    ->whereNotIn('siakip2_users.nip_lama', ['340019239'])
                    ->whereNull('siakip2_peran.deleted_at')
                    ->select('siakip2_users.nama', 'siakip2_users.nip_lama', 'siakip2_users.username')
                    ->orderBy('siakip2_users.nip_lama', 'ASC')
                    ->distinct()
                    ->get();

        $username_arr_dict = [];
        foreach($user_peran as $item){
            $username_arr_dict[$item->username] = $item;
        }

        $data_get_ = Jurnal_Harian_Item::where(function($query0) use ($param_bulan){
                $query0->orWhereMonth('tanggal_deadline', $param_bulan);
                $query0->orWhereMonth('tanggal_realisasi', $param_bulan);
            })
            ->where('tahun', session('tahun_active'))
            ->select('pelaksana_username', DB::raw('count(*) as count_'), DB::raw('sum(nilai) as sum_nilai_'))
            ->groupBy('pelaksana_username')
            ->get();

        $return_data = [];
        // foreach($data_get_  as $item){
        //     if(array_key_exists($item->pelaksana_username, $username_arr_dict)){
        //         $value__ = $item;
        //         $value__['user_data'] = $username_arr_dict[$item->pelaksana_username];
        //         $value__['rata_rata'] = round(($value__['sum_nilai_'] / $value__['count_']), 2);
        //         $value__['indeks'] = floor($value__['rata_rata'] *4) / 100;
        //         array_push($return_data, $value__);
        //     }
        // }

        foreach($username_arr_dict as $key => $value){
            $value__ = [
                'user_data' => $value,
                'pelaksana_username' => $key,
                'sum_nilai_' => 0,
                'count_' => 0,
                'rata_rata' => 0,
                'indeks' => 0
            ];
            foreach($data_get_ as $item){
                if($item->pelaksana_username == $key){
                    $value__['sum_nilai_'] = $item->sum_nilai_;
                    $value__['count_'] = $item->count_;
                    $value__['rata_rata'] = floor($value__['sum_nilai_'] / $value__['count_'] * 100) / 100;
                    $value__['indeks'] = floor($value__['sum_nilai_'] / $value__['count_'] * 4) / 100;
                    break;
                }
            }
            array_push($return_data, $value__);
        }

        return $return_data;
    }



}
