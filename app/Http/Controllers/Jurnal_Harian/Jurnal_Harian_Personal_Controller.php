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

use Webpatser\Uuid\Uuid;

class Jurnal_Harian_Personal_Controller extends Controller
{
    public function stat_indikator(Request $request)
    {
        $param_username = Auth::user()->username;


        $query_item_ditugaskan = Jurnal_Harian_Item::query();
        $query_item_ditugaskan->where('tahun', session('tahun_active'));
        $query_item_ditugaskan->where('pelaksana_username', $param_username);
        $query_item_ditugaskan->whereIn('type', [1, 2, 3]);
        $query_item_ditugaskan->whereNull('parent_id');


        $have_peran_pimpinan_atau_ketua = Peran::where('tahun', session('tahun_active'))
            ->whereIn('peran', ['pimpinan', 'ketua'])
            ->where('pemilik_username', Auth::user()->username)
            ->count();

        $count_belum_evaluasi = 0;

        if($have_peran_pimpinan_atau_ketua > 0){
            $bulan_selected = $request->input('bulan_selected');

            $data_can_be_nilai = Penilaian_Jurnal_Harian_Controller::get_data_can_be_nilai(null, $bulan_selected, [], [], ['1']);
            $count_belum_evaluasi = count( $data_can_be_nilai );
        }

        return ['ditugaskan' => $query_item_ditugaskan->count(), 'belum_evaluasi' => $count_belum_evaluasi];
    }

    public function daftar(Request $request)
    {

        $parent_id_arr_before_pluck = Peran::where('pemilik_username', Auth::user()->username)
            // ->where(function ($query){
            //     $query->where(function ($query2){
            //         $query2->where('tipe_peran', 'satuan_kerja_tahun');
            //         // $query2->whereIn('peran', ['pimpinan', 'admin']);
            //     });
            //     $query->orWhere(function ($query2){
            //         $query2->where('tipe_peran', 'tim');
            //         // $query2->whereIn('peran', ['ketua', 'admin']);
            //     });
            //     $query->orWhere(function ($query2){
            //         $query2->where('tipe_peran', 'proyek');
            //         // $query2->whereIn('peran', ['ketua', 'admin', 'anggota']);
            //     });
            // })
            ->whereIn('tipe_peran', ['satuan_kerja_tahun', 'tim', 'proyek'])
            ->where('tahun', session('tahun_active'))
            ->select('tipe_peran', 'parent_id', 'peran')
            ->distinct()
            ->get();

        $parent_id_satuan_kerja_tahun = [];
        $parent_id_tim = [];
        $parent_id_proyek = [];

        $tambahan_satuan_kerja_tahun_id = [];
        $tambahan_tim_id = [];

        foreach($parent_id_arr_before_pluck as $item){
            if($item->tipe_peran == 'satuan_kerja_tahun'){
                if(in_array($item->peran, ['pimpinan'])){
                    array_push($parent_id_satuan_kerja_tahun, $item->parent_id);
                }
                array_push($tambahan_satuan_kerja_tahun_id, $item->parent_id);
            }else if($item->tipe_peran == 'tim'){
                if(in_array($item->peran, ['ketua'])){
                    array_push($parent_id_tim, $item->parent_id);
                }
                array_push($tambahan_tim_id, $item->parent_id);
            }else if($item->tipe_peran == 'proyek'){
                array_push($parent_id_proyek, $item->parent_id);
            }
        }

        $proyek = Jurnal_Harian_Proyek::where('tahun', session('tahun_active'))
            ->where(function ($query) use ($parent_id_satuan_kerja_tahun, $parent_id_tim, $parent_id_proyek){
                // $query->orWhere(function($query0) use ($parent_id_satuan_kerja_tahun){
                //     $query0->whereIn('satuan_kerja_tahun_id', $parent_id_satuan_kerja_tahun);
                //     $query0->whereNull('fungsi_id');
                //     $query0->whereNull('tim_id');
                // });
                $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_satuan_kerja_tahun);
                $query->orWhereIn('tim_id', $parent_id_tim);
                $query->orWhereIn('id', $parent_id_proyek);
            })
            ->get();

        $satuan_kerja_tahun_id_from_proyek = [];
        $tim_id_from_proyek = [];

        foreach($proyek as $item){
            if(!is_null($item->tim_id)){
                if(!in_array($item->tim_id, $tim_id_from_proyek)){
                    array_push($tim_id_from_proyek, $item->tim_id);
                }
            }
            if(!is_null($item->satuan_kerja_tahun_id) && is_null($item->fungsi_id) && is_null($item->tim_id)){
                if(!in_array($item->satuan_kerja_tahun_id, $satuan_kerja_tahun_id_from_proyek)){
                    array_push($satuan_kerja_tahun_id_from_proyek, $item->satuan_kerja_tahun_id);
                }
            }
        }

        $satuan_kerja_tahun = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                    ->where(function($query) use ($tambahan_satuan_kerja_tahun_id, $satuan_kerja_tahun_id_from_proyek){
                        $query->orWhereIn('id', $tambahan_satuan_kerja_tahun_id);
                        $query->orWhereIn('id', $satuan_kerja_tahun_id_from_proyek);
                    })
                    ->get();

        $tim = Tim::where('tahun', session('tahun_active'))
                    ->where(function ($query) use ($tambahan_satuan_kerja_tahun_id, $satuan_kerja_tahun_id_from_proyek, $tambahan_tim_id, $tim_id_from_proyek){
                        $query->orWhereIn('satuan_kerja_tahun_id', $tambahan_satuan_kerja_tahun_id);
                        $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_id_from_proyek);
                        $query->orWhereIn('id', $tambahan_tim_id);
                        $query->orWhereIn('id', $tim_id_from_proyek);
                    })
                    ->get();

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


        $query_item_belum_on_progress->where(function ($query) use ($id_tambahan){
            $query->orWhereIn('id', $id_tambahan);
            $query->orWhere(function($query0){
                $query0->WhereIn('status_pekerjaan', [0,1]);
                $query0->where(function($query1){
                    $query1->orWhereIn('type', [1,2,3]);
                    $query1->orWhere(function($query2){
                        $query2->where('type', 4);
                        $query2->whereColumn('pembuat_username', '!=', 'pelaksana_username');
                    });
                });
            });

        });

        $item_belum_on_progress = $query_item_belum_on_progress->get();

        return [
            'key_dict' => [
                'utama' =>  ['satuan_kerja_tahun_id' => $satuan_kerja_tahun_id_from_proyek, 'fungsi_id' => [], 'tim_id' => $tim_id_from_proyek],
                'tambahan' => ['satuan_kerja_tahun_id' => $tambahan_satuan_kerja_tahun_id, 'fungsi_id' => [], 'tim_id' => $tambahan_tim_id]
            ],
            'satuan_kerja_tahun' => $satuan_kerja_tahun,
            'tim' => $tim,
            'proyek' => $proyek,
            'item_sesuai_tanggal' => $item_sesuai_tanggal,
            'item_belum_on_progress' => $item_belum_on_progress,
        ];

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
                            $query2->where('tipe_peran', 'satuan_kerja_tahun');
                            $query2->where('peran', 'pimpinan');
                        });
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
                            $query2->where('tipe_peran', 'satuan_kerja_tahun');
                            $query2->where('peran', 'pimpinan');
                        });
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
            if($jurnal_harian_item->type == '0' ||
                (
                    $jurnal_harian_item->type == '4'
                    && $jurnal_harian_item->pembuat_username == Auth::user()->username
                    && $jurnal_harian_item->pelaksana_username == Auth::user()->username
                )
            ){
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

    public function daftar_ditugaskan(Request $request)
    {
        $param_username = Auth::user()->username;
        $query_item = Jurnal_Harian_Item::query();
        $query_item->where('tahun', session('tahun_active'));
        $query_item->where('pelaksana_username', $param_username);
        $query_item->where(function($query){
            $query->orWhereIn('type', [1,2,3]);
            $query->orWhere(function($query1){
                $query1->where('type', 4);
                $query1->whereColumn('pembuat_username', '!=', 'pelaksana_username');
            });
        });

        $query_item->whereNull('parent_id');

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

    public function penugasan_item_daftar(Request $request)
    {
        $bulan_selected = $request->input('bulan_selected');

        $peran_as_pimpinan_atau_ketua = Peran::where('pemilik_username', Auth::user()->username)
            ->where('tahun', session('tahun_active'))
            ->whereIn('peran', ['pimpinan', 'ketua'])
            ->get();

        $pimpinan_satuan_kerja_tahun_id = [];
        $ketua_tim_id = [];
        $ketua_proyek_id = [];
        $parent_id_all = [];

        foreach($peran_as_pimpinan_atau_ketua as $item_peran){
            if($item_peran->tipe_peran == 'satuan_kerja_tahun'){
                array_push($pimpinan_satuan_kerja_tahun_id, $item_peran->parent_id);
            }else if($item_peran->tipe_peran == 'tim'){
                array_push($ketua_tim_id, $item_peran->parent_id);
            }else if($item_peran->tipe_peran == 'proyek'){
                array_push($ketua_proyek_id, $item_peran->parent_id);
            }
            array_push($parent_id_all, $item_peran->parent_id);
        }

        $satuan_kerja_tahun_child_id = collect(Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->whereIn('parent_id', $pimpinan_satuan_kerja_tahun_id)
                ->get())
                ->pluck('id')->toArray();

        $pimpinan_satuan_kerja_tahun_id = array_merge($pimpinan_satuan_kerja_tahun_id, $satuan_kerja_tahun_child_id);
        $parent_id_all = array_merge($parent_id_all, $satuan_kerja_tahun_child_id);

        $proyek = Jurnal_Harian_Proyek::where(function($query) use ($pimpinan_satuan_kerja_tahun_id, $ketua_tim_id, $ketua_proyek_id) {
            $query->orWhereIn('satuan_kerja_tahun_id', $pimpinan_satuan_kerja_tahun_id);
            $query->orWhereIn('tim_id', $ketua_tim_id);
            $query->orWhereIn('id', $ketua_proyek_id);
        })->get();

        $tim = Tim::where(function($query) use ($pimpinan_satuan_kerja_tahun_id, $ketua_tim_id, $proyek) {
            $query->orWhereIn('satuan_kerja_tahun_id', $pimpinan_satuan_kerja_tahun_id);
            $query->orWhereIn('id', $ketua_tim_id);

            $tim_id_ = [];
            foreach($proyek as $item){
                array_push($tim_id_, $item->tim_id);
            }
            $query->orWhereIn('id', $tim_id_);
        })->get();

        $satuan_kerja_tahun = Satuan_Kerja_Tahun::where(function($query) use ($pimpinan_satuan_kerja_tahun_id, $tim) {
            $query->orWhereIn('id', $pimpinan_satuan_kerja_tahun_id);

            $satuan_kerja_tahun_id_ = [];
            foreach($tim as $item){
                array_push($satuan_kerja_tahun_id_, $item->satuan_kerja_tahun_id);
            }
            $query->orWhereIn('id', $satuan_kerja_tahun_id_);
        })->get();

        foreach($proyek as $item){
            if(!in_array($item->id, $parent_id_all)
                && ( in_array($item->satuan_kerja_tahun_id, $pimpinan_satuan_kerja_tahun_id) || in_array($item->tim_id, $ketua_tim_id) )){
                    array_push($parent_id_all, $item->id);
            }
        }

        foreach($tim as $item){
            if(!in_array($item->id, $parent_id_all)
                && in_array($item->satuan_kerja_tahun_id, $pimpinan_satuan_kerja_tahun_id)){
                    array_push($parent_id_all, $item->id);
            }
        }


        $peran_anggota = Peran::whereIn('parent_id', $parent_id_all)->orderBy('peran_number')->get();

        $struktur_id = ['satuan_kerja_tahun' => [], 'tim' => [], 'proyek' => []];
        $struktur = ['satuan_kerja_tahun' => [], 'tim' => [], 'proyek' => []];

        foreach($satuan_kerja_tahun as $item){
            $struktur_id['satuan_kerja_tahun'][$item->id] = count($struktur['satuan_kerja_tahun']);
            array_push($struktur['satuan_kerja_tahun'], ['item' => $item, 'anggota' => []]);
        }
        foreach($tim as $item){
            $struktur_id['tim'][$item->id] = count($struktur['tim']);
            array_push($struktur['tim'], ['item' => $item, 'anggota' => []]);
        }
        foreach($proyek as $item){
            $struktur_id['proyek'][$item->id] = count($struktur['proyek']);
            array_push($struktur['proyek'], ['item' => $item, 'anggota' => []]);
        }

        foreach($peran_anggota as $item_anggota){
            if($item_anggota->tipe_peran == 'satuan_kerja_tahun'){
                array_push($struktur['satuan_kerja_tahun'][$struktur_id['satuan_kerja_tahun'][$item_anggota->parent_id]]['anggota'], $item_anggota);
            }else if($item_anggota->tipe_peran == 'tim'){
                array_push($struktur['tim'][$struktur_id['tim'][$item_anggota->parent_id]]['anggota'], $item_anggota);
            }else if($item_anggota->tipe_peran == 'proyek'){
                array_push($struktur['proyek'][$struktur_id['proyek'][$item_anggota->parent_id]]['anggota'], $item_anggota);
            }
        }

        $kegiatan_item = Jurnal_Harian_Item::where(function ($query) use ($pimpinan_satuan_kerja_tahun_id, $ketua_tim_id, $ketua_proyek_id) {
            $query->orWhereIn('satuan_kerja_tahun_id', $pimpinan_satuan_kerja_tahun_id);
            $query->orWhereIn('tim_id', $ketua_tim_id);
            $query->orWhereIn('jurnal_harian_proyek_id', $ketua_proyek_id);
            // $query->orWhere('pembuat_username', Auth::user()->username);
        })
        ->where(function ($query){
            $query->orWhereIn('type', ['1', '2', '3']);
            $query->orWhere(function ($query1) {
                $query1->where('type', '4');
                $query1->whereColumn('pembuat_username', '!=', 'pelaksana_username');
            });
        })
        ->where(function ($query) use ($bulan_selected){
            $query->orWhereMonth('tanggal_deadline', $bulan_selected);
            $query->orWhereMonth('tanggal_realisasi', $bulan_selected);
        })
        ->where('tahun', session('tahun_active'))
        ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END ASC')
        ->get();

        return [
            'struktur' => $struktur,
            'key_as_pimpinan_ketua' => ['satuan_kerja' => $pimpinan_satuan_kerja_tahun_id, 'tim' => $ketua_tim_id],
            'anggota' => $peran_anggota,
            'kegiatan_item'=> $kegiatan_item
        ];
    }

    public function penugasan_item_tambah(Request $request)
    {
        $peran_as_pimpinan_atau_ketua = Peran::where('pemilik_username', Auth::user()->username)
            ->where('tahun', session('tahun_active'))
            ->whereIn('peran', ['pimpinan', 'ketua'])
            ->get();

        $pimpinan_satuan_kerja_tahun_id = [];
        $ketua_tim_id = [];
        $ketua_proyek_id = [];

        foreach($peran_as_pimpinan_atau_ketua as $item_peran){
            if($item_peran->tipe_peran == 'satuan_kerja_tahun'){
                array_push($pimpinan_satuan_kerja_tahun_id, $item_peran->parent_id);
            }else if($item_peran->tipe_peran == 'tim'){
                array_push($ketua_tim_id, $item_peran->parent_id);
            }else if($item_peran->tipe_peran == 'proyek'){
                array_push($ketua_proyek_id, $item_peran->parent_id);
            }
        }

        $satuan_kerja_tahun_child_id = collect(Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->whereIn('parent_id', $pimpinan_satuan_kerja_tahun_id)
                ->get())
                ->pluck('id')->toArray();

        $pimpinan_satuan_kerja_tahun_id = array_merge($pimpinan_satuan_kerja_tahun_id, $satuan_kerja_tahun_child_id);


        $req_satuan_kerja_tahun_id = $request->input('satuan_kerja_tahun_id');
        $req_tim_id = $request->input('tim_id');
        $req_proyek_id = $request->input('jurnal_harian_proyek_id');
        $req_type = $request->input('type');

        if(is_null( $req_type)){
            abort(422);
        }

        if(is_null($req_satuan_kerja_tahun_id) && is_null($req_tim_id) && is_null($req_proyek_id)){
            abort(422);
        }


        if(in_array($req_type, ['1', '2','3'])){
            if(is_null($req_satuan_kerja_tahun_id) || is_null($req_tim_id) || is_null($req_proyek_id)){
                abort(422);
            }

            if( ! (in_array( $req_satuan_kerja_tahun_id, $pimpinan_satuan_kerja_tahun_id) || in_array( $req_tim_id, $ketua_tim_id) || in_array( $req_proyek_id, $ketua_proyek_id) ) ){
                abort(403);
            }

            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $request->input('jurnal_harian_proyek_id'))
                ->where(function($query) use ($pimpinan_satuan_kerja_tahun_id, $ketua_tim_id, $ketua_proyek_id) {
                    $query->orWhereIn('satuan_kerja_tahun_id', $pimpinan_satuan_kerja_tahun_id);
                    $query->orWhereIn('tim_id', $ketua_tim_id);
                    $query->orWhereIn('id', $ketua_proyek_id);
                })->first();

            $pelaksana_peran_proyek = Peran::where('parent_id', $request->input('jurnal_harian_proyek_id'))
                ->where('pemilik_username', $request->input('pelaksana_username'))
                ->first();

            if(! ($jurnal_harian_proyek && $pelaksana_peran_proyek) ){
                abort(404);
            }

            DB::beginTransaction();
            try{
                $satuan_kerja_tahun = Satuan_Kerja_Tahun::where('id', $req_satuan_kerja_tahun_id)->first();
                $have_auth_penilaian_as_ketua = true;

                if(in_array($satuan_kerja_tahun->id, $pimpinan_satuan_kerja_tahun_id)){
                    if(!is_null($satuan_kerja_tahun->parent_id)
                        && Auth::user()->username == $request->input('pelaksana_username')){
                            $have_auth_penilaian_as_ketua = false;
                    }
                }

                $baru = Jurnal_Harian_Item::helper_tambah_with_proyek($req_type, $jurnal_harian_proyek, $request, $have_auth_penilaian_as_ketua);
                DB::commit();
                return $baru;
            }catch(Exception $ex){
                DB::rollBack();
                Log::error($ex);
                return abort(500);
            }
        }else if($req_type == '4'){
            if( ! (in_array( $req_satuan_kerja_tahun_id, $pimpinan_satuan_kerja_tahun_id) || in_array( $req_tim_id, $ketua_tim_id) || in_array( $req_proyek_id, $ketua_proyek_id) ) ){
                abort(403);
            }

            $tambahan_data = ['satuan_kerja_tahun_id' => $req_satuan_kerja_tahun_id, 'fungsi_id' => null, 'tim_id' => $req_tim_id];

            $pelaksana_peran_proyek = null;

            if(!is_null($req_tim_id)){
                $pelaksana_peran_proyek = Peran::where('parent_id', $req_tim_id)
                    ->where('pemilik_username', $request->input('pelaksana_username'))
                    ->first();
            }else if(!is_null($req_satuan_kerja_tahun_id)){
                $pelaksana_peran_proyek = Peran::where('parent_id', $req_satuan_kerja_tahun_id)
                    ->where('pemilik_username', $request->input('pelaksana_username'))
                    ->first();
            }

            if(! $pelaksana_peran_proyek ){
                abort(404);
            }

            DB::beginTransaction();
            try{
                $baru = Jurnal_Harian_Item::helper_tambah_penugasan_tambahan($request, $tambahan_data);
                DB::commit();
                return $baru;
            }catch(Exception $ex){
                DB::rollBack();
                Log::error($ex);
                return abort(500);
            }
        }
    }

    public function penugasan_item_ubah(Request $request)
    {
        $peran_as_pimpinan_atau_ketua = Peran::where('pemilik_username', Auth::user()->username)
            ->where('tahun', session('tahun_active'))
            ->whereIn('peran', ['pimpinan', 'ketua'])
            ->get();

        $pimpinan_satuan_kerja_tahun_id = [];
        $ketua_tim_id = [];
        $ketua_proyek_id = [];

        foreach($peran_as_pimpinan_atau_ketua as $item_peran){
            if($item_peran->tipe_peran == 'satuan_kerja_tahun'){
                array_push($pimpinan_satuan_kerja_tahun_id, $item_peran->parent_id);
            }else if($item_peran->tipe_peran == 'tim'){
                array_push($ketua_tim_id, $item_peran->parent_id);
            }else if($item_peran->tipe_peran == 'proyek'){
                array_push($ketua_proyek_id, $item_peran->parent_id);
            }
        }

        $satuan_kerja_tahun_child_id = collect(Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->whereIn('parent_id', $pimpinan_satuan_kerja_tahun_id)
                ->get())
                ->pluck('id')->toArray();

        $pimpinan_satuan_kerja_tahun_id = array_merge($pimpinan_satuan_kerja_tahun_id, $satuan_kerja_tahun_child_id);


        $req_satuan_kerja_tahun_id = $request->input('satuan_kerja_tahun_id');
        $req_tim_id = $request->input('tim_id');
        $req_proyek_id = $request->input('jurnal_harian_proyek_id');
        $req_type = $request->input('type');

        if(is_null( $req_type)){
            abort(422);
        }

        if(is_null($req_satuan_kerja_tahun_id) && is_null($req_tim_id) && is_null($req_proyek_id)){
            abort(422);
        }

        $jurnal_harian_item = Jurnal_Harian_Item::where('id', $request->route('jurnal_harian_item_id'))
            ->where(function($query) use ($pimpinan_satuan_kerja_tahun_id, $ketua_tim_id, $ketua_proyek_id) {
                $query->orWhereIn('satuan_kerja_tahun_id', $pimpinan_satuan_kerja_tahun_id);
                $query->orWhereIn('tim_id', $ketua_tim_id);
                $query->orWhereIn('jurnal_harian_proyek_id', $ketua_proyek_id);
                // $query->orWhere('pembuat_username', Auth::user()->username);
            })
            ->where(function ($query){
                $query->orWhereIn('type', ['1', '2', '3']);
                $query->orWhere(function ($query1) {
                    $query1->where('type', '4');
                    $query1->whereColumn('pembuat_username', '!=', 'pelaksana_username');
                });
            })
            ->where('tahun', session('tahun_active'))
            ->first();

        if(! ($jurnal_harian_item ) ){
            abort(404);
        }

        if(in_array($req_type, ['1', '2','3'])){

            if(is_null($req_satuan_kerja_tahun_id) || is_null($req_tim_id) || is_null($req_proyek_id)){
                abort(422);
            }

            if( ! (in_array( $req_satuan_kerja_tahun_id, $pimpinan_satuan_kerja_tahun_id) || in_array( $req_tim_id, $ketua_tim_id) || in_array( $req_proyek_id, $ketua_proyek_id) ) ){
                abort(403);
            }

            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $request->input('jurnal_harian_proyek_id'))
                ->where(function($query) use ($pimpinan_satuan_kerja_tahun_id, $ketua_tim_id, $ketua_proyek_id) {
                    $query->orWhereIn('satuan_kerja_tahun_id', $pimpinan_satuan_kerja_tahun_id);
                    $query->orWhereIn('tim_id', $ketua_tim_id);
                    $query->orWhereIn('id', $ketua_proyek_id);
                })->first();

            $pelaksana_peran_proyek = Peran::where('parent_id', $request->input('jurnal_harian_proyek_id'))
                ->where('pemilik_username', $request->input('pelaksana_username'))
                ->first();

            if(! ($jurnal_harian_proyek && $pelaksana_peran_proyek) ){
                abort(404);
            }

            DB::beginTransaction();
            try{
                $satuan_kerja_tahun = Satuan_Kerja_Tahun::where('id', $req_satuan_kerja_tahun_id)->first();
                $have_auth_penilaian_as_ketua = true;

                if(in_array($satuan_kerja_tahun->id, $pimpinan_satuan_kerja_tahun_id)){
                    if(!is_null($satuan_kerja_tahun->parent_id)
                        && Auth::user()->username == $request->input('pelaksana_username')){
                            $have_auth_penilaian_as_ketua = false;
                    }
                }

                $jurnal_harian_item->helper_ubah_with_proyek($request, $have_auth_penilaian_as_ketua, $jurnal_harian_proyek);
                DB::commit();
                return $jurnal_harian_item;;
            }catch(Exception $ex){
                DB::rollBack();
                Log::error($ex);
                return abort(500);
            }
        }else if($req_type == '4'){
            if( ! (in_array( $req_satuan_kerja_tahun_id, $pimpinan_satuan_kerja_tahun_id) || in_array( $req_tim_id, $ketua_tim_id) || in_array( $req_proyek_id, $ketua_proyek_id) ) ){
                abort(403);
            }

            $tambahan_data = ['satuan_kerja_tahun_id' => $req_satuan_kerja_tahun_id, 'fungsi_id' => null, 'tim_id' => $req_tim_id];

            $pelaksana_peran_proyek = null;

            if(!is_null($req_tim_id)){
                $pelaksana_peran_proyek = Peran::where('parent_id', $req_tim_id)
                    ->where('pemilik_username', $request->input('pelaksana_username'))
                    ->first();
            }else if(!is_null($req_satuan_kerja_tahun_id)){
                $pelaksana_peran_proyek = Peran::where('parent_id', $req_satuan_kerja_tahun_id)
                    ->where('pemilik_username', $request->input('pelaksana_username'))
                    ->first();
            }

            if(! $pelaksana_peran_proyek ){
                abort(404);
            }

            DB::beginTransaction();
            try{
                $jurnal_harian_item->helper_ubah_penugasan_tambahan($request, $tambahan_data);
                return $jurnal_harian_item;
            }catch(Exception $ex){
                DB::rollBack();
                Log::error($ex);
                return abort(500);
            }
        }
    }

    public function penugasan_item_hapus(Request $request)
    {
        $peran_as_pimpinan_atau_ketua = Peran::where('pemilik_username', Auth::user()->username)
            ->where('tahun', session('tahun_active'))
            ->whereIn('peran', ['pimpinan', 'ketua'])
            ->get();

        $pimpinan_satuan_kerja_tahun_id = [];
        $ketua_tim_id = [];
        $ketua_proyek_id = [];

        foreach($peran_as_pimpinan_atau_ketua as $item_peran){
            if($item_peran->tipe_peran == 'satuan_kerja_tahun'){
                array_push($pimpinan_satuan_kerja_tahun_id, $item_peran->parent_id);
            }else if($item_peran->tipe_peran == 'tim'){
                array_push($ketua_tim_id, $item_peran->parent_id);
            }else if($item_peran->tipe_peran == 'proyek'){
                array_push($ketua_proyek_id, $item_peran->parent_id);
            }
        }

        $satuan_kerja_tahun_child_id = collect(Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->whereIn('parent_id', $pimpinan_satuan_kerja_tahun_id)
                ->get())
                ->pluck('id')->toArray();

        $pimpinan_satuan_kerja_tahun_id = array_merge($pimpinan_satuan_kerja_tahun_id, $satuan_kerja_tahun_child_id);

        $jurnal_harian_item = Jurnal_Harian_Item::where('id', $request->route('jurnal_harian_item_id'))
            ->where(function($query) use ($pimpinan_satuan_kerja_tahun_id, $ketua_tim_id, $ketua_proyek_id) {
                $query->orWhereIn('satuan_kerja_tahun_id', $pimpinan_satuan_kerja_tahun_id);
                $query->orWhereIn('tim_id', $ketua_tim_id);
                $query->orWhereIn('jurnal_harian_proyek_id', $ketua_proyek_id);
                // $query->orWhere('pembuat_username', Auth::user()->username);
            })
            ->where(function ($query){
                $query->orWhereIn('type', ['1', '2', '3']);
                $query->orWhere(function ($query1) {
                    $query1->where('type', '4');
                    $query1->whereColumn('pembuat_username', '!=', 'pelaksana_username');
                });
            })
            ->where('tahun', session('tahun_active'))
            ->first();

        if(! ($jurnal_harian_item ) ){
            abort(404);
        }


        DB::beginTransaction();
        try{
            $jurnal_harian_item->delete();
            DB::commit();
            return $jurnal_harian_item;;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function penilaian_daftar(Request $request)
    {
        if(!$request->input('bulan_selected')){
            return abort(400, "Kekurangan Parameter");
        }
        return Penilaian_Jurnal_Harian_Controller::get_data_can_be_nilai(null, $request->input('bulan_selected'), [], [], []);
    }

    public function penilaian_nilai(Request $request)
    {
        DB::beginTransaction();
        try
        {
            if(!( $request->input('bulan_selected'))){
                return abort(400, "Kekurangan Parameter");
            }

            $item_nilai_format = [];
            $item_nilai_format_id = [];
            foreach($request->input('nilai') as $item_nilai){
                $item_nilai_format[$item_nilai['id']] = ['status_nilai' => $item_nilai['status_nilai'], 'nilai_kualitas' => $item_nilai['nilai_kualitas']];
                array_push($item_nilai_format_id, $item_nilai['id']);
            }

            $item_bisa_dinilai_arr = Penilaian_Jurnal_Harian_Controller::get_data_can_be_nilai(null, $request->input('bulan_selected'), $item_nilai_format_id, [], []);

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
