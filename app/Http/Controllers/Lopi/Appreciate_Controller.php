<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Appreciate_Data;
use App\Model\Lopi\Appreciate_Master;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Appreciate_Controller extends Controller
{

    public function index()
    {
        $satuan_kerja_tahun_kode = [];
        $peran_satuan_kerja = Peran::get_my_peran_satuan_kerja_kerja_tahun();

        foreach($peran_satuan_kerja as $item){
            if(!in_array($item->parent_id, $satuan_kerja_tahun_kode)){
                array_push($satuan_kerja_tahun_kode, $item->parent_id);
            }
        }

        $master_id = [];
        $master = Appreciate_Master::with('target')
                ->where('isi_awal', "<=", Carbon::today()->format('Y-m-d'))
                ->where('isi_akhir', ">=", Carbon::today()->format('Y-m-d'))
                ->whereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)
                ->get();
        foreach($master as $item){
            array_push($master_id, $item->id);
        }
        return Inertia::render('appreciate/tambah', [
            "user" => User::whereHas('peran', function (Builder $query) use ($satuan_kerja_tahun_kode) {
                        $query->where('tipe_peran', 'satuan_kerja_tahun');
                        $query->whereIn('parent_id', $satuan_kerja_tahun_kode);
                    })->get(),
            "master" => $master,
            "data" => Appreciate_Data::whereIn('master_id', $master_id)->where('dari', Auth::user()->username)->get(),
        ]);
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'status_anonim' => Rule::in(['1', '0']),
            'tujuan' => 'required',
            'pesan' => 'required|string',
            'kosong' => 'required|boolean',
        ]);

        $satuan_kerja_tahun_kode = [];
        $peran_satuan_kerja = Peran::get_my_peran_satuan_kerja_kerja_tahun();

        foreach($peran_satuan_kerja as $item){
            if(!in_array($item->parent_id, $satuan_kerja_tahun_kode)){
                array_push($satuan_kerja_tahun_kode, $item->parent_id);
            }
        }

        DB::beginTransaction();
        try
        {
            $target_master = [];
            $target_username = [];
            $appreciate_master = Appreciate_Master::with('target')
                    ->where('isi_awal', "<=", Carbon::today()->format('Y-m-d'))
                    ->where('isi_akhir', ">=", Carbon::today()->format('Y-m-d'))
                    ->whereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)
                    ->get();

            foreach($appreciate_master as $item){
                foreach($item->target as $item_target){
                    array_push($target_username, $item_target->username);
                    $target_master[$item_target->username] = $item->id;
                }
            }

            if(!in_array($request->input('tujuan'), $target_username)){
                throw ValidationException::withMessages([
                    '_status' => 'Gagal menambah data',
                ]);
            }

            Appreciate_Data::where('master_id', $target_master[$request->input('tujuan')])
                ->where('tujuan', $request->input('tujuan'))
                ->where('dari', Auth::user()->username)
                ->delete();

            $data = null;
            if(!$request->input('kosong')){
                $data = new Appreciate_Data();

                $data->master_id = $target_master[$request->input('tujuan')];
                $data->tujuan = $request->input('tujuan');
                $data->dari = Auth::user()->username;
                $data->status_anonim = $request->status_anonim;
                $data->pesan = $request->input('pesan');
                $data->status = "0";
                $data->save();
            }
            DB::commit();

            return $data;

        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal menambah data',
            ]);
        }



    }

    public function buka(Request $request)
    {
        // $data =  Appreciate_Data::where('tujuan', Auth::user()->username)->where('status', "2")->orderBy('id', 'desc')->get()
        // for($i)
        return Inertia::render('appreciate/buka', [
            "user" => User::all(),
            "data" => Appreciate_Data::where('tujuan', Auth::user()->username)->where('status', "2")->orderBy('id', 'desc')->get(),
        ]);
    }

}
