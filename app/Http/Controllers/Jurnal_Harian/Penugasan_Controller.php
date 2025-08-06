<?php

namespace App\Http\Controllers\Jurnal_Harian;

use App\Http\Controllers\Controller;

use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use App\Model\Jurnal_Harian\Jurnal_Harian_Item;
use App\Model\Jurnal_Harian\Jurnal_Harian_Proyek;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class Penugasan_Controller extends Controller
{

    public function master_daftar(Request $request)
    {
        $peran_super_admin = Auth::user()->peran_super_admin;
        if(count($peran_super_admin) > 0){
            $satuan_kerja_tahun = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))->get();
            $tim = Tim::where('tahun', session('tahun_active'))
                        ->get();
            $proyek = Jurnal_Harian_Proyek::where('tahun', session('tahun_active'))->get();

            return [
                'satuan_kerja_tahun' => $satuan_kerja_tahun,
                'tim' => $tim,
                'proyek' => $proyek
            ];
        }else{
            $parent_id_arr_before_pluck = Peran::where('pemilik_username', Auth::user()->username)
                ->where(function ($query){
                    $query->where(function ($query2){
                        $query2->where('tipe_peran', 'satuan_kerja_tahun');
                        $query2->whereIn('peran', ['pimpinan', 'admin']);
                    });
                    $query->orWhere(function ($query2){
                        $query2->where('tipe_peran', 'tim');
                        $query2->whereIn('peran', ['ketua', 'admin']);
                    });
                    $query->orWhere(function ($query2){
                        $query2->where('tipe_peran', 'proyek');
                        $query2->whereIn('peran', ['ketua', 'admin', 'anggota']);
                    });
                })
                ->where('tahun', session('tahun_active'))
                ->select('tipe_peran', 'parent_id')
                ->distinct()
                ->get();

            $parent_id_satuan_kerja_tahun = [];
            $parent_id_tim = [];
            $parent_id_proyek = [];

            foreach($parent_id_arr_before_pluck as $item){
                if($item->tipe_peran == 'satuan_kerja_tahun'){
                    array_push($parent_id_satuan_kerja_tahun, $item->parent_id);
                }else if($item->tipe_peran == 'tim'){
                    array_push($parent_id_tim, $item->parent_id);
                }else if($item->tipe_peran == 'proyek'){
                    array_push($parent_id_proyek, $item->parent_id);
                }
            }

            $satuan_kerja_tahun_child_id = collect(Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                    ->whereIn('parent_id', $parent_id_satuan_kerja_tahun)
                    ->get())
                    ->pluck('id')->toArray();

            $parent_id_satuan_kerja_tahun = array_merge($parent_id_satuan_kerja_tahun, $satuan_kerja_tahun_child_id);

            $proyek = Jurnal_Harian_Proyek::where('tahun', session('tahun_active'))
                ->where(function ($query) use ($parent_id_satuan_kerja_tahun, $parent_id_tim, $parent_id_proyek){
                    $query->orWhereIn('id', $parent_id_proyek);
                    $query->orWhereIn('tim_id', $parent_id_tim);
                    $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_satuan_kerja_tahun);
                })
                ->get();

            $satuan_kerja_tahun_id_from_proyek = [];
            $tim_id_from_proyek = [];

            foreach($proyek as $item){
                array_push($satuan_kerja_tahun_id_from_proyek, $item->satuan_kerja_tahun_id);
                array_push($tim_id_from_proyek, $item->tim_id);
            }

            $satuan_kerja_tahun = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                        ->where(function($query) use ($parent_id_satuan_kerja_tahun, $satuan_kerja_tahun_id_from_proyek){
                            $query->orWhereIn('id', $parent_id_satuan_kerja_tahun);
                            $query->orWhereIn('id', $satuan_kerja_tahun_id_from_proyek);
                        })
                        ->get();

            $tim = Tim::where('tahun', session('tahun_active'))
                        ->where(function ($query) use ($parent_id_satuan_kerja_tahun, $satuan_kerja_tahun_id_from_proyek, $parent_id_tim, $tim_id_from_proyek){
                            $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_satuan_kerja_tahun);
                            $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_id_from_proyek);
                            $query->orWhereIn('id', $parent_id_tim);
                            $query->orWhereIn('id', $tim_id_from_proyek);

                        })
                        ->get();

            return [
                'satuan_kerja_tahun' => $satuan_kerja_tahun,
                'tim' => $tim,
                'proyek' => $proyek
            ];
        }
    }

    public function daftar(Request $request)
    {
        $param_username = Auth::user()->username;
        $param_tangal = $request->input('tanggal');
        if(!$param_tangal){
            abort(400, 'Parameter tanggal harus terisi');
        }

        $query_item_sesuai_tanggal = Jurnal_Harian_Item::query();
        $query_item_sesuai_tanggal->where('tahun', session('tahun_active'));
        $query_item_sesuai_tanggal->where('pelaksana_username', $param_username);
        $query_item_sesuai_tanggal->where(function ($query) use ($param_tangal) {
            $query->orWhere('tanggal_deadline', $param_tangal);
            $query->orWhere('tanggal_realisasi', $param_tangal);
        });
        $item_sesuai_tanggal = $query_item_sesuai_tanggal->get();

        $id_tambahan = [];
        foreach($item_sesuai_tanggal as $item){
            if(in_array($item->type, ['1', '2', '3'])){
                array_push($id_tambahan, $item->id);
            }else if(!is_null($item->parent_id)){
                array_push($id_tambahan, $item->parent_id);
            }
        }

        $query_item_belum_on_progress = Jurnal_Harian_Item::query();
        $query_item_belum_on_progress->where('tahun', session('tahun_active'));
        $query_item_belum_on_progress->where('pelaksana_username', $param_username);
        // $query_item_belum_on_progress->whereIn('type', ['1', '2', '3']);


        $query_item_belum_on_progress->where(function ($query0) use ($id_tambahan, $param_tangal){
            $query0->orWhereIn('id', $id_tambahan);
            $query0->orWhere(function ($query2) {
                $query2->WhereIn('status_pekerjaan', [0,1]);
                $query2->whereIn('type', [1,2,3]);
            });
        });

        $item_belum_on_progress = $query_item_belum_on_progress->get();

        return [
            'item_sesuai_tanggal' => $item_sesuai_tanggal,
            'item_belum_on_progress' => $item_belum_on_progress,
        ];

    }

    public function daftar_penugasan(Request $request)
    {
        $param_username = Auth::user()->username;
        $query_item = Jurnal_Harian_Item::query();
        $query_item->where('tahun', session('tahun_active'));
        $query_item->where('pelaksana_username', $param_username);
        $query_item->whereIn('type', [1, 2, 3]);

        // $query_item->orderBy('tanggal_realisasi', 'desc');
        // $query_item->orderBy('tanggal_deadline', 'desc');
        $query_item->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END DESC');

        if(!$request->limit || !$request->page ){
            $query_item_array = $query_item->clone();
            $query_item_count = $query_item->clone();

            return ['daftar' => $query_item_array->get(), 'total' => $query_item_count->count()];
        }else{
            $search_term = $request->search_term;
            $selected_bulan = $request->selected_bulan;
            $selected_status = $request->selected_status;

            if($search_term){
                $query_item->where(function ($query) use ($search_term){
                    $query->orWhere('nama', 'LIKE', '%'.$search_term.'%');
                    $query->orWhereHas('pelaksana_user_nama', function ($query1)  use ($search_term){
                        $query1->where('nama', 'LIKE', '%'.$search_term.'%');
                    });
                });
            }
            if($selected_bulan>=1 && $selected_bulan<=12){
                $query_item->where(function ($query) use ($selected_bulan){
                    $query->orWhereMonth('tanggal_deadline', $selected_bulan);
                    $query->orWhereMonth('tanggal_realisasi', $selected_bulan);
                });
            }

            if(in_array($selected_status, ['1','2','3'])){
                $query_item->where('status_pekerjaan', intval($selected_status)-1);
            }
            if(in_array($selected_status, ['4','5','6'])){
                if($selected_status == 4){
                    $query_item->whereIn('status_nilai', ['0','1']);
                }else if($selected_status == 5){
                    $query_item->where('status_nilai', '2');
                }else if($selected_status == 6){
                    $query_item->where('status_nilai', '3');
                }
            }

            $query_item_array = $query_item->clone();
            $query_item_count = $query_item->clone();

            $query_item_array->offset(($request->page - 1)*$request->limit);
            $query_item_array->limit($request->limit);

            return ['daftar' => $query_item_array->get(), 'total' => $query_item_count->count()];
        }
    }

    public function daftar_penugasan_total(Request $request)
    {
        $param_username = Auth::user()->username;
        $query_item_count = Jurnal_Harian_Item::query();
        $query_item_count->where('tahun', session('tahun_active'));
        $query_item_count->where('pelaksana_username', $param_username);
        $query_item_count->whereIn('type', [1, 2, 3]);

        $query_item_count->orderBy('tanggal_realisasi', 'desc');
        $query_item_count->orderBy('tanggal_deadline', 'desc');
        return $query_item_count->count();
    }

    public function item_tambah(Request $request)
    {
        if(in_array( $request->input('status_pekerjaan'), ['1','2'] ) && $request->input('tanggal_realisasi')){
            if(\Carbon\Carbon::parse($request->input('tanggal_realisasi')) > \Carbon\Carbon::today()){
                throw new Exception("Tanggal realisasi harus sebelum hari ini");
            }
        }
        if($request->input('target') == 0){
            throw new Exception("Target tidak boleh kosong");
        }

        DB::beginTransaction();
        try
        {
            if(in_array($request->input('other')['state_tambah'], ['1', '2', '3'])){
                $parent_id_arr = collect(Peran::where('pemilik_username', Auth::user()->username)
                    ->where(function ($query){
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'tim');
                            $query2->where('peran', 'ketua');
                        });
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'proyek');
                            $query2->whereIn('peran', ['ketua', 'admin', 'anggota']);
                        });
                    })
                    ->where('tahun', session('tahun_active'))
                    ->select('parent_id')
                    ->distinct()
                    ->get()
                )->pluck('parent_id')->toArray();

                $jurnal_harian_proyek_id = $request->input('other')['proyek_id'];
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where(function ($query) use ($parent_id_arr){
                        $query->whereIn('id', $parent_id_arr);
                        $query->orWhereIn('tim_id', $parent_id_arr);
                        $query->orWhereIn('fungsi_id', $parent_id_arr);
                        $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_arr);
                    })
                    ->where(function ($query) use ($request){
                        $query->where('tim_id', $request->input('other')['pemilik_tugas']['id']);
                        $query->orWhere('fungsi_id', $request->input('other')['pemilik_tugas']['id']);
                        $query->orWhere('satuan_kerja_tahun_id', $request->input('other')['pemilik_tugas']['id']);
                    })
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                if($request->input('other')['state_tambah'] == '1'){
                    $baru = Jurnal_Harian_Item::helper_tambah_personal($jurnal_harian_proyek, $request, []);
                    DB::commit();
                    return $baru;
                }else if($request->input('other')['state_tambah'] == '2'){
                    $baru = Jurnal_Harian_Item::helper_tambah_personal($jurnal_harian_proyek, $request, []);
                    DB::commit();
                    return $baru;
                }else if($request->input('other')['state_tambah'] == '3'){
                    $baru = Jurnal_Harian_Item::helper_tambah_personal($jurnal_harian_proyek, $request, []);
                    DB::commit();
                    return $baru;
                }
            }else if($request->input('other')['state_tambah'] == 4){
                $tambahan_data = ['satuan_kerja_tahun_id' => null, 'fungsi_id' => null, 'tim_id' => null];
                if($request->input('other')['pemilik_tugas']['type'] == 'satuan_kerja_tahun'){
                    if( Peran::check_user_have_role('satuan_kerja_tahun', $request->input('other')['pemilik_tugas']['id']
                        , Auth::user()->username, ['pimpinan', 'admin', 'anggota'])){
                            $tambahan_data['satuan_kerja_tahun_id'] = $request->input('other')['pemilik_tugas']['id'];
                    }else{
                        return abort(400, 'Kesalahan Input');
                    }
                }else if($request->input('other')['pemilik_tugas']['type'] == 'fungsi'){
                    if( Peran::check_user_have_role('fungsi', $request->input('other')['pemilik_tugas']['id']
                        , Auth::user()->username, ['pj'])){
                            $tambahan_data['satuan_kerja_tahun_id'] = $request->input('other')['pemilik_tugas']['satuan_kerja_tahun_id'];
                            $tambahan_data['fungsi_id'] = $request->input('other')['pemilik_tugas']['id'];
                    }else{
                        return abort(400, 'Kesalahan Input');
                    }
                }else if($request->input('other')['pemilik_tugas']['type'] == 'tim'){
                    if( Peran::check_user_have_role('tim', $request->input('other')['pemilik_tugas']['id']
                        , Auth::user()->username, ['ketua', 'admin', 'anggota'])){
                            $tambahan_data['satuan_kerja_tahun_id'] = $request->input('other')['pemilik_tugas']['satuan_kerja_tahun_id'];
                            $tambahan_data['fungsi_id'] = $request->input('other')['pemilik_tugas']['fungsi_id'];
                            $tambahan_data['tim_id'] = $request->input('other')['pemilik_tugas']['id'];
                    }else{
                        return abort(400, 'Kesalahan Input');
                    }
                }
                $baru = Jurnal_Harian_Item::helper_tambah_personal(null, $request, $tambahan_data);
                DB::commit();
                return $baru;
            }
            DB::rollBack();
            return abort(400, 'Kesalahan Input');
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function item_ubah(Request $request)
    {
        if(in_array( $request->input('status_pekerjaan'), ['1','2'] ) && $request->input('tanggal_realisasi')){
            if(\Carbon\Carbon::parse($request->input('tanggal_realisasi')) > \Carbon\Carbon::today()){
                throw new Exception("Tanggal realisasi harus sebelum hari ini");
            }
        }
        if($request->input('target') == 0){
            throw new Exception("Target tidak boleh kosong");
        }
        DB::beginTransaction();
        try
        {
            $jurnal_harian_item = null;
            if(in_array($request->input('other')['state_tambah'], ['1', '2', '3'])){
                $parent_id_arr = collect(Peran::where('pemilik_username', Auth::user()->username)
                    ->where(function ($query){
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'tim');
                            $query2->where('peran', 'ketua');
                        });
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'proyek');
                            $query2->whereIn('peran', ['ketua', 'admin', 'anggota']);
                        });
                    })
                    ->where('tahun', session('tahun_active'))
                    ->select('parent_id')
                    ->distinct()
                    ->get()
                )->pluck('parent_id')->toArray();

                $jurnal_harian_proyek_id = $request->input('other')['proyek_id'];
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where(function ($query) use ($parent_id_arr){
                        $query->whereIn('id', $parent_id_arr);
                        $query->orWhereIn('tim_id', $parent_id_arr);
                        $query->orWhereIn('fungsi_id', $parent_id_arr);
                        $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_arr);
                    })
                    ->where(function ($query) use ($request){
                        $query->where('tim_id', $request->input('other')['pemilik_tugas']['id']);
                        $query->orWhere('fungsi_id', $request->input('other')['pemilik_tugas']['id']);
                        $query->orWhere('satuan_kerja_tahun_id', $request->input('other')['pemilik_tugas']['id']);
                    })
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                $jurnal_harian_item_id = $request->route('jurnal_harian_item_id');
                $jurnal_harian_item = Jurnal_Harian_Item::where('id', $jurnal_harian_item_id)
                                        ->where('pelaksana_username', Auth::user()->username)
                                        ->where('jurnal_harian_proyek_id', $jurnal_harian_proyek->id)
                                        ->whereIn('type', ['0', '1', '2', '3'])
                                        ->first();

                if(!$jurnal_harian_item){
                    return abort(404, 'Id tidak ditemukan');
                }

                $jurnal_harian_item->helper_ubah_personal_2($request);

            }else if($request->input('other')['state_tambah'] == 4){
                $jurnal_harian_item_id = $request->route('jurnal_harian_item_id');
                $jurnal_harian_item = Jurnal_Harian_Item::where('id', $jurnal_harian_item_id)
                                        ->where('pelaksana_username', Auth::user()->username)
                                        ->where('type', 4)
                                        ->first();

                if($request->input('other')['pemilik_tugas']['type'] == 'satuan_kerja_tahun'){
                    if( Peran::check_user_have_role('satuan_kerja_tahun', $request->input('other')['pemilik_tugas']['id']
                        , Auth::user()->username, ['pimpinan', 'admin', 'anggota'])){
                            $jurnal_harian_item->helper_ubah_personal_2($request);
                    }else{
                        return abort(400, 'Kesalahan Input');
                    }
                }else if($request->input('other')['pemilik_tugas']['type'] == 'fungsi'){
                    if( Peran::check_user_have_role('satuan_kerja_tahun', $request->input('other')['pemilik_tugas']['id']
                        , Auth::user()->username, ['pj'])){
                            $jurnal_harian_item->helper_ubah_personal_2($request);
                    }else{
                        return abort(400, 'Kesalahan Input');
                    }
                }else if($request->input('other')['pemilik_tugas']['type'] == 'tim'){
                    if( Peran::check_user_have_role('satuan_kerja_tahun', $request->input('other')['pemilik_tugas']['id']
                        , Auth::user()->username, ['ketua', 'admin', 'anggota'])){
                            $jurnal_harian_item->helper_ubah_personal_2($request);
                    }else{
                        return abort(400, 'Kesalahan Input');
                    }
                }
            }

            DB::commit();
            return $jurnal_harian_item;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function item_hapus(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $jurnal_harian_item_id = $request->route('jurnal_harian_item_id');
            $jurnal_harian_item = Jurnal_Harian_Item::where('id', $jurnal_harian_item_id)
                                    ->where('tahun', session('tahun_active'))
                                    ->where('pelaksana_username', Auth::user()->username)
                                    // ->whereIn('type', ['0', '4'])
                                    ->first();

            if(!$jurnal_harian_item_id){
                abort(404, 'Id tidak ditemukan');
            }
            if($jurnal_harian_item->type == '0' || $jurnal_harian_item->type == '4'){
                //hapus jika personal atau tambahan
                $jurnal_harian_item->delete();
            }else {
                //ubah jika dari penugasan
                $jurnal_harian_item_lastest = Jurnal_Harian_Item::where('parent_id', $jurnal_harian_item_id)
                    ->whereIn('type', ['0', '4'])
                    ->whereNotNull('tanggal_realisasi')
                    ->whereNotNull('jam_realisasi_akhir')
                    ->orderBy('tanggal_realisasi', 'desc')
                    ->orderBy('jam_realisasi_akhir', 'desc')
                    ->first();
                if($jurnal_harian_item_lastest){
                    $jurnal_harian_item->realisasi = 0;
                    $jurnal_harian_item->status_pekerjaan = 1;
                    $jurnal_harian_item->bukti_url = $jurnal_harian_item_lastest->bukti_url;
                    $jurnal_harian_item->tanggal_realisasi = null;
                    $jurnal_harian_item->jam_realisasi_awal = null;
                    $jurnal_harian_item->jam_realisasi_akhir = null;

                    $jurnal_harian_item->status_nilai = 0;
                    $jurnal_harian_item->nilai_kualitas = null;
                }else{
                    $jurnal_harian_item->realisasi = 0;
                    $jurnal_harian_item->status_pekerjaan = 0;
                    $jurnal_harian_item->bukti_url = null;
                    $jurnal_harian_item->tanggal_realisasi = null;
                    $jurnal_harian_item->jam_realisasi_awal = null;
                    $jurnal_harian_item->jam_realisasi_akhir = null;

                    $jurnal_harian_item->status_nilai = 0;
                    $jurnal_harian_item->nilai_kualitas = null;
                }
                $jurnal_harian_item->save();
                $jurnal_harian_item->reload_nilai();
            }

            DB::commit();
            return $jurnal_harian_item;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }
}
