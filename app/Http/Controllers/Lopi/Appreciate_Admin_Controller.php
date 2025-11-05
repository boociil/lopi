<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Appreciate_Data;
use App\Model\Lopi\Appreciate_Master;
use App\Model\Lopi\Appreciate_Target;
use App\Model\Lopi\AppreciateTarget;
use App\Model\Lopi\Jadwal;
use App\Model\Umum\Peran;
use App\Model\Umum\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

use App\Traits\Menu;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class Appreciate_Admin_Controller extends Controller
{

   public function index()
    {
        $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

        return Inertia::render('appreciate/master', [
            "user" => User::all(),
            'have_appreciate_peran' => $have_appreciate_peran,
            "data" =>  Appreciate_Master::with('target')->get(),
        ]);
    }

    public function tambah(Request $request)
    {
        $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

        $validated = $request->validate([
            'nama' => 'required',
            'isi_awal' => 'required|date',
            'isi_akhir' => 'required|date|after_or_equal:isi_awal',
        ]);

        DB::beginTransaction();
        try
        {
            $master = new  Appreciate_Master();
            $master->nama = $request->input('nama');
            $master->isi_awal = $request->input('isi_awal');
            $master->isi_akhir = $request->input('isi_akhir');
            $master->save();

            foreach($request->target as $username){
                $target = new Appreciate_Target();
                $target->master_id = $master->id;
                $target->username = $username;
                $target->save();
            }

            DB::commit();

            return [
                "data" => Appreciate_Master::with('target')->where('id', $master->id)->first(),
                '_status' => 'Berhasil menambah master',
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal menambah master',
            ]);
        }

    }

    public function ubah(Request $request, Appreciate_Master $master)
    {
        $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

        $validated = $request->validate([
            'nama' => 'required',
            'isi_awal' => 'required|date',
            'isi_akhir' => 'required|date|after_or_equal:isi_awal',
        ]);

        DB::beginTransaction();
        try
        {
            Appreciate_Target::where('master_id', $master->id)->delete();

            $master->nama = $request->input('nama');
            $master->isi_awal = $request->input('isi_awal');
            $master->isi_akhir = $request->input('isi_akhir');
            $master->save();

            foreach($request->target as $username){
                $target = new Appreciate_Target();
                $target->master_id = $master->id;
                $target->username = $username;
                $target->save();
            }

            DB::commit();

            return [
                "data" => Appreciate_Master::with('target')->where('id', $master->id)->first(),
                '_status' => 'Berhasil menambah master',
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal menambah master',
            ]);
        }

    }

    public function hapus(Request $request, Appreciate_Master $master)
    {
        $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

        DB::beginTransaction();
        try
        {
            Appreciate_Target::where('id', $master->id)->delete();
            $master->delete();

            DB::commit();

            return [
                '_status' => 'Berhasil menambah master',
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal menambah master',
            ]);
        }

    }

    public function aproval_index(Request $request)
    {
        $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

        return Inertia::render('appreciate/aproval', [
            "user" => User::all(),
            'have_appreciate_peran' => $have_appreciate_peran,
            "data" =>  Appreciate_Data::where('status', '0')->get(),
        ]);
    }

    public function aproval_data(Request $request, Appreciate_Data $appreciation)
    {
        $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

        $validated = $request->validate([
            'status' => Rule::in(['1', '2']),
        ]);

        $appreciation->status = $request->status;
        $appreciation->save();
        return $appreciation;
    }


}
