<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Jurnal_Harian\Jurnal_Harian_Proyek;
use App\Model\Lopi\Rumah_Link;
use App\Model\Lopi\User_Setting;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

use Exception;

class Timeline_Controller extends Controller
{

    public function index(Request $request)
    {

        $all_satuan_kerja_tahun_id_sesuai_peran = Peran:: where('tipe_peran', 'satuan_kerja_tahun')
                                            ->where('tahun', session('tahun_active'))->get()
                                            ->where('pemilik_username', Auth::user()->username)
                                            ->pluck('parent_id')->toArray();
        $all_tim_id_sesuai_peran_satuan_kerja_tahun = Tim::whereIn('satuan_kerja_tahun_id', $all_satuan_kerja_tahun_id_sesuai_peran)
                                            ->get()
                                            ->pluck('id')
                                            ->toArray();
        $all_proyek_id_sesuai_peran_satuan_kerja_tahun = Jurnal_Harian_Proyek::whereIn('satuan_kerja_tahun_id', $all_satuan_kerja_tahun_id_sesuai_peran)
                                            ->get()
                                            ->pluck('id')
                                            ->toArray();

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
                if(in_array($item->parent_id, $all_satuan_kerja_tahun_id_sesuai_peran)){
                    if(in_array($item->peran, ['pimpinan', 'admin'])){
                        array_push($parent_id_satuan_kerja_tahun, $item->parent_id);
                    }
                    array_push($tambahan_satuan_kerja_tahun_id, $item->parent_id);
                }
            }else if($item->tipe_peran == 'tim'){
                if(in_array($item->parent_id, $all_tim_id_sesuai_peran_satuan_kerja_tahun)){
                    if(in_array($item->peran, ['ketua', 'admin'])){
                        array_push($parent_id_tim, $item->parent_id);
                    }
                    array_push($tambahan_tim_id, $item->parent_id);
                }
            }else if($item->tipe_peran == 'proyek'){
                if(in_array($item->parent_id, $all_proyek_id_sesuai_peran_satuan_kerja_tahun)){
                    array_push($parent_id_proyek, $item->parent_id);
                }
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


        $data_ori = Rumah_Link::where(function($query) use ($tambahan_satuan_kerja_tahun_id, $satuan_kerja_tahun_id_from_proyek){
                        $query->orWhereIn('satuan_kerja_tahun_id', $tambahan_satuan_kerja_tahun_id);
                        $query->orWhereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_id_from_proyek);
                        $query->orWhere('access_id', Auth::user()->username);
                    })->get();

        $data_return = [];

        foreach($data_ori as $item_ori){
            if($item_ori->access_type == 'satuan_kerja_tahun'
                && (in_array($item_ori->access_id, $satuan_kerja_tahun_id_from_proyek) || in_array($item_ori->access_id, $tambahan_satuan_kerja_tahun_id))){
                array_push($data_return, $item_ori);
            }else if($item_ori->access_type == 'tim'
                && (in_array($item_ori->access_id, $tim_id_from_proyek) || in_array($item_ori->access_id, $tambahan_tim_id))){
                array_push($data_return, $item_ori);
            }else if($item_ori->access_type == 'proyek' && (in_array($item_ori->access_id, $parent_id_proyek))){
                array_push($data_return, $item_ori);
            }else if($item_ori->access_type == 'user' && $item_ori->access_id == Auth::user()->username){
                array_push($data_return, $item_ori);
            }
        }


         return Inertia::render('rumah_link', [
                "data" => $data_return,
                'satuan_kerja_tahun' => $satuan_kerja_tahun,
                'tim' => $tim,
                'proyek' => $proyek,
                'setting_pinned' => User_Setting::where('username', Auth::user()->username)
                    ->where('category', 'rumah_link')
                    ->where('key', 'pinned')
                    ->whereIn('satuan_kerja_tahun_id',  $all_satuan_kerja_tahun_id_sesuai_peran)
                    ->first(),
                'setting_sort' => User_Setting::where('username', Auth::user()->username)
                    ->where('category', 'rumah_link')
                    ->where('key', 'sort')
                    ->whereIn('satuan_kerja_tahun_id',  $all_satuan_kerja_tahun_id_sesuai_peran)
                    ->first()
            ]);
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:256',
            'url' => 'required|url|max:1024',
            'color' => 'nullable|max:25',
            'icon' => 'required|max:100',
            'access_type' => 'required|in:satuan_kerja_tahun,tim,proyek,user',
            'access_id' => 'required|max:100',
            'default_pin' => 'in:0,1',
        ]);

        $satuan_kerja_tahun_kode = [];
        $peran_satuan_kerja = Peran::get_my_peran_satuan_kerja_kerja_tahun();

        foreach($peran_satuan_kerja as $item){
            if(!in_array($item->parent_id, $satuan_kerja_tahun_kode)){
                array_push($satuan_kerja_tahun_kode, $item->parent_id);
            }
        }

        if(count($satuan_kerja_tahun_kode) != 1){
            return abort(401);
        }

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

        $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)
            ->where(function ($query) use ($parent_id_arr){
                $query->whereIn('id', $parent_id_arr);
                $query->orWhereIn('tim_id', $parent_id_arr);
                $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_arr);
            })
            ->where('tahun', session('tahun_active'))
            ->get();

        $can_item_added = [Auth::user()->username];

        foreach($jurnal_harian_proyek as $item){
            array_push($can_item_added, $item->satuan_kerja_tahun_id);
            array_push($can_item_added, $item->tim_id);
            array_push($can_item_added, $item->id);
        }

        if(!in_array($request->input('access_id'),$can_item_added)){
            return abort(404, 'Id tidak ditemukan');
        }

        DB::beginTransaction();
        try
        {

            $baru = new Rumah_Link();
            $baru->id = Str::uuid();
            $baru->title = $request->input('title');
            $baru->url = $request->input('url');
            $baru->color = $request->input('color');
            $baru->icon = $request->input('icon');
            $baru->access_type = $request->input('access_type');
            $baru->access_id = $request->input('access_id');
            $baru->default_pin = $request->input('default_pin');
            $baru->pembuat_username = Auth::user()->username;
            $baru->satuan_kerja_tahun_id = $satuan_kerja_tahun_kode[0];
            $baru->save();

            DB::commit();
            return [
                "_status" => 'Berhasil membuat link!',
                "data" => $baru,
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal membuat link!',
            ]);
        }
    }

    public function ubah(Request $request, $rumah_link)
    {
        $validated = $request->validate([
            'title' => 'required|max:256',
            'url' => 'required|url|max:1024',
            'color' => 'nullable|max:25',
            'icon' => 'required|max:100',
            'default_pin' => 'in:0,1',
        ]);

        $satuan_kerja_tahun_kode = [];
        $peran_satuan_kerja = Peran::get_my_peran_satuan_kerja_kerja_tahun();

        foreach($peran_satuan_kerja as $item){
            if(!in_array($item->parent_id, $satuan_kerja_tahun_kode)){
                array_push($satuan_kerja_tahun_kode, $item->parent_id);
            }
        }

        if(count($satuan_kerja_tahun_kode) != 1){
            return abort(401);
        }

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

         $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)
            ->where(function ($query) use ($parent_id_arr){
                $query->whereIn('id', $parent_id_arr);
                $query->orWhereIn('tim_id', $parent_id_arr);
                $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_arr);
            })
            ->where('tahun', session('tahun_active'))
            ->get();

        $can_item_added = [Auth::user()->username];

        foreach($jurnal_harian_proyek as $item){
            array_push($can_item_added, $item->satuan_kerja_tahun_id);
            array_push($can_item_added, $item->tim_id);
            array_push($can_item_added, $item->id);
        }

        if(!in_array($rumah_link->access_id, $can_item_added)){
            return abort(404, 'Id tidak ditemukan');
        }

        DB::beginTransaction();
        try
        {

            $rumah_link->title = $request->input('title');
            $rumah_link->url = $request->input('url');
            $rumah_link->color = $request->input('color');
            $rumah_link->icon = $request->input('icon');
            $rumah_link->default_pin = $request->input('default_pin');

            $rumah_link->save();

            DB::commit();
            return [
                "_status" => 'Berhasil membuat link!',
                "data" => $rumah_link,
            ];

        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal membuat peminjaman zoom!',
            ]);
        }
    }

    public function hapus(Request $request, $rumah_link)
    {
        $satuan_kerja_tahun_kode = [];
        $peran_satuan_kerja = Peran::get_my_peran_satuan_kerja_kerja_tahun();

        foreach($peran_satuan_kerja as $item){
            if(!in_array($item->parent_id, $satuan_kerja_tahun_kode)){
                array_push($satuan_kerja_tahun_kode, $item->parent_id);
            }
        }

        if(count($satuan_kerja_tahun_kode) != 1){
            return abort(401);
        }

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

        $jurnal_harian_proyek = Jurnal_Harian_Proyek::where('pemilik_username', Auth::user()->username)
            ->where('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)
            ->where(function ($query) use ($parent_id_arr){
                $query->whereIn('id', $parent_id_arr);
                $query->orWhereIn('tim_id', $parent_id_arr);
                $query->orWhereIn('satuan_kerja_tahun_id', $parent_id_arr);
            })
            ->where('tahun', session('tahun_active'))
            ->first();

        $can_item_added = [Auth::user()->username];

        foreach($jurnal_harian_proyek as $item){
            array_push($can_item_added, $item->satuan_kerja_tahun_id);
            array_push($can_item_added, $item->tim_id);
            array_push($can_item_added, $item->id);
        }

        if(!in_array($rumah_link->access_id, $can_item_added)){
            return abort(404, 'Id tidak ditemukan');
        }


        $rumah_link->delete();
    }

    public function setting(Request $request)
    {
        $satuan_kerja_tahun_kode = [];
        $peran_satuan_kerja = Peran::get_my_peran_satuan_kerja_kerja_tahun();

        foreach($peran_satuan_kerja as $item){
            if(!in_array($item->parent_id, $satuan_kerja_tahun_kode)){
                array_push($satuan_kerja_tahun_kode, $item->parent_id);
            }
        }

        if(count($satuan_kerja_tahun_kode) != 1){
            return abort(401);
        }

        DB::beginTransaction();
        try
        {

            User_Setting::where('username', Auth::user()->username)
                ->where('category', 'rumah_link')
                ->whereIn('key', ['sort', 'pinned'])
                ->whereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)
                ->delete();



            $baru = new User_Setting();
            $baru->id =  Str::uuid();
            $baru->username = Auth::user()->username;
            $baru->category = 'rumah_link';
            $baru->key = 'sort';
            $baru->data = $request->input('data_sort');
            $baru->satuan_kerja_tahun_id = $satuan_kerja_tahun_kode[0];
            $baru->save();

            $baru = new User_Setting();
            $baru->id =  Str::uuid();
            $baru->username = Auth::user()->username;
            $baru->category = 'rumah_link';
            $baru->key = 'pinned';
            $baru->data = $request->input('data_pinned');
            $baru->satuan_kerja_tahun_id = $satuan_kerja_tahun_kode[0];
            $baru->save();

            DB::commit();
            return [
                "_status" => 'Berhasil membuat peminjaman zoom!',
                "data" => $baru,
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal membuat peminjaman zoom!',
            ]);
        }
    }

}
