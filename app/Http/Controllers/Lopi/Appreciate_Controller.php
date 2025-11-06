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
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Appreciate_Controller extends Controller
{

   public function index()
    {
        $master_id = [];
        $master = Appreciate_Master::with('target')
                ->where('isi_awal', "<=", Carbon::today()->format('Y-m-d'))
                ->where('isi_akhir', ">=", Carbon::today()->format('Y-m-d'))
                ->get();
        foreach($master as $item){
            array_push($master_id, $item->id);
        }
        return Inertia::render('appreciate/data', [
            "user" => User::all(),
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
        ]);

        $target_master = [];
        $target_username = [];
        $appreciate_master = Appreciate_Master::with('target')->where('isi_awal', "<=", Carbon::today()->format('Y-m-d'))
                ->where('isi_akhir', ">=", Carbon::today()->format('Y-m-d'))
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

        $data = Appreciate_Data::where('master_id', $target_master[$request->input('tujuan')])
            ->where('tujuan', $request->input('tujuan'))
            ->where('dari', Auth::user()->username)
            ->first();
        if(!$data){
            $data = new Appreciate_Data();
        }
        $data->master_id = $target_master[$request->input('tujuan')];
        $data->tujuan = $request->input('tujuan');
        $data->dari = Auth::user()->username;
        $data->status_anonim = $request->status_anonim;
        $data->pesan = $request->input('pesan');
        $data->status = "0";
        $data->save();

        return $data;
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

    public function baca(Request $request)
    {
        $data =  Appreciate_Data::where('status', "2")->orderBy('id', 'desc')->get();
        $data_hasil = [];

        foreach($data as $item){
            if(array_key_exists($item->tujuan, $data_hasil)){
                array_push($data_hasil[$item->tujuan], $item);
            }else{
                $data_hasil[$item->tujuan] = [$item];
            }
        }

        $return_data = [];
        foreach($data_hasil as $key => $item){
            if(count($item)>5){
                $data_item = [];
                $key_get = array_rand($item, 5);
                foreach ($key_get as $key_) {
                    array_push($data_item, $item[$key_]);
                }

                array_push($return_data, ['tujuan' => $key, 'data' => $data_item]);
            }else{
                array_push($return_data, ['tujuan' => $key, 'data' => $item]);
            }
        }

        return Inertia::render('appreciate/baca', [
            "user" => User::all(),
            "data" => $return_data,
        ]);
    }


}
