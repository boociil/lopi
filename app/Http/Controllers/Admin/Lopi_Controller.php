<?php

namespace App\Http\Controllers\Admin;

use App\Model\Umum\Satuan_Kerja;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Peran;

use App\Http\Controllers\Controller;
use App\Model\Umum\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Exception;
use Inertia\Inertia;

class Lopi_Controller extends Controller
{
    public function index(Request $request)
    {
        $peran_admin_global = Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'admin')
            ->where('tahun', null)
            ->get();

        if(count($peran_admin_global) == 0){
            return abort(401);
        }

        return Inertia::render('peran', [
            "peranZoom_" => Peran::whereIn('peran', ['zoom', 'ruang'])->where('tahun', session('tahun_active'))->get(),
            "satuan_kerja_tahun_" => Satuan_Kerja_Tahun::with(['peran_daftar'])->where('tahun', session('tahun_active'))->get(),
            "user_" => User::all(),
        ]);
    }

    public function getUser(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'required|string',
            'pemilik_username' => 'required|string',
            'peran' => ['string', Rule::in(['zoom', 'ruang'])]
        ]);

        $peran_arr = Peran::with(['pemilik_user'])
            ->where('', $request->input('parent_id'))
            ->get();

        $user_peran = [];
        $user_peran_username = [];

        foreach($peran_arr as $item_peran){
            if(!in_array($item_peran->pemilik_username, $user_peran_username)){
                array_push($user_peran, $item_peran->pemilik_user);
                array_push($user_peran_username, $item_peran->pemilik_username);
            }
        }

        return $user_peran;
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'required|string',
            'pemilik_username' => 'required|string',
            'peran' => ['string', Rule::in(['zoom', 'ruang'])]
        ]);

        $peran_admin_global = Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'admin')
            ->where('tahun', null)
            ->get();

        if(count($peran_admin_global) == 0){
            return abort(401);
        }

        if($request->input('peran') == 'zoom'){
            $parent = Satuan_Kerja_Tahun::where('id', $request->input('parent_id'))
                ->where('kode_satuan_kerja', '7600')
                ->first();
            if(!$parent){
                return abort(401);
            }
        }else if($request->input('peran') == 'ruang'){
            $parent = Satuan_Kerja_Tahun::where('id', $request->input('parent_id'))
                ->first();
            if(!$parent){
                return abort(401);
            }
        }else{
            return abort(401);
        }

        $baru = new Peran();
        $baru->id =  Str::uuid();
        $baru->tipe_peran = 'satuan_kerja_tahun';
        $baru->parent_id = $request->input('parent_id');
        $baru->pemilik_username = $request->input('pemilik_username');
        $baru->pemilik_tim_id = null;
        $baru->pemilik_satuan_kerja_tahun_id = null;
        $baru->tahun = session('tahun_active');
        $baru->peran = $request->input('pemilik_username');
        $baru->peran_number = 16;
        $baru->save();


        DB::beginTransaction();
        try
        {
            $satuan_kerja = Satuan_Kerja::helper_getByUUID($request->satuan_kerja_id)->first();
            if(!$satuan_kerja){
                abort(404, 'Id tidak ditemukan');
            }
            $baru = Satuan_Kerja_Tahun::helper_tambah($satuan_kerja, $request);
            DB::commit();
            return $baru;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }

        redirect()->route('zoom-index');
    }

    public function hapus(Request $request, $peran_id)
    {
        $peran_admin_global = Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'admin')
            ->where('tahun', null)
            ->get();

        if(count($peran_admin_global) == 0){
            return abort(401);
        }

        Peran::where('id', $peran_id)->delete();

        redirect()->route('zoom-index');
    }
}
