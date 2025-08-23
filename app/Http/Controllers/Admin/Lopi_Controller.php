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
use Illuminate\Validation\ValidationException;
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
            "peranLopi" => Peran::whereIn('peran', ['zoom', 'ruang'])->where('tahun', session('tahun_active'))->get(),
            "satuanKerjaTahun" => Satuan_Kerja_Tahun::with(['peran_daftar'])->where('tahun', session('tahun_active'))->get(),
            "user" => User::all(),
        ]);
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'satuan_kerja_tahun' => 'required|string',
            'username' => 'required|string',
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
            $parent = Satuan_Kerja_Tahun::where('id', $request->input('satuan_kerja_tahun'))
                ->where('kode_satuan_kerja', '7600')
                ->first();
            if(!$parent){
                throw ValidationException::withMessages([
                    'status' => 'Peran tidak dapat dibuat',
                ]);
            }
        }else if($request->input('peran') == 'ruang'){
            $parent = Satuan_Kerja_Tahun::where('id', $request->input('satuan_kerja_tahun'))
                ->first();
            if(!$parent){
                throw ValidationException::withMessages([
                    'status' => 'Peran tidak dapat dibuat',
                ]);
            }
        }else{
            throw ValidationException::withMessages([
                'status' => 'Peran tidak dapat dibuat',
            ]);
        }

        $baru = new Peran();
        $baru->id =  Str::uuid();
        $baru->tipe_peran = 'satuan_kerja_tahun';
        $baru->parent_id = $request->input('satuan_kerja_tahun');
        $baru->pemilik_username = $request->input('username');
        $baru->pemilik_tim_id = null;
        $baru->pemilik_satuan_kerja_tahun_id = null;
        $baru->tahun = session('tahun_active');
        $baru->peran = $request->input('peran');
        $baru->peran_number = 16;
        $baru->save();

        redirect()->route('peran-index');
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

        redirect()->route('peran-index');
    }
}
