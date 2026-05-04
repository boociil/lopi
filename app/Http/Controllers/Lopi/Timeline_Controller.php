<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Rumah_Link;
use App\Model\Lopi\Timeline;
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

        $parent_id_arr_before_pluck = Peran::where('pemilik_username', Auth::user()->username)
            ->whereIn('tipe_peran', ['satuan_kerja_tahun', 'tim'])
            ->where('tahun', session('tahun_active'))
            ->select('tipe_peran', 'parent_id', 'peran')
            ->distinct()
            ->get();

        $parent_id_satuan_kerja_tahun = [];
        $parent_id_tim = [];

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
            }
        }

        $satuan_kerja_tahun = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                    ->where(function($query) use ($tambahan_satuan_kerja_tahun_id){
                        $query->orWhereIn('id', $tambahan_satuan_kerja_tahun_id);
                    })
                    ->get();

        $tim = Tim::where('tahun', session('tahun_active'))
                    ->where(function ($query) use ($tambahan_satuan_kerja_tahun_id, $tambahan_tim_id){
                        $query->orWhereIn('satuan_kerja_tahun_id', $tambahan_satuan_kerja_tahun_id);
                        $query->orWhereIn('id', $tambahan_tim_id);
                    })
                    ->get();


        $data_ori = Timeline::where(function($query) use ($tambahan_satuan_kerja_tahun_id){
                        $query->orWhereIn('satuan_kerja_tahun_id', $tambahan_satuan_kerja_tahun_id);
                        $query->orWhere('access_id', Auth::user()->username);
                    })->get();

        $data_return = [];

        foreach($data_ori as $item_ori){
            if($item_ori->access_type == 'satuan_kerja_tahun'
                && (in_array($item_ori->access_id, $tambahan_satuan_kerja_tahun_id))){
                array_push($data_return, $item_ori);
            }else if($item_ori->access_type == 'tim'
                && (in_array($item_ori->access_id, $tambahan_tim_id))){
                array_push($data_return, $item_ori);
            }else if($item_ori->access_type == 'user' && $item_ori->access_id == Auth::user()->username){
                array_push($data_return, $item_ori);
            }
        }

        $array_can_add_ubah = array_merge($parent_id_satuan_kerja_tahun, $parent_id_tim);


         return Inertia::render('timeline', [
                "data" => $data_return,
                'satuan_kerja_tahun' => $satuan_kerja_tahun,
                'tim' => $tim,
                "can_auth" => $array_can_add_ubah,
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

        $can_item_added= $parent_id_arr;
        array_push($can_item_added, Auth::user()->username);

        if(!in_array($request->input('access_id'),$can_item_added)){
            return abort(401, 'Tidak memiliki akses');
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

    public function ubah(Request $request, Rumah_Link $rumah_link)
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

        $can_item_added= $parent_id_arr;
        array_push($can_item_added, Auth::user()->username);

        if(!in_array($rumah_link->access_id, $can_item_added)){
            return abort(401, 'Tidak memiliki akses');
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

    public function hapus(Request $request, Rumah_Link $rumah_link)
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

        $can_item_added= $parent_id_arr;
        array_push($can_item_added, Auth::user()->username);

        if(!in_array($rumah_link->access_id, $can_item_added)){
            return abort(401, 'Tidak memiliki akses');
        }


        $rumah_link->delete();
        return 1;
    }

}
