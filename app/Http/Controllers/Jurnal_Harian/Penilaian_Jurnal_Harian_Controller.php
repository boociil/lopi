<?php

namespace App\Http\Controllers\Jurnal_Harian;

use App\Http\Controllers\Controller;
use App\Model\Jurnal_Harian\Jurnal_Harian_Item;
use App\Model\Jurnal_Harian\Jurnal_Harian_Proyek;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class Penilaian_Jurnal_Harian_Controller extends Controller
{

    public static function get_data_can_be_nilai($satuan_kerja_tahun_id, $bulan_selected, $id_arr = [], $status_pekerjaan = [], $status_nilai = [])
    {
        if(count($id_arr) > 30){
            $id_arr = [];
        }

        $satuan_kerja_tahun_prov_arr = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))->where('type', 'prov')->get();
        $satuan_kerja_tahun_id_prov_arr = collect($satuan_kerja_tahun_prov_arr)->pluck('id')->toArray();
        $satuan_kerja_tahun_id_prov_pimpinan_arr = [];

        $peran_ku_as_pim_or_ketua_user = Peran::where('tahun', session('tahun_active'))
            // ->where('pemilik_username', Auth::user()->username)
            ->whereIn('peran', ['pimpinan', 'ketua'])
            ->select('tipe_peran','parent_id', 'pemilik_username')
            ->get();

        $id_satuan_kerja_tahun_ku_as_pimpinan = [];
        $id_tim_ku_as_ketua = [];
        $id_proyek_ku_as_ketua = [];
        $dict_id_username_pimpinan_pj_ketua = [];

        foreach($peran_ku_as_pim_or_ketua_user as $item_peran){
            if($item_peran->pemilik_username == Auth::user()->username){
                if($item_peran->tipe_peran == 'satuan_kerja_tahun'){
                    array_push($id_satuan_kerja_tahun_ku_as_pimpinan, $item_peran->parent_id);
                    if(in_array($item_peran->parent_id,  $satuan_kerja_tahun_id_prov_arr)){
                        array_push($satuan_kerja_tahun_id_prov_pimpinan_arr, $item_peran->parent_id);
                    }
                }else if($item_peran->tipe_peran == 'tim'){
                    array_push($id_tim_ku_as_ketua, $item_peran->parent_id);
                }else if($item_peran->tipe_peran == 'proyek'){
                    array_push($id_proyek_ku_as_ketua, $item_peran->parent_id);
                }
            }
            $dict_id_username_pimpinan_pj_ketua[$item_peran->parent_id] = $item_peran->pemilik_username;
        }

        if( (count($id_satuan_kerja_tahun_ku_as_pimpinan) + count($id_tim_ku_as_ketua) + count($id_proyek_ku_as_ketua)) == 0) {
            return abort(403, "Tidak memili akses terhadap fitur ini");
        }

        $return_data_id = [];
        $return_data = [];

        #utama dan tambahan pimpinan satuan kerja tahun
        if(count($id_satuan_kerja_tahun_ku_as_pimpinan) > 0){
            $tim_in_satuan_kerja = Tim::whereIn('satuan_kerja_tahun_id', $id_satuan_kerja_tahun_ku_as_pimpinan)
                                ->select('id', 'satuan_kerja_tahun_id')
                                ->get();

            $query_item = Jurnal_Harian_Item::query();
            $query_item->where('tahun', session('tahun_active'));
            $query_item->whereMonth('tanggal_realisasi', $bulan_selected);
            $query_item->whereIn('satuan_kerja_tahun_id', $id_satuan_kerja_tahun_ku_as_pimpinan);
            if(count($status_nilai) == 0){
                $query_item->whereIn('status_nilai', [1,2,3]);
            }else{
                $query_item->whereIn('status_nilai', $status_nilai);
            }


            if(count($satuan_kerja_tahun_id_prov_pimpinan_arr) > 0 ){
                $query_item->where(function ($query0) use ($tim_in_satuan_kerja, $dict_id_username_pimpinan_pj_ketua) {
                    $query0->orWhere(function ($query1) use ($tim_in_satuan_kerja, $dict_id_username_pimpinan_pj_ketua) {
                        $query1->whereNotNull('jurnal_harian_proyek_id');
                        $query1->where(function ($query2) use ($tim_in_satuan_kerja, $dict_id_username_pimpinan_pj_ketua) {
                            $query2->orwhere('pelaksana_username', Auth::user()->username);
                            foreach($tim_in_satuan_kerja as $item_tim){
                                if(array_key_exists($item_tim->id, $dict_id_username_pimpinan_pj_ketua)){
                                    $query2->orwhere(function ($query3) use ($item_tim, $dict_id_username_pimpinan_pj_ketua) {
                                        $query3->where('tim_id', $item_tim->id);
                                        $query3->where('pelaksana_username', $dict_id_username_pimpinan_pj_ketua[$item_tim->id]);
                                    });
                                }
                            }
                        });
                    });
                    $query0->orWhere('type', 4);
                });
            }else if(count($satuan_kerja_tahun_id_prov_pimpinan_arr) == 0 ){
                $query_item->where('pelaksana_username', '!=', Auth::user()->username);

                $query_item->where(function ($query0) use ($tim_in_satuan_kerja, $dict_id_username_pimpinan_pj_ketua) {
                    $query0->orWhere(function ($query1) use ($tim_in_satuan_kerja, $dict_id_username_pimpinan_pj_ketua) {
                        $query1->whereNotNull('jurnal_harian_proyek_id');
                        $query1->where(function ($query2) use ($tim_in_satuan_kerja, $dict_id_username_pimpinan_pj_ketua) {
                            foreach($tim_in_satuan_kerja as $item_tim){
                                if(array_key_exists($item_tim->id, $dict_id_username_pimpinan_pj_ketua)){
                                    $query2->orwhere(function ($query3) use ($item_tim, $dict_id_username_pimpinan_pj_ketua) {
                                        $query3->where('tim_id', $item_tim->id);
                                        $query3->where('pelaksana_username', $dict_id_username_pimpinan_pj_ketua[$item_tim->id]);
                                    });
                                }
                            }
                        });
                    });
                    $query0->orWhere('type', 4);
                });
            }



            if(count($id_arr) > 0){
                $query_item->whereIn('id', $id_arr);
            }
            $query_data = $query_item->get();
            foreach($query_data as $item_data){
                if(is_null($satuan_kerja_tahun_id) || $item_data->satuan_kerja_tahun_id == $satuan_kerja_tahun_id){
                    array_push($return_data, $item_data);
                    array_push($return_data_id, $item_data->id);
                }
            }

            if(count($satuan_kerja_tahun_id_prov_pimpinan_arr) > 0 ){
                $satuan_kerja_tahun_prov_child_id = collect(Satuan_Kerja_Tahun::whereIn('parent_id', $satuan_kerja_tahun_id_prov_pimpinan_arr)->get())->pluck('id')->toArray();
                $satuan_kerja_tahun_prov_child_pimpinan_username = collect(Peran::where('tahun', session('tahun_active'))
                    ->where('tipe_peran', 'satuan_kerja_tahun')
                    ->whereIn('peran', ['pimpinan'])
                    ->whereIn('parent_id', $satuan_kerja_tahun_prov_child_id)
                    ->select('tipe_peran','parent_id', 'pemilik_username')
                    ->get())->pluck('pemilik_username')->toArray();

                $query_item_kepala_kabupaten = Jurnal_Harian_Item::whereIn('pelaksana_username', $satuan_kerja_tahun_prov_child_pimpinan_username)
                            ->where('tahun', session('tahun_active'))
                            ->whereMonth('tanggal_realisasi', $bulan_selected)
                            ->get();

                foreach($query_item_kepala_kabupaten as $item_data){
                    array_push($return_data, $item_data);
                    array_push($return_data_id, $item_data->id);
                }
            }
        }

        #utama dan tambahan ketua tim
        if(count($id_tim_ku_as_ketua) > 0){
            $proyek_in_tim = Jurnal_Harian_Proyek::whereIn('tim_id', $id_tim_ku_as_ketua)
                        ->select('id', 'tim_id', 'satuan_kerja_tahun_id')
                        ->get();


            $query_item = Jurnal_Harian_Item::query();
            $query_item->where('tahun', session('tahun_active'));
            $query_item->whereMonth('tanggal_realisasi', $bulan_selected);
            $query_item->whereIn('tim_id', $id_tim_ku_as_ketua);
            if(count($status_nilai) == 0){
                $query_item->whereIn('status_nilai', [1,2,3]);
            }else{
                $query_item->whereIn('status_nilai', $status_nilai);
            }
            $query_item->where('pelaksana_username', '!=', Auth::user()->username);

            $query_item->where(function ($query0) use ($proyek_in_tim, $dict_id_username_pimpinan_pj_ketua) {
                $query0->orWhere(function ($query1) use ($proyek_in_tim, $dict_id_username_pimpinan_pj_ketua) {
                    $query1->whereNotNull('jurnal_harian_proyek_id');
                    $query1->where(function ($query2) use ($proyek_in_tim, $dict_id_username_pimpinan_pj_ketua) {
                        foreach($proyek_in_tim as $item_proyek){
                            if(array_key_exists($item_proyek->id, $dict_id_username_pimpinan_pj_ketua)){
                                $query2->orwhere(function ($query3) use ($item_proyek, $dict_id_username_pimpinan_pj_ketua) {
                                    $query3->where('jurnal_harian_proyek_id', $item_proyek->id);
                                    $query3->where('pelaksana_username', $dict_id_username_pimpinan_pj_ketua[$item_proyek->id]);
                                });
                            }
                        }
                    });
                });
                $query0->orWhere('type', 4);
            });


            if(count($id_arr) > 0){
                $query_item->whereIn('id', $id_arr);
            }
            $query_data = $query_item->get();
            foreach($query_data as $item_data){
                if(array_key_exists($item_data->satuan_kerja_tahun_id, $dict_id_username_pimpinan_pj_ketua)){
                    if($dict_id_username_pimpinan_pj_ketua[$item_data->satuan_kerja_tahun_id] == $item_data->pelaksana_username){
                        continue;
                    }
                }
                if(array_key_exists($item_data->tim_id, $dict_id_username_pimpinan_pj_ketua)){
                    if($dict_id_username_pimpinan_pj_ketua[$item_data->tim_id] == $item_data->pelaksana_username){
                        continue;
                    }
                }

                if(in_array($item_data->id, $return_data_id)){
                    continue;
                }
                if(is_null($satuan_kerja_tahun_id) || $item_data->satuan_kerja_tahun_id == $satuan_kerja_tahun_id){
                    array_push($return_data, $item_data);
                    array_push($return_data_id, $item_data->id);
                }
            }
        }



        if(count($id_proyek_ku_as_ketua) > 0){
            $query_item = Jurnal_Harian_Item::query();
            $query_item->where('tahun', session('tahun_active'));
            $query_item->whereMonth('tanggal_realisasi', $bulan_selected);
            $query_item->whereIn('jurnal_harian_proyek_id', $id_proyek_ku_as_ketua);
            if(count($status_nilai) == 0){
                $query_item->whereIn('status_nilai', [1,2,3]);
            }else{
                $query_item->whereIn('status_nilai', $status_nilai);
            }
            $query_item->where('pelaksana_username', '!=', Auth::user()->username);
            if(count($id_arr) > 0){
                $query_item->whereIn('id', $id_arr);
            }
            $query_data = $query_item->get();

            foreach($query_data as $item_data){
                if(array_key_exists($item_data->satuan_kerja_tahun_id, $dict_id_username_pimpinan_pj_ketua)){
                    if($dict_id_username_pimpinan_pj_ketua[$item_data->satuan_kerja_tahun_id] == $item_data->pelaksana_username){
                        continue;
                    }
                }
                if(array_key_exists($item_data->tim_id, $dict_id_username_pimpinan_pj_ketua)){
                    if($dict_id_username_pimpinan_pj_ketua[$item_data->tim_id] == $item_data->pelaksana_username){
                        continue;
                    }
                }
                if(array_key_exists($item_data->jurnal_harian_proyek_id, $dict_id_username_pimpinan_pj_ketua)){
                    if($dict_id_username_pimpinan_pj_ketua[$item_data->jurnal_harian_proyek_id] == $item_data->pelaksana_username){
                        continue;
                    }
                }
                if(in_array($item_data->id, $return_data_id)){
                    continue;
                }
                if(is_null($satuan_kerja_tahun_id) || $item_data->satuan_kerja_tahun_id == $satuan_kerja_tahun_id){
                    array_push($return_data, $item_data);
                    array_push($return_data_id, $item_data->id);
                }
            }
        }

        return $return_data;
    }

    public function master(Request $request)
    {
        $peran_as_pimpinan_ketua_parent_id = collect(Peran::where('tahun', session('tahun_active'))
                ->whereIn('peran', ['pimpinan', 'ketua', 'anggota'])
                ->where('pemilik_username', Auth::user()->username)
                ->where('tipe_peran', 'satuan_kerja_tahun')
                ->get()
            )
            ->pluck('parent_id');

        return Satuan_Kerja_Tahun::whereIn('id', $peran_as_pimpinan_ketua_parent_id)->get();

    }

    public function item_daftar(Request $request)
    {
        if(!($request->input('satuan_kerja_tahun_id') && $request->input('bulan_selected'))){
            return abort(400, "Kekurangan Parameter");
        }
        return Penilaian_Jurnal_Harian_Controller::get_data_can_be_nilai($request->input('satuan_kerja_tahun_id'), $request->input('bulan_selected'), [], [], []);
    }

    public function item_nilai(Request $request)
    {
        DB::beginTransaction();
        try
        {
            if(!($request->input('satuan_kerja_tahun_id') && $request->input('bulan_selected'))){
                return abort(400, "Kekurangan Parameter");
            }

            $item_nilai_format = [];
            $item_nilai_format_id = [];
            foreach($request->input('nilai') as $item_nilai){
                $item_nilai_format[$item_nilai['id']] = ['status_nilai' => $item_nilai['status_nilai'], 'nilai_kualitas' => $item_nilai['nilai_kualitas']];
                array_push($item_nilai_format_id, $item_nilai['id']);
            }

            $item_bisa_dinilai_arr = Penilaian_Jurnal_Harian_Controller::get_data_can_be_nilai($request->input('satuan_kerja_tahun_id'), $request->input('bulan_selected'), $item_nilai_format_id, [], []);

            foreach($item_bisa_dinilai_arr as $item){
                if(array_key_exists($item->id, $item_nilai_format)){
                    $item->status_nilai = $item_nilai_format[$item->id]['status_nilai'];
                    if($item->status_nilai == 3){
                        $item->nilai_kualitas = $item_nilai_format[$item->id]['nilai_kualitas'];
                    }else{
                        $item->nilai_kualitas = null;
                    }
                    $item->save();
                    $item->reload_nilai();
                }
            }

            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

}
