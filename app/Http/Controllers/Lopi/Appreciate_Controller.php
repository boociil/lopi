<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Appreciate_Data;
use App\Model\Lopi\Appreciate_Master;
use App\Model\Umum\Peran;
use App\Model\Umum\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Appreciate_Controller extends Controller
{

   public function index()
    {
       $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->first();

        if($have_appreciate_peran){
            abort(401);
        }

        return Inertia::render('appreciate/master', [
            "user" => User::all(),
            'have_appreciate_peran' => $have_appreciate_peran,
            "master" => Appreciate_Master::with('target')->where('isi_awal', "<=", Carbon::today()->format('Y-m-d'))
                ->where('isi_akhir', ">=", Carbon::today()->format('Y-m-d'))
                ->get(),
        ]);
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'anonim' => Rule::in(['1', '0']),
            'tujuan' => 'required',
            'pesan' => 'required|string',
        ]);

        $target_master = [];
        $target_username = [];
        $appreciate_master = Appreciate_Master::with('target')->where('isi_awal', "<=", Carbon::today()->format('Y-m-d'))
                ->where('isi_akhir', ">=", Carbon::today()->format('Y-m-d'))
                ->get();

        foreach($appreciate_master as $item){
            foreach($item->target as $item_target){
                array_push($target_username, $item_target->username);
                $target_master[$target_username] = $item->id;
            }
        }

        if(!in_array($request->input('tujuan'), $target_username)){
            throw ValidationException::withMessages([
                '_status' => 'Gagal menambah data',
            ]);
        }

        $data = new Appreciate_Data();
        $data->master_id = $target_master[$request->input('tujuan')];
        if($request->anonim == '1'){
            $data->dari = null;
        }else if($request->anonim == '0'){
            $data->tujuan = Auth::user()->usernem;
        }
        $data->pesan = $request->input('pesan');
        $data->save();

        return 1;
    }

    public function ubah(Request $request, Appreciate_Data $appreciation)
    {
        $validated = $request->validate([
            'anonim' => Rule::in(['1', '0']),
            'tujuan' => 'required',
            'pesan' => 'required|string',
        ]);

        $target_master = [];
        $target_username = [];
        $appreciate_master = Appreciate_Master::with('target')->where('isi_awal', "<=", Carbon::today()->format('Y-m-d'))
                ->where('isi_akhir', ">=", Carbon::today()->format('Y-m-d'))
                ->get();

        foreach($appreciate_master as $item){
            foreach($item->target as $item_target){
                array_push($target_username, $item_target->username);
                $target_master[$target_username] = $item->id;
            }
        }

        if(!in_array($request->input('tujuan'), $target_username)){
            throw ValidationException::withMessages([
                '_status' => 'Gagal menambah data',
            ]);
        }

        if($request->anonim == '1'){
            $appreciation->dari = null;
        }else if($request->anonim == '0'){
            $appreciation->tujuan = Auth::user()->usernem;
        }
        $appreciation->pesan = $request->input('pesan');
        $appreciation->save();

        return 1;
    }

    public function hapus(Request $request, Appreciate_Data $appreciation)
    {
        $appreciation->delete();

        return 1;
    }

    public function buka(Request $request)
    {
         return Inertia::render('appreciate/master', [
            "user" => User::all(),
            "master" => Appreciate_Data::where('tujuan', Auth::user()->username)
                ->get(),
        ]);
    }


}
