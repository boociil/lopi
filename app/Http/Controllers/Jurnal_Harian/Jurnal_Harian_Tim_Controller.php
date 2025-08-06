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
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Exception;

use Webpatser\Uuid\Uuid;

class Jurnal_Harian_Tim_Controller extends Controller
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

            Log::alert($parent_id_arr_before_pluck);

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

            $tim = Tim::where('tahun', session('tahun_active'))
                        ->where(function ($query) use ($parent_id_satuan_kerja_tahun, $satuan_kerja_tahun_id_from_proyek, $parent_id_tim, $tim_id_from_proyek){
                            $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_satuan_kerja_tahun);
                            $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_id_from_proyek);
                            $query->orWhereIn('id', $parent_id_tim);
                            $query->orWhereIn('id', $tim_id_from_proyek);

                        })
                        ->get();

            $satuan_kerja_tahun_id_dari_tim_kecuali = [];
            foreach($tim as $tim_item){
                if(!in_array($tim_item->satuan_kerja_tahun_id, $parent_id_satuan_kerja_tahun) && !in_array($tim_item->satuan_kerja_tahun_id, $satuan_kerja_tahun_id_from_proyek)){
                    array_push($satuan_kerja_tahun_id_dari_tim_kecuali, $tim_item->satuan_kerja_tahun_id);
                }
            }

            $satuan_kerja_tahun = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->where(function($query) use ($parent_id_satuan_kerja_tahun, $satuan_kerja_tahun_id_from_proyek, $satuan_kerja_tahun_id_dari_tim_kecuali){
                    $query->orWhereIn('id', $parent_id_satuan_kerja_tahun);
                    $query->orWhereIn('id', $satuan_kerja_tahun_id_from_proyek);
                    $query->orWhereIn('id', $satuan_kerja_tahun_id_dari_tim_kecuali);
                })
                ->get();

            return [
                'satuan_kerja_tahun' => $satuan_kerja_tahun,
                'tim' => $tim,
                'proyek' => $proyek
            ];
        }
    }

    public function proyek_daftar(Request $request)
    {
        $peran_super_admin = Auth::user()->peran_super_admin;
        $type = $request->input('type');
        $satuan_kerja_tahun_id = $request->input('satuan_kerja_tahun_id');
        $tim_id = $request->input('tim_id');

        if(! ($type && $satuan_kerja_tahun_id && $tim_id) ){
            return abort(422);
        }

        if(count($peran_super_admin) > 0){
            return Jurnal_Harian_Proyek::where('tahun', session('tahun_active'))
                    ->where(function ($query) use ($type, $satuan_kerja_tahun_id, $tim_id){
                        if($type == 'satuan_kerja_tahun'){
                            $query->where('satuan_kerja_tahun_id', $satuan_kerja_tahun_id);
                            $query->where('tim_id', null);
                        }else if($type == 'tim'){
                            $query->where('satuan_kerja_tahun_id', $satuan_kerja_tahun_id);
                            $query->where('tim_id', $tim_id);
                        }
                    })
                    ->get();
        }else{
            $parent_id_arr = [];
            if($satuan_kerja_tahun_id){
                array_push($parent_id_arr, $satuan_kerja_tahun_id);
            }
            if($tim_id){
                array_push($parent_id_arr, $tim_id);
            }
            $parent_id_arr_before_pluck = Peran::where('pemilik_username', Auth::user()->username)
                ->where(function ($query) use ($parent_id_arr){
                    $query->orWhere(function ($query_1) use ($parent_id_arr){
                        $query_1->where('tipe_peran', 'satuan_kerja_tahun');
                        $query_1->whereIn('peran', ['pimpinan', 'admin']);
                    });
                    $query->orWhere(function ($query_1) use ($parent_id_arr){
                        $query_1->whereIn('parent_id', $parent_id_arr);
                        $query_1->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'tim');
                            $query2->whereIn('peran', ['ketua', 'admin']);
                        });
                        $query_1->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'proyek');
                            $query2->whereIn('peran', ['ketua', 'admin', 'anggota']);
                        });
                    });

                })
                ->where('tahun', session('tahun_active'))
                ->select('tipe_peran', 'parent_id')
                ->distinct()
                ->get();

            $parent_id_satuan_kerja_tahun = [];
            $parent_id_satuan_kerja_tahun__ = [];
            $parent_id_tim = [];
            $parent_id_proyek = [];

            foreach($parent_id_arr_before_pluck as $item){
                if($item->tipe_peran == 'satuan_kerja_tahun'){
                    array_push($parent_id_satuan_kerja_tahun__, $item->parent_id);
                    if(in_array($item->parent_id, $parent_id_arr)){
                        array_push($parent_id_satuan_kerja_tahun, $item->parent_id);
                    }
                }else if($item->tipe_peran == 'tim'){
                    array_push($parent_id_tim, $item->parent_id);
                }else if($item->tipe_peran == 'proyek'){
                    array_push($parent_id_proyek, $item->parent_id);
                }
            }

            $satuan_kerja_tahun_child_id = collect(Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                    ->whereIn('parent_id', $parent_id_satuan_kerja_tahun__)
                    ->get())
                    ->pluck('id')->toArray();

            $parent_id_satuan_kerja_tahun = array_merge($parent_id_satuan_kerja_tahun, $satuan_kerja_tahun_child_id);

            return Jurnal_Harian_Proyek::where('tahun', session('tahun_active'))
                ->where(function ($query) use ($parent_id_satuan_kerja_tahun, $parent_id_tim, $parent_id_proyek){
                    $query->orWhereIn('id', $parent_id_proyek);
                    $query->orWhereIn('tim_id', $parent_id_tim);
                    $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_satuan_kerja_tahun);
                })
                ->where('tim_id',  $tim_id)
                ->get();
        }
    }

    public function proyek_tambah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            if(!$request->input('type')){
                return abort(422, 'Data type harus tersedia');
            }

            $peran_super_admin = Auth::user()->peran_super_admin;

            $satuan_kerja_tahun_ = Satuan_Kerja_Tahun::where('id', $request->input('satuan_kerja_tahun_id'))
                        ->where('tahun', session('tahun_active'))
                        ->first();
            if(!$satuan_kerja_tahun_){
                abort(404, 'Id tidak ditemukan');
            }
            $tim_ = Tim::where('id', $request->input('tim_id'))
                        ->where('satuan_kerja_tahun_id', $satuan_kerja_tahun_->id)
                        ->where('tahun', session('tahun_active'))
                        ->first();
            if(!$tim_){
                abort(404, 'Id tidak ditemukan');
            }

            $baru = null;

            if(count($peran_super_admin) > 0){
                $baru = Jurnal_Harian_Proyek::helper_tambah($satuan_kerja_tahun_, $tim_, $request);
            }else{
                $parent_id_arr = collect(Peran::where('pemilik_username', Auth::user()->username)
                    ->where(function ($query){
                        $query->where(function ($query2){
                            $query2->where('tipe_peran', 'satuan_kerja_tahun');
                            $query2->whereIn('peran', ['pimpinan', 'admin']);
                        });
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'tim');
                            $query2->whereIn('peran', ['ketua', 'admin']);
                        });
                    })
                    ->where('tahun', session('tahun_active'))
                    ->select('parent_id')
                    ->distinct()
                    ->get()
                )->pluck('parent_id')->toArray();

                $ada_auth = false;
                if(in_array($satuan_kerja_tahun_->id, $parent_id_arr)){
                    $ada_auth = true;
                }
                if(in_array($tim_->id, $parent_id_arr)){
                    $ada_auth = true;
                }

                if(!$ada_auth){
                    abort(403, 'Tidak memiliki akses untuk menambahkan');
                }

                $baru = Jurnal_Harian_Proyek::helper_tambah($satuan_kerja_tahun_, $tim_, $request);
            }

            DB::commit();
            return $baru;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function proyek_lihat(Request $request)
    {
        $peran_super_admin = Auth::user()->peran_super_admin;
        if(count($peran_super_admin) > 0){
            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            return $jurnal_harian_proyek;
        }else{
            $relevant_peran = Peran::where('pemilik_username', Auth::user()->username)
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

            $satuan_kerja_tahun_parent_id = [];
            $tim_parent_id = [];
            $proyek_parent_id = [];

            foreach($relevant_peran as $item){
                if($item->tipe_peran == 'satuan_kerja_tahun'){
                    array_push($satuan_kerja_tahun_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'tim'){
                    array_push($tim_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'proyek'){
                    array_push($proyek_parent_id, $item->parent_id);
                }
            }

            $satuan_kerja_tahun_id_child = collect(Satuan_Kerja_Tahun::whereIn('parent_id', $satuan_kerja_tahun_parent_id)->get())->pluck('id')->toArray();
            $satuan_kerja_tahun_parent_id = array_merge($satuan_kerja_tahun_parent_id,  $satuan_kerja_tahun_id_child);

            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where(function ($query) use ($satuan_kerja_tahun_parent_id, $tim_parent_id, $proyek_parent_id){
                    $query->orWhereIn('id', $proyek_parent_id);
                    $query->orWhereIn('tim_id', $tim_parent_id);
                    $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_parent_id);
                })
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $satuan_kerja_tahun_parent_id)
                or in_array($jurnal_harian_proyek->tim_id, $tim_parent_id)
                or in_array($jurnal_harian_proyek->id, $proyek_parent_id) )){
                    return abort(403, 'Tidak memiliki akses untuk melihat');
            }

            return $jurnal_harian_proyek;
        }

    }

    public function proyek_ubah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $peran_super_admin = Auth::user()->peran_super_admin;
            if(count($peran_super_admin) > 0){
                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                $jurnal_harian_proyek->helper_ubah($request);

                DB::commit();
                return 1;
            }else{
                $relevant_peran = Peran::where('pemilik_username', Auth::user()->username)
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
                            $query2->whereIn('peran', ['ketua', 'admin']);
                        });
                    })
                    ->where('tahun', session('tahun_active'))
                    ->select('tipe_peran', 'parent_id')
                    ->distinct()
                    ->get();

                $satuan_kerja_tahun_parent_id = [];
                $tim_parent_id = [];
                $proyek_parent_id = [];

                foreach($relevant_peran as $item){
                    if($item->tipe_peran == 'satuan_kerja_tahun'){
                        array_push($satuan_kerja_tahun_parent_id, $item->parent_id);
                    }else if($item->tipe_peran == 'tim'){
                        array_push($tim_parent_id, $item->parent_id);
                    }else if($item->tipe_peran == 'proyek'){
                        array_push($proyek_parent_id, $item->parent_id);
                    }
                }

                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where(function ($query) use ($satuan_kerja_tahun_parent_id, $tim_parent_id, $proyek_parent_id){
                        $query->orWhereIn('id', $proyek_parent_id);
                        $query->orWhereIn('tim_id', $tim_parent_id);
                        $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_parent_id);
                    })
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $satuan_kerja_tahun_parent_id)
                    or in_array($jurnal_harian_proyek->tim_id, $tim_parent_id)
                    or in_array($jurnal_harian_proyek->id, $proyek_parent_id) )){
                        return abort(403, 'Tidak memiliki akses untuk melihat');
                }

                $jurnal_harian_proyek->helper_ubah($request);

                DB::commit();
                return 1;
            }
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function proyek_hapus(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $peran_super_admin = Auth::user()->peran_super_admin;
            if(count($peran_super_admin) > 0){
                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                $jurnal_harian_proyek->helper_hapus();
                DB::commit();
                return 1;
            }else{
                $relevant_peran = Peran::where('pemilik_username', Auth::user()->username)
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
                            $query2->whereIn('peran', ['ketua', 'admin']);
                        });
                    })
                    ->where('tahun', session('tahun_active'))
                    ->select('tipe_peran', 'parent_id')
                    ->distinct()
                    ->get();

                $satuan_kerja_tahun_parent_id = [];
                $tim_parent_id = [];
                $proyek_parent_id = [];

                foreach($relevant_peran as $item){
                    if($item->tipe_peran == 'satuan_kerja_tahun'){
                        array_push($satuan_kerja_tahun_parent_id, $item->parent_id);
                    }else if($item->tipe_peran == 'tim'){
                        array_push($tim_parent_id, $item->parent_id);
                    }else if($item->tipe_peran == 'proyek'){
                        array_push($proyek_parent_id, $item->parent_id);
                    }
                }

                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where(function ($query) use ($satuan_kerja_tahun_parent_id, $tim_parent_id, $proyek_parent_id){
                        $query->orWhereIn('id', $proyek_parent_id);
                        $query->orWhereIn('tim_id', $tim_parent_id);
                        $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_parent_id);
                    })
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $satuan_kerja_tahun_parent_id)
                    or in_array($jurnal_harian_proyek->tim_id, $tim_parent_id)
                    or in_array($jurnal_harian_proyek->id, $proyek_parent_id) )){
                        return abort(403, 'Tidak memiliki akses untuk melihat');
                }

                $jurnal_harian_proyek->helper_hapus();
                DB::commit();
                return 1;
            }
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function peran_daftar(Request $request)
    {
        $peran_super_admin = Auth::user()->peran_super_admin;

        $jurnal_harian_proyek_id = null;
        $jurnal_harian_proyek = null;
        $have_auth = false;
        if(count($peran_super_admin) > 0){
            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            $have_auth = true;
        }else{
            $relevant_peran = Peran::where('pemilik_username', Auth::user()->username)
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

            $satuan_kerja_tahun_parent_id = [];
            $tim_parent_id = [];
            $proyek_parent_id = [];

            foreach($relevant_peran as $item){
                if($item->tipe_peran == 'satuan_kerja_tahun'){
                    array_push($satuan_kerja_tahun_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'tim'){
                    array_push($tim_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'proyek'){
                    array_push($proyek_parent_id, $item->parent_id);
                }
            }

            $satuan_kerja_tahun_id_child = collect(Satuan_Kerja_Tahun::whereIn('parent_id', $satuan_kerja_tahun_parent_id)->get())->pluck('id')->toArray();
            $satuan_kerja_tahun_parent_id = array_merge($satuan_kerja_tahun_parent_id,  $satuan_kerja_tahun_id_child);

            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where(function ($query) use ($satuan_kerja_tahun_parent_id, $tim_parent_id, $proyek_parent_id){
                    $query->orWhereIn('id', $proyek_parent_id);
                    $query->orWhereIn('tim_id', $tim_parent_id);
                    $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_parent_id);
                })
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $satuan_kerja_tahun_parent_id)
                or in_array($jurnal_harian_proyek->tim_id, $tim_parent_id)
                or in_array($jurnal_harian_proyek->id, $proyek_parent_id) )){
                    return abort(403, 'Tidak memiliki akses untuk melihat');
            }

            $have_auth = true;
        }

        if(!$have_auth){
            return abort(403, 'Tidak memiliki akses untuk melihat');
        }

        if(!$request->limit || !$request->page ){
            return ['daftar' =>  Peran::with('pemilik_user_nama.photo_url')
                    ->where(function ($query0) use ($jurnal_harian_proyek){
                        $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                            $query1->where('tipe_peran', 'tim');
                            $query1->where('peran', 'ketua');
                            $query1->where('parent_id', $jurnal_harian_proyek->tim_id);
                        });
                        $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                            $query1->where('tipe_peran', 'proyek');
                            $query1->where('parent_id', $jurnal_harian_proyek->id);
                        });
                    })
                    ->orderBy('peran_number')
                    ->get()
                , 'total' => Peran::where(function ($query0) use ($jurnal_harian_proyek){
                        $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                            $query1->where('tipe_peran', 'tim');
                            $query1->where('peran', 'ketua');
                            $query1->where('parent_id', $jurnal_harian_proyek->tim_id);
                        });
                        $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                            $query1->where('tipe_peran', 'proyek');
                            $query1->where('parent_id', $jurnal_harian_proyek->id);
                        });
                    })->count()
                ];
        }else{
            $search_term = $request->search_term;
            return ['daftar' =>  Peran::with('pemilik_user_nama.photo_url')
                        ->where(function ($query0) use ($jurnal_harian_proyek){
                            $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                                $query1->where('tipe_peran', 'tim');
                                $query1->where('peran', 'ketua');
                                $query1->where('parent_id', $jurnal_harian_proyek->tim_id);
                            });
                            $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                                $query1->where('tipe_peran', 'proyek');
                                $query1->where('parent_id', $jurnal_harian_proyek->id);
                            });
                        })
                        ->where(function($query_like) use ($search_term){
                            $query_like->where('pemilik_username', 'LIKE', '%'.$search_term.'%')
                                ->orWhere('pemilik_tim_id', 'LIKE', '%'.$search_term.'%')
                                ->orWhere('pemilik_satuan_kerja_tahun_id', 'LIKE', '%'.$search_term.'%');
                        })
                        ->offset(($request->page - 1)*$request->limit)
                        ->limit($request->limit)->get()
                , 'total' => Peran::where(function ($query0) use ($jurnal_harian_proyek){
                            $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                                $query1->where('tipe_peran', 'tim');
                                $query1->where('peran', 'ketua');
                                $query1->where('parent_id', $jurnal_harian_proyek->tim_id);
                            });
                            $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                                $query1->where('tipe_peran', 'proyek');
                                $query1->where('parent_id', $jurnal_harian_proyek->id);
                            });
                        })
                        ->where(function($query_like) use ($search_term){
                                $query_like->where('pemilik_username', 'LIKE', '%'.$search_term.'%')
                                    ->orWhere('pemilik_tim_id', 'LIKE', '%'.$search_term.'%')
                                    ->orWhere('pemilik_satuan_kerja_tahun_id', 'LIKE', '%'.$search_term.'%');
                        })
                        ->count()
                ];
        }

    }

    public function peran_format_unduh(Request $request)
    {
        $peran_super_admin = Auth::user()->peran_super_admin;

        $jurnal_harian_proyek_id = null;
        $jurnal_harian_proyek = null;
        $have_auth = false;
        if(count($peran_super_admin) > 0){
            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            $have_auth = true;
        }else{
            $relevant_peran = Peran::where('pemilik_username', Auth::user()->username)
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
                        $query2->whereIn('peran', ['ketua', 'admin']);
                    });
                })
                ->where('tahun', session('tahun_active'))
                ->select('tipe_peran', 'parent_id')
                ->distinct()
                ->get();

            $satuan_kerja_tahun_parent_id = [];
            $tim_parent_id = [];
            $proyek_parent_id = [];

            foreach($relevant_peran as $item){
                if($item->tipe_peran == 'satuan_kerja_tahun'){
                    array_push($satuan_kerja_tahun_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'tim'){
                    array_push($tim_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'proyek'){
                    array_push($proyek_parent_id, $item->parent_id);
                }
            }

            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where(function ($query) use ($satuan_kerja_tahun_parent_id, $tim_parent_id, $proyek_parent_id){
                    $query->orWhereIn('id', $proyek_parent_id);
                    $query->orWhereIn('tim_id', $tim_parent_id);
                    $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_parent_id);
                })
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $satuan_kerja_tahun_parent_id)
                or in_array($jurnal_harian_proyek->tim_id, $tim_parent_id)
                or in_array($jurnal_harian_proyek->id, $proyek_parent_id) )){
                    return abort(403, 'Tidak memiliki akses untuk melihat');
            }

            $have_auth = true;
        }

        if(!$have_auth){
            return abort(403, 'Tidak memiliki akses untuk melihat');
        }

        $file_awal= Storage::disk('local')->path('/data_awal/'.'peran_proyek.xls');
        $file_akhir= Storage::disk('local')->path('/data_process/'.((string) Uuid::generate()).'.xls');

        copy($file_awal, $file_akhir);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_awal);
        $sheet_user = $spreadsheet->getSheetByName("user_semua");

        $all_user = Peran::with('pemilik_user')->where('parent_id', $jurnal_harian_proyek->satuan_kerja_tahun_id)->get();

        $index = 2;
        foreach($all_user as $item){
            $sheet_user->setCellValue("A".$index, $item->pemilik_user->username);
            $sheet_user->setCellValue("B".$index, $item->pemilik_user->nama);
            $index = $index + 1;
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save($file_akhir);


        $headers = ['Content-Type' => 'application/xls'];

        return Response::download($file_akhir, 'format_peran.xls', $headers)->deleteFileAfterSend(true);
    }

    public function peran_format_unggah(Request $request)
    {
        $peran_super_admin = Auth::user()->peran_super_admin;

        $jurnal_harian_proyek_id = null;
        $jurnal_harian_proyek = null;
        $have_auth = false;
        if(count($peran_super_admin) > 0){
            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            $have_auth = true;
        }else{
            $relevant_peran = Peran::where('pemilik_username', Auth::user()->username)
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
                        $query2->whereIn('peran', ['ketua', 'admin']);
                    });
                })
                ->where('tahun', session('tahun_active'))
                ->select('tipe_peran', 'parent_id')
                ->distinct()
                ->get();

            $satuan_kerja_tahun_parent_id = [];
            $tim_parent_id = [];
            $proyek_parent_id = [];

            foreach($relevant_peran as $item){
                if($item->tipe_peran == 'satuan_kerja_tahun'){
                    array_push($satuan_kerja_tahun_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'tim'){
                    array_push($tim_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'proyek'){
                    array_push($proyek_parent_id, $item->parent_id);
                }
            }

            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where(function ($query) use ($satuan_kerja_tahun_parent_id, $tim_parent_id, $proyek_parent_id){
                    $query->orWhereIn('id', $proyek_parent_id);
                    $query->orWhereIn('tim_id', $tim_parent_id);
                    $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_parent_id);
                })
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $satuan_kerja_tahun_parent_id)
                or in_array($jurnal_harian_proyek->tim_id, $tim_parent_id)
                or in_array($jurnal_harian_proyek->id, $proyek_parent_id) )){
                    return abort(403, 'Tidak memiliki akses untuk melihat');
            }

            $have_auth = true;
        }

        if(!$have_auth){
            return abort(403, 'Tidak memiliki akses untuk melihat');
        }

        DB::beginTransaction();
        try
        {
            $file_name = ((string)Uuid::generate()).'.xls';

            Storage::disk('local')->putFileAs(
                '/data_upload/',
                $request->file,
                $file_name
            );
            $inputFileName = Storage::disk('local')->path("/data_upload/".$file_name);

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($inputFileName);
            $reader->setLoadSheetsOnly('peran');
            $spreadsheet = $reader->load($inputFileName);


            $peran_daftar = Peran::where('tipe_peran', 'proyek')
                    ->where('parent_id', $jurnal_harian_proyek->id)
                    ->get();

            $peran_daftar_key = [];
            $ketua = '';
            foreach($peran_daftar as $item_peran){
                array_push($peran_daftar_key, $jurnal_harian_proyek->id.'_'.$item_peran->pemilik_username.'_'.$item_peran->peran);
                if($item_peran->peran == 'ketua'){
                    $ketua = $item_peran->pemilik_username;
                }
            }

            $pertama = true;
            foreach($spreadsheet->getActiveSheet()->toArray() as $item){
                if($pertama){
                    $pertama = false;
                }else{
                    if(in_array(trim($item[1]), ['ketua', 'admin', 'anggota'])
                        && !in_array($jurnal_harian_proyek->id.'_'.trim($item[0]).'_'.trim($item[1]), $peran_daftar_key)){

                            if(!($item[1] == 'ketua' && $ketua!= '')){
                                $baru =  new Peran;
                                $baru->id =  (string) Uuid::generate();
                                $baru->tipe_peran = 'proyek';
                                $baru->parent_id = $jurnal_harian_proyek->id;
                                $baru->pemilik_username = trim($item[0]);
                                $baru->pemilik_tim_id = null;
                                $baru->pemilik_satuan_kerja_tahun_id = null;
                                $baru->tahun = $jurnal_harian_proyek->tahun;
                                $baru->peran = trim($item[1]);
                                $baru->save();

                                // Pengumuman::buat_untuk_peran_satuan_kerja_tahun(0, $jurnal_harian_proyek, $baru);
                            }
                    }
                }
            }
            unlink($inputFileName);
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function peran_hapus(Request $request, $username)
    {
        DB::beginTransaction();
        try
        {
            $peran_super_admin = Auth::user()->peran_super_admin;

            $jurnal_harian_proyek_id = null;
            $jurnal_harian_proyek = null;
            $have_auth = false;
            if(count($peran_super_admin) > 0){
                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                $have_auth = true;
            }else{
                $relevant_peran = Peran::where('pemilik_username', Auth::user()->username)
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
                            $query2->whereIn('peran', ['ketua', 'admin']);
                        });
                    })
                    ->where('tahun', session('tahun_active'))
                    ->select('tipe_peran', 'parent_id')
                    ->distinct()
                    ->get();

                $satuan_kerja_tahun_parent_id = [];
                $tim_parent_id = [];
                $proyek_parent_id = [];

                foreach($relevant_peran as $item){
                    if($item->tipe_peran == 'satuan_kerja_tahun'){
                        array_push($satuan_kerja_tahun_parent_id, $item->parent_id);
                    }else if($item->tipe_peran == 'tim'){
                        array_push($tim_parent_id, $item->parent_id);
                    }else if($item->tipe_peran == 'proyek'){
                        array_push($proyek_parent_id, $item->parent_id);
                    }
                }

                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where(function ($query) use ($satuan_kerja_tahun_parent_id, $tim_parent_id, $proyek_parent_id){
                        $query->orWhereIn('id', $proyek_parent_id);
                        $query->orWhereIn('tim_id', $tim_parent_id);
                        $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_parent_id);
                    })
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $satuan_kerja_tahun_parent_id)
                    or in_array($jurnal_harian_proyek->tim_id, $tim_parent_id)
                    or in_array($jurnal_harian_proyek->id, $proyek_parent_id) )){
                        return abort(403, 'Tidak memiliki akses untuk melihat');
                }

                $have_auth = true;
            }

            if(!$have_auth){
                return abort(403, 'Tidak memiliki akses untuk melihat');
            }

            $peran = Peran::where('id', $request->route('peran_id'))
                        ->where('parent_id', $jurnal_harian_proyek->id)
                        ->where('tipe_peran', 'proyek')
                        ->first();
            if(!$peran){
                abort(404, 'Id tidak ditemukan');
            }

            $peran->delete();
            DB::commit();
            return $peran;
        }catch(Exception $ex){
            DB::rollBack();
            return abort(500, $ex->getMessage());
        }
    }

    public function item_daftar(Request $request)
    {
        $peran_super_admin = Auth::user()->peran_super_admin;

        $jurnal_harian_proyek_id = null;
        $jurnal_harian_proyek = null;
        $have_auth = false;
        if(count($peran_super_admin) > 0){
            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            $have_auth = true;
        }else{
            $relevant_peran = Peran::where('pemilik_username', Auth::user()->username)
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

            $satuan_kerja_tahun_parent_id = [];
            $tim_parent_id = [];
            $proyek_parent_id = [];

            foreach($relevant_peran as $item){
                if($item->tipe_peran == 'satuan_kerja_tahun'){
                    array_push($satuan_kerja_tahun_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'tim'){
                    array_push($tim_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'proyek'){
                    array_push($proyek_parent_id, $item->parent_id);
                }
            }

            $satuan_kerja_tahun_id_child = collect(Satuan_Kerja_Tahun::whereIn('parent_id', $satuan_kerja_tahun_parent_id)->get())->pluck('id')->toArray();
            $satuan_kerja_tahun_parent_id = array_merge($satuan_kerja_tahun_parent_id,  $satuan_kerja_tahun_id_child);

            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where(function ($query) use ($satuan_kerja_tahun_parent_id, $tim_parent_id, $proyek_parent_id){
                    $query->orWhereIn('id', $proyek_parent_id);
                    $query->orWhereIn('tim_id', $tim_parent_id);
                    $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_parent_id);
                })
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $satuan_kerja_tahun_parent_id)
                or in_array($jurnal_harian_proyek->tim_id, $tim_parent_id)
                or in_array($jurnal_harian_proyek->id, $proyek_parent_id) )){
                    return abort(403, 'Tidak memiliki akses untuk melihat');
            }

            $have_auth = true;
        }

        if(!$have_auth){
            return abort(403, 'Tidak memiliki akses untuk melihat');
        }

        $item_array = [];
        $item_count = 0;

        if(!$request->limit || !$request->page ){
           $item_array = Jurnal_Harian_Item::where('jurnal_harian_proyek_id', $jurnal_harian_proyek->id)
                ->whereIn('type', [1, 2, 3])
                // ->orderBy('tanggal_realisasi', 'desc')
                // ->orderBy('tanggal_deadline', 'desc')
                ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END DESC')
                ->get();
           $item_count = Jurnal_Harian_Item::where('jurnal_harian_proyek_id', $jurnal_harian_proyek->id)
                ->whereIn('type', [1, 2, 3])
                ->count();
        }else{
            $search_term = $request->search_term;
            $selected_bulan = $request->selected_bulan;
            $selected_status = $request->selected_status;

            $item_array_query = Jurnal_Harian_Item::query();
            $item_count_query = Jurnal_Harian_Item::query();

            $item_array_query->where('jurnal_harian_proyek_id', $jurnal_harian_proyek->id);
            $item_count_query->where('jurnal_harian_proyek_id', $jurnal_harian_proyek->id);

            $item_array_query->whereIn('type', [1, 2, 3]);
            $item_count_query->whereIn('type', [1, 2, 3]);

            if($search_term){
                $item_array_query->where(function ($query) use ($search_term){
                    $query->orWhere('nama', 'LIKE', '%'.$search_term.'%');
                    $query->orWhereHas('pelaksana_user_nama', function ($query1)  use ($search_term){
                        $query1->where('nama', 'LIKE', '%'.$search_term.'%');
                    });
                });
                $item_count_query->where(function ($query) use ($search_term){
                    $query->orWhere('nama', 'LIKE', '%'.$search_term.'%');
                    $query->orWhereHas('pelaksana_user_nama', function ($query1)  use ($search_term){
                        $query1->where('nama', 'LIKE', '%'.$search_term.'%');
                    });
                });
            }
            if($selected_bulan>=1 && $selected_bulan<=12){
                $item_array_query->where(function ($query) use ($selected_bulan){
                    $query->orWhereMonth('tanggal_deadline', $selected_bulan);
                    $query->orWhereMonth('tanggal_realisasi', $selected_bulan);
                });
                $item_count_query->where(function ($query) use ($selected_bulan){
                    $query->orWhereMonth('tanggal_deadline', $selected_bulan);
                    $query->orWhereMonth('tanggal_realisasi', $selected_bulan);
                });
            }
            if(in_array($selected_status, ['1','2','3'])){
                $item_array_query->where('status_pekerjaan', intval($selected_status)-1);
                $item_count_query->where('status_pekerjaan', intval($selected_status)-1);
            }
            if(in_array($selected_status, ['4','5','6'])){
                if($selected_status == 4){
                    $item_array_query->whereIn('status_nilai', ['0','1']);
                    $item_count_query->whereIn('status_nilai', ['0','1']);
                }else if($selected_status == 5){
                    $item_array_query->where('status_nilai', '2');
                    $item_count_query->where('status_nilai', '2');
                }else if($selected_status == 6){
                    $item_array_query->where('status_nilai', '3');
                    $item_count_query->where('status_nilai', '3');
                }
            }

            // $item_array_query->orderBy('tanggal_realisasi', 'desc');
            // $item_array_query->orderBy('tanggal_deadline', 'desc');
            $item_array_query->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END DESC');

            $item_array_query->offset(($request->page - 1)*$request->limit);
            $item_array_query->limit($request->limit);

            $item_array = $item_array_query->get();
            $item_count = $item_count_query->count();
        }

        return ['jurnal_harian_proyek' => $jurnal_harian_proyek
            , 'jurnal_harian_item' => $item_array
            , 'jurnal_harian_count' => $item_count
            , 'peran' => Peran::with('pemilik_user_nama')
                ->where(function ($query0) use ($jurnal_harian_proyek){
                    $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                        $query1->where('tipe_peran', 'tim');
                        $query1->where('peran', 'ketua');
                        $query1->where('parent_id', $jurnal_harian_proyek->tim_id);
                    });
                    $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                        $query1->where('tipe_peran', 'proyek');
                        $query1->where('parent_id', $jurnal_harian_proyek->id);
                    });
                })
                ->get()
            ];
    }

    public function item_daftar_personal(Request $request)
    {
        $peran_super_admin = Auth::user()->peran_super_admin;

        $jurnal_harian_proyek_id = null;
        $jurnal_harian_proyek = null;
        $have_auth = false;
        if(count($peran_super_admin) > 0){
            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            $have_auth = true;
        }else{
            $relevant_peran = Peran::where('pemilik_username', Auth::user()->username)
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

            $satuan_kerja_tahun_parent_id = [];
            $tim_parent_id = [];
            $proyek_parent_id = [];

            foreach($relevant_peran as $item){
                if($item->tipe_peran == 'satuan_kerja_tahun'){
                    array_push($satuan_kerja_tahun_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'tim'){
                    array_push($tim_parent_id, $item->parent_id);
                }else if($item->tipe_peran == 'proyek'){
                    array_push($proyek_parent_id, $item->parent_id);
                }
            }

            $satuan_kerja_tahun_id_child = collect(Satuan_Kerja_Tahun::whereIn('parent_id', $satuan_kerja_tahun_parent_id)->get())->pluck('id')->toArray();
            $satuan_kerja_tahun_parent_id = array_merge($satuan_kerja_tahun_parent_id,  $satuan_kerja_tahun_id_child);

            $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
            $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                ->where(function ($query) use ($satuan_kerja_tahun_parent_id, $tim_parent_id, $proyek_parent_id){
                    $query->orWhereIn('id', $proyek_parent_id);
                    $query->orWhereIn('tim_id', $tim_parent_id);
                    $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_parent_id);
                })
                ->where('tahun', session('tahun_active'))
                ->first();

            if(!$jurnal_harian_proyek){
                return abort(404, 'Id tidak ditemukan');
            }

            if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $satuan_kerja_tahun_parent_id)
                or in_array($jurnal_harian_proyek->tim_id, $tim_parent_id)
                or in_array($jurnal_harian_proyek->id, $proyek_parent_id) )){
                    return abort(403, 'Tidak memiliki akses untuk melihat');
            }

            $have_auth = true;
        }

        if(!$have_auth){
            return abort(403, 'Tidak memiliki akses untuk melihat');
        }

        $item_array = [];
        $item_count = 0;

        if(!$request->limit || !$request->page ){
           $item_array = Jurnal_Harian_Item::where('jurnal_harian_proyek_id', $jurnal_harian_proyek->id)
                ->whereIn('type', [0, 4])
                // ->orderBy('tanggal_realisasi', 'desc')
                // ->orderBy('tanggal_deadline', 'desc')
                ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END DESC')
                ->get();
           $item_count = Jurnal_Harian_Item::where('jurnal_harian_proyek_id', $jurnal_harian_proyek->id)
                ->whereIn('type', [0, 4])
                ->count();
        }else{
            $search_term = $request->search_term;
            $selected_bulan = $request->selected_bulan;
            $selected_status = $request->selected_status;

            $item_array_query = Jurnal_Harian_Item::query();
            $item_count_query = Jurnal_Harian_Item::query();

            $item_array_query->where('jurnal_harian_proyek_id', $jurnal_harian_proyek->id);
            $item_count_query->where('jurnal_harian_proyek_id', $jurnal_harian_proyek->id);

            $item_array_query->whereIn('type', [0, 4]);
            $item_count_query->whereIn('type', [0, 4]);

            if($search_term){
                $item_array_query->where(function ($query) use ($search_term){
                    $query->orWhere('nama', 'LIKE', '%'.$search_term.'%');
                    $query->orWhereHas('pelaksana_user_nama', function ($query1)  use ($search_term){
                        $query1->where('nama', 'LIKE', '%'.$search_term.'%');
                    });
                });
                $item_count_query->where(function ($query) use ($search_term){
                    $query->orWhere('nama', 'LIKE', '%'.$search_term.'%');
                    $query->orWhereHas('pelaksana_user_nama', function ($query1)  use ($search_term){
                        $query1->where('nama', 'LIKE', '%'.$search_term.'%');
                    });
                });
            }
            if($selected_bulan>=1 && $selected_bulan<=12){
                $item_array_query->where(function ($query) use ($selected_bulan){
                    $query->orWhereMonth('tanggal_deadline', $selected_bulan);
                    $query->orWhereMonth('tanggal_realisasi', $selected_bulan);
                });
                $item_count_query->where(function ($query) use ($selected_bulan){
                    $query->orWhereMonth('tanggal_deadline', $selected_bulan);
                    $query->orWhereMonth('tanggal_realisasi', $selected_bulan);
                });
            }

            if(in_array($selected_status, ['1','2','3'])){
                $item_array_query->where('status_pekerjaan', intval($selected_status)-1);
                $item_count_query->where('status_pekerjaan', intval($selected_status)-1);
            }
            if(in_array($selected_status, ['4','5','6'])){
                if($selected_status == 4){
                    $item_array_query->whereIn('status_nilai', ['0','1']);
                    $item_count_query->whereIn('status_nilai', ['0','1']);
                }else if($selected_status == 5){
                    $item_array_query->where('status_nilai', '2');
                    $item_count_query->where('status_nilai', '2');
                }else if($selected_status == 6){
                    $item_array_query->where('status_nilai', '3');
                    $item_count_query->where('status_nilai', '3');
                }
            }

            // $item_array_query->orderBy('tanggal_realisasi', 'desc');
            // $item_array_query->orderBy('tanggal_deadline', 'desc');
            $item_array_query->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END DESC');

            $item_array_query->offset(($request->page - 1)*$request->limit);
            $item_array_query->limit($request->limit);

            $item_array = $item_array_query->get();
            $item_count = $item_count_query->count();
        }

        return ['jurnal_harian_proyek' => $jurnal_harian_proyek
            , 'jurnal_harian_item' => $item_array
            , 'jurnal_harian_count' => $item_count
            , 'peran' => Peran::with('pemilik_user_nama')
                ->where(function ($query0) use ($jurnal_harian_proyek){
                    $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                        $query1->where('tipe_peran', 'tim');
                        $query1->where('peran', 'ketua');
                        $query1->where('parent_id', $jurnal_harian_proyek->tim_id);
                    });
                    $query0->orWhere(function ($query1) use ($jurnal_harian_proyek) {
                        $query1->where('tipe_peran', 'proyek');
                        $query1->where('parent_id', $jurnal_harian_proyek->id);
                    });
                })
                ->get()
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
            $peran_super_admin = Auth::user()->peran_super_admin;

            $jurnal_harian_proyek_id = null;
            $jurnal_harian_proyek = null;
            $have_auth = false;
            $have_auth_penilaian_as_ketua = false;
            $pelaksana_is_in_proyek_or_ketua_tim = false;
            if(count($peran_super_admin) > 0){
                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                $have_auth = true;
                $have_auth_penilaian_as_ketua = true;
                $peran_pelaksana = Peran::where(function($query1) use ($jurnal_harian_proyek) {
                        $query1->orWhere(function($query2) use ($jurnal_harian_proyek) {
                            $query2->where('tipe_peran', 'tim');
                            $query2->where('peran', 'ketua');
                            $query2->where('parent_id', $jurnal_harian_proyek->tim_id);
                        });
                        $query1->orWhere(function($query2) use ($jurnal_harian_proyek) {
                            $query2->where('tipe_peran', 'proyek');
                            $query2->where('parent_id', $jurnal_harian_proyek->id);
                        });
                    })
                    ->whereNotNull('parent_id')
                    ->where('pemilik_username', $request->input('pelaksana_username'))
                    ->where('tahun', session('tahun_active'))
                    ->get();
                if(count($peran_pelaksana) > 0){
                    $pelaksana_is_in_proyek_or_ketua_tim = true;
                }
            }else{
                $peran_arr_ = Peran::where('pemilik_username', Auth::user()->username)
                    ->where(function ($query){
                        $query->where(function ($query2){
                            $query2->where('tipe_peran', 'satuan_kerja_tahun');
                            $query2->whereIn('peran', ['pimpinan']);
                        });
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'tim');
                            $query2->whereIn('peran', ['ketua']);
                        });
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'proyek');
                        });
                    })
                    ->where('tahun', session('tahun_active'))
                    ->select('tipe_peran', 'parent_id', 'peran')
                    ->distinct()
                    ->get();
                $parent_id_arr = collect($peran_arr_)->pluck('parent_id')->toArray();

                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where(function ($query) use ($parent_id_arr){
                        $query->whereIn('id', $parent_id_arr);
                        $query->orWhereIn('tim_id', $parent_id_arr);
                        $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_arr);
                    })
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $parent_id_arr)
                    or in_array($jurnal_harian_proyek->tim_id, $parent_id_arr)
                    or in_array($jurnal_harian_proyek->id, $parent_id_arr) )){
                        return abort(403, 'Tidak memiliki akses untuk melihat');
                }

                $have_auth = true;

                $akses_filtered_as_ketua__satuan_kerja_tahun = collect($peran_arr_)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'pimpinan' && $value['tipe_peran'] == 'satuan_kerja_tahun'
                        && $value['parent_id'] == $jurnal_harian_proyek->satuan_kerja_tahun_id;
                });
                $akses_filtered_as_ketua__tim = collect($peran_arr_)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'ketua' && $value['tipe_peran'] == 'tim'
                        && $value['parent_id'] == $jurnal_harian_proyek->tim_id;
                });
                $akses_filtered_as_ketua__proyek = collect($peran_arr_)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'ketua'  && $value['tipe_peran'] == 'proyek'
                        && $value['parent_id'] == $jurnal_harian_proyek->id;
                });

                $peran_untuk_cek_nilai = Peran::where(function($query1) use ($jurnal_harian_proyek) {
                        $query1->orWhere(function($query2) use ($jurnal_harian_proyek) {
                            $query2->where('tipe_peran', 'satuan_kerja_tahun');
                            $query2->where('peran', 'pimpinan');
                            $query2->where('parent_id', $jurnal_harian_proyek->satuan_kerja_tahun_id);
                        });
                        $query1->orWhere(function($query2) use ($jurnal_harian_proyek) {
                            $query2->where('tipe_peran', 'tim');
                            $query2->where('peran', 'ketua');
                            $query2->where('parent_id', $jurnal_harian_proyek->tim_id);
                        });
                        $query1->orWhere(function($query2) use ($jurnal_harian_proyek) {
                            $query2->where('tipe_peran', 'proyek');
                            $query2->where('parent_id', $jurnal_harian_proyek->id);
                        });
                    })
                    ->whereNotNull('parent_id')
                    ->where('pemilik_username', $request->input('pelaksana_username'))
                    ->where('tahun', session('tahun_active'))
                    ->get();

                $pelaksana_filtered_as_ketua__satuan_kerja_tahun = collect($peran_untuk_cek_nilai)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'pimpinan' && $value['tipe_peran'] == 'satuan_kerja_tahun'
                        && $value['parent_id'] == $jurnal_harian_proyek->satuan_kerja_tahun_id;
                });
                $pelaksana_filtered_as_ketua__tim = collect($peran_untuk_cek_nilai)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'ketua' && $value['tipe_peran'] == 'tim'
                        && $value['parent_id'] == $jurnal_harian_proyek->tim_id;
                });
                $pelaksana_filtered_as_ketua__proyek = collect($peran_untuk_cek_nilai)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'ketua' && $value['tipe_peran'] == 'proyek'
                        && $value['parent_id'] == $jurnal_harian_proyek->id;
                });

                $pelaksana_filtered_as_in__proyek = collect($peran_untuk_cek_nilai)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return  $value['tipe_peran'] == 'proyek' && $value['parent_id'] == $jurnal_harian_proyek->id ;
                });

                $have_auth_penilaian_as_ketua = false;

                if(count($akses_filtered_as_ketua__satuan_kerja_tahun) > 0){
                    if($request->input('pelaksana_username') == Auth::user()->username
                        || count($pelaksana_filtered_as_ketua__tim) > 0 ){
                            $have_auth_penilaian_as_ketua = true;
                    }
                }

                if(count($akses_filtered_as_ketua__tim) > 0 ){
                    if($request->input('pelaksana_username') != Auth::user()->username && count($pelaksana_filtered_as_ketua__proyek) > 0){
                        if(count($pelaksana_filtered_as_ketua__satuan_kerja_tahun) == 0 && count($pelaksana_filtered_as_ketua__tim) == 0){
                            $have_auth_penilaian_as_ketua = true;
                        }
                    }
                }

                if(count($akses_filtered_as_ketua__proyek) > 0 ){
                    if($request->input('pelaksana_username') != Auth::user()->username){
                        if(count($pelaksana_filtered_as_ketua__satuan_kerja_tahun) == 0
                            && count($pelaksana_filtered_as_ketua__tim) == 0
                            && count($pelaksana_filtered_as_ketua__proyek) == 0){
                            $have_auth_penilaian_as_ketua = true;
                        }
                    }
                }
                if(count($pelaksana_filtered_as_in__proyek) > 0 || count($pelaksana_filtered_as_ketua__tim) > 0){
                    $pelaksana_is_in_proyek_or_ketua_tim = true;
                }
            }

            if(!$have_auth){
                return abort(403, 'Tidak memiliki akses untuk melihat');
            }

            $type = null;
            if(!is_null($jurnal_harian_proyek->satuan_kerja_tahun_id)
                && !is_null($jurnal_harian_proyek->tim_id)){
                    $type = 1;
            }else if(!is_null($jurnal_harian_proyek->satuan_kerja_tahun_id)
                && is_null($jurnal_harian_proyek->tim_id)){
                    $type = 3;
            }
            if(is_null($type)){
                return abort(500, 'Terjadi kesalahan');
            }

            if(!$pelaksana_is_in_proyek_or_ketua_tim){
                return abort(500, 'Peran tidak ditemukan untuk pelaksana user');
            }

            $baru = Jurnal_Harian_Item::helper_tambah_with_proyek($type, $jurnal_harian_proyek, $request, $have_auth_penilaian_as_ketua);
            DB::commit();
            return $baru;
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
                return abort(400, "Tanggal realisasi harus sebelum atau hari ini");
            }
        }
        if($request->input('target') == 0){
            throw new Exception("Target tidak boleh kosong");
        }

        DB::beginTransaction();
        try
        {
            $peran_super_admin = Auth::user()->peran_super_admin;

            $jurnal_harian_proyek_id = null;
            $jurnal_harian_proyek = null;
            $have_auth = false;
            $have_auth_penilaian_as_ketua = false;
            $pelaksana_is_in_proyek_or_ketua_tim = false;
            if(count($peran_super_admin) > 0){
                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                $have_auth = true;
                $have_auth_penilaian_as_ketua = true;
                $peran_pelaksana = Peran::where(function($query1) use ($jurnal_harian_proyek) {
                        $query1->orWhere(function($query2) use ($jurnal_harian_proyek) {
                            $query2->where('tipe_peran', 'tim');
                            $query2->where('peran', 'ketua');
                            $query2->where('parent_id', $jurnal_harian_proyek->tim_id);
                        });
                        $query1->orWhere(function($query2) use ($jurnal_harian_proyek) {
                            $query2->where('tipe_peran', 'proyek');
                            $query2->where('parent_id', $jurnal_harian_proyek->id);
                        });
                    })
                    ->whereNotNull('parent_id')
                    ->where('pemilik_username', $request->input('pelaksana_username'))
                    ->where('tahun', session('tahun_active'))
                    ->get();
                if(count($peran_pelaksana) > 0){
                    $pelaksana_is_in_proyek_or_ketua_tim = true;
                }
            }else{
                $peran_arr_ = Peran::where('pemilik_username', Auth::user()->username)
                    ->where(function ($query){
                        $query->where(function ($query2){
                            $query2->where('tipe_peran', 'satuan_kerja_tahun');
                            $query2->whereIn('peran', ['pimpinan']);
                        });
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'tim');
                            $query2->whereIn('peran', ['ketua']);
                        });
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'proyek');
                        });
                    })
                    ->where('tahun', session('tahun_active'))
                    ->select('tipe_peran', 'parent_id', 'peran')
                    ->distinct()
                    ->get();
                $parent_id_arr = collect($peran_arr_)->pluck('parent_id')->toArray();

                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where(function ($query) use ($parent_id_arr){
                        $query->whereIn('id', $parent_id_arr);
                        $query->orWhereIn('tim_id', $parent_id_arr);
                        $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_arr);
                    })
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $parent_id_arr)
                    or in_array($jurnal_harian_proyek->tim_id, $parent_id_arr)
                    or in_array($jurnal_harian_proyek->id, $parent_id_arr) )){
                        return abort(403, 'Tidak memiliki akses untuk melihat');
                }

                $have_auth = true;

                $akses_filtered_as_ketua__satuan_kerja_tahun = collect($peran_arr_)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'pimpinan' && $value['tipe_peran'] == 'satuan_kerja_tahun'
                        && $value['parent_id'] == $jurnal_harian_proyek->satuan_kerja_tahun_id;
                });
                $akses_filtered_as_ketua__tim = collect($peran_arr_)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'ketua' && $value['tipe_peran'] == 'tim'
                        && $value['parent_id'] == $jurnal_harian_proyek->tim_id;
                });
                $akses_filtered_as_ketua__proyek = collect($peran_arr_)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'ketua'  && $value['tipe_peran'] == 'proyek'
                        && $value['parent_id'] == $jurnal_harian_proyek->id;
                });

                $peran_untuk_cek_nilai = Peran::where(function($query1) use ($jurnal_harian_proyek) {
                        $query1->orWhere(function($query2) use ($jurnal_harian_proyek) {
                            $query2->where('tipe_peran', 'satuan_kerja_tahun');
                            $query2->where('peran', 'pimpinan');
                            $query2->where('parent_id', $jurnal_harian_proyek->satuan_kerja_tahun_id);
                        });
                        $query1->orWhere(function($query2) use ($jurnal_harian_proyek) {
                            $query2->where('tipe_peran', 'tim');
                            $query2->where('peran', 'ketua');
                            $query2->where('parent_id', $jurnal_harian_proyek->tim_id);
                        });
                        $query1->orWhere(function($query2) use ($jurnal_harian_proyek) {
                            $query2->where('tipe_peran', 'proyek');
                            $query2->where('parent_id', $jurnal_harian_proyek->id);
                        });
                    })
                    ->whereNotNull('parent_id')
                    ->where('pemilik_username', $request->input('pelaksana_username'))
                    ->where('tahun', session('tahun_active'))
                    ->get();

                $pelaksana_filtered_as_ketua__satuan_kerja_tahun = collect($peran_untuk_cek_nilai)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'pimpinan' && $value['tipe_peran'] == 'satuan_kerja_tahun'
                        && $value['parent_id'] == $jurnal_harian_proyek->satuan_kerja_tahun_id;
                });
                $pelaksana_filtered_as_ketua__tim = collect($peran_untuk_cek_nilai)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'ketua' && $value['tipe_peran'] == 'tim'
                        && $value['parent_id'] == $jurnal_harian_proyek->tim_id;
                });
                $pelaksana_filtered_as_ketua__proyek = collect($peran_untuk_cek_nilai)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return $value['peran'] == 'ketua' && $value['tipe_peran'] == 'proyek'
                        && $value['parent_id'] == $jurnal_harian_proyek->id;
                });

                $pelaksana_filtered_as_in__proyek = collect($peran_untuk_cek_nilai)->filter(function ($value, int $key) use($jurnal_harian_proyek) {
                    return  $value['tipe_peran'] == 'proyek' && $value['parent_id'] == $jurnal_harian_proyek->id ;
                });

                $have_auth_penilaian_as_ketua = false;

                if(count($akses_filtered_as_ketua__satuan_kerja_tahun) > 0){
                    if($request->input('pelaksana_username') == Auth::user()->username
                        || count($pelaksana_filtered_as_ketua__tim) > 0 ){
                            $have_auth_penilaian_as_ketua = true;
                    }
                }

                if(count($akses_filtered_as_ketua__tim) > 0 ){
                    if($request->input('pelaksana_username') != Auth::user()->username && count($pelaksana_filtered_as_ketua__proyek) > 0){
                        if(count($pelaksana_filtered_as_ketua__satuan_kerja_tahun) == 0 && count($pelaksana_filtered_as_ketua__tim) == 0){
                            $have_auth_penilaian_as_ketua = true;
                        }
                    }
                }

                if(count($akses_filtered_as_ketua__proyek) > 0 ){
                    if($request->input('pelaksana_username') != Auth::user()->username){
                        if(count($pelaksana_filtered_as_ketua__satuan_kerja_tahun) == 0
                            && count($pelaksana_filtered_as_ketua__tim) == 0
                            && count($pelaksana_filtered_as_ketua__proyek) == 0){
                            $have_auth_penilaian_as_ketua = true;
                        }
                    }
                }
                if(count($pelaksana_filtered_as_in__proyek) > 0 || count($pelaksana_filtered_as_ketua__tim) > 0){
                    $pelaksana_is_in_proyek_or_ketua_tim = true;
                }
            }

            if(!$have_auth){
                return abort(403, 'Tidak memiliki akses untuk melihat');
            }

            $jurnal_harian_item_id = $request->route('jurnal_harian_item_id');
            $jurnal_harian_item = Jurnal_Harian_Item::where('id', $jurnal_harian_item_id)
                                    ->where('jurnal_harian_proyek_id', $jurnal_harian_proyek_id)
                                    ->first();
            if(!$jurnal_harian_item){
                return abort(404, 'Id Item tidak ditemukan');
            }

            if(!$pelaksana_is_in_proyek_or_ketua_tim){
                return abort(500, 'Peran tidak ditemukan untuk pelaksana user');
            }

            $jurnal_harian_item->helper_ubah_with_proyek($request, $have_auth_penilaian_as_ketua);
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
            $peran_super_admin = Auth::user()->peran_super_admin;

            $jurnal_harian_proyek_id = null;
            $jurnal_harian_proyek = null;
            $have_auth = false;
            if(count($peran_super_admin) > 0){
                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                $have_auth = true;
            }else{
                $parent_id_arr = collect(Peran::where('pemilik_username', Auth::user()->username)
                    ->where(function ($query){
                        $query->where(function ($query2){
                            $query2->where('tipe_peran', 'satuan_kerja_tahun');
                            $query2->whereIn('peran', ['pimpinan']);
                        });
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'tim');
                            $query2->whereIn('peran', ['ketua']);
                        });
                        $query->orWhere(function ($query2){
                            $query2->where('tipe_peran', 'proyek');
                            $query2->whereIn('peran', ['ketua', 'admin']);
                        });
                    })
                    ->where('tahun', session('tahun_active'))
                    ->select('parent_id')
                    ->distinct()
                    ->get()
                )->pluck('parent_id')->toArray();

                $jurnal_harian_proyek_id = $request->route('jurnal_harian_proyek_id');
                $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('id', $jurnal_harian_proyek_id)
                    ->where(function ($query) use ($parent_id_arr){
                        $query->whereIn('id', $parent_id_arr);
                        $query->orWhereIn('tim_id', $parent_id_arr);
                        $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_arr);
                    })
                    ->where('tahun', session('tahun_active'))
                    ->first();

                if(!$jurnal_harian_proyek){
                    return abort(404, 'Id tidak ditemukan');
                }

                if(! (in_array($jurnal_harian_proyek->satuan_kerja_tahun_id, $parent_id_arr)
                    or in_array($jurnal_harian_proyek->tim_id, $parent_id_arr)
                    or in_array($jurnal_harian_proyek->id, $parent_id_arr) )){
                        return abort(403, 'Tidak memiliki akses untuk melihat');
                }

                $have_auth = true;
            }

            if(!$have_auth){
                return abort(403, 'Tidak memiliki akses untuk melihat');
            }

            $jurnal_harian_item_id = $request->route('jurnal_harian_item_id');
            $jurnal_harian_item = Jurnal_Harian_Item::where('id', $jurnal_harian_item_id)
                                    ->where('jurnal_harian_proyek_id', $jurnal_harian_proyek_id)
                                    ->first();
            if(!$jurnal_harian_item){
                return abort(404, 'Id Item tidak ditemukan');
            }
            $jurnal_harian_item->delete();

            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

}
