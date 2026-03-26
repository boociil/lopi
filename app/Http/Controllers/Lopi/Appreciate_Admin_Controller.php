<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Appreciate_Data;
use App\Model\Lopi\Appreciate_Master;
use App\Model\Lopi\Appreciate_Target;
use App\Model\Lopi\AppreciateTarget;
use App\Model\Lopi\Jadwal;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

use App\Traits\Menu;
use Exception;
use Illuminate\Database\Eloquent\Builder;
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
            ->where('tahun', session('tahun_active'))
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

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

        return Inertia::render('appreciate/master', [
            "user" => User::whereHas('peran', function (Builder $query) use ($satuan_kerja_tahun_kode) {
                        $query->where('tipe_peran', 'satuan_kerja_tahun');
                        $query->whereIn('parent_id', $satuan_kerja_tahun_kode);
                    })->get(),
            'have_appreciate_peran' => $have_appreciate_peran,
            "data" =>  Appreciate_Master::with('target')->whereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)->get(),
        ]);
    }

    public function tambah(Request $request)
    {
        $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->where('tahun', session('tahun_active'))
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

        $validated = $request->validate([
            'nama' => 'required',
            'isi_awal' => 'required|date',
            'isi_akhir' => 'required|date|after_or_equal:isi_awal',
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

        DB::beginTransaction();
        try
        {
            $master = new  Appreciate_Master();
            $master->nama = $request->input('nama');
            $master->isi_awal = $request->input('isi_awal');
            $master->isi_akhir = $request->input('isi_akhir');
            $master->satuan_kerja_tahun_id = $satuan_kerja_tahun_kode[0];
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
            ->where('tahun', session('tahun_active'))
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

        $validated = $request->validate([
            'nama' => 'required',
            'isi_awal' => 'required|date',
            'isi_akhir' => 'required|date|after_or_equal:isi_awal',
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

        if(!in_array($master->satuan_kerja_tahun_id, $satuan_kerja_tahun_kode)){
            return abort(403);
        }

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
            ->where('tahun', session('tahun_active'))
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

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
            ->where('tahun', session('tahun_active'))
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

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

        $master_id = Appreciate_Master::whereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)->get()->pluck('id')->toArray();

        return Inertia::render('appreciate/aproval', [
            "user" => User::all(),
            'have_appreciate_peran' => $have_appreciate_peran,
            "data" =>  Appreciate_Data::whereIn('master_id', $master_id)->where('status', '0')->get(),
        ]);
    }

    public function aproval_data(Request $request, Appreciate_Data $appreciation)
    {
        $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->where('tahun', session('tahun_active'))
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

        $validated = $request->validate([
            'status' => Rule::in(['1', '2']),
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

        if(!Appreciate_Master::whereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)->where('id',$appreciation->master_id)->first()){
            return abort(401);
        }

        $appreciation->status = $request->status;
        $appreciation->save();
        return $appreciation;
    }

    public function baca(Request $request)
    {
        $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->where('tahun', session('tahun_active'))
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

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

        $master_id = [];
        $master = Appreciate_Master::where('isi_awal', "<=", Carbon::today()->format('Y-m-d'))
                ->whereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)
                ->orderBy('isi_awal', 'desc')
                ->limit(1)
                ->get();
        foreach($master as $item){
            array_push($master_id, $item->id);
        }

        $data =  Appreciate_Data::whereIn('master_id', $master_id)->where('status', "2")->orderBy('id', 'desc')->get();
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

    public function rekap(Request $request)
    {
        $have_appreciate_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'appreciate')
            ->where('tahun', session('tahun_active'))
            ->first();

        if(!$have_appreciate_peran){
            abort(401);
        }

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

        $user = User::whereHas('peran', function (Builder $query) use ($satuan_kerja_tahun_kode) {
                $query->where('tipe_peran', 'satuan_kerja_tahun');
                $query->whereIn('parent_id', $satuan_kerja_tahun_kode);
            })->orderBy('nama')->get();

        $user_username_arr = $user->pluck('username')->toArray();

        $master_id = [];
        $master_target = [];
        $master = Appreciate_Master::with(['target'=> function($q) use($user_username_arr) {
                $q->whereIn('username', $user_username_arr);
            }])
            ->whereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)
            ->get();


        foreach($master as $item){
            array_push($master_id, $item->id);
            if(!array_key_exists($item->id, $master_target)){
                $master_target[$item->id] = [];
            }
            foreach($item->target as $item_target){
                array_push($master_target[$item->id], $item_target->username);
            }
        }



        $data =  Appreciate_Data::
                    whereIn('master_id', $master_id)
                    ->select( 'master_id', 'dari', 'tujuan')
                    ->whereIn('tujuan', $user_username_arr)
                    ->where('status', "2")
                    ->select('master_id', 'dari', 'tujuan')
                    ->distinct()
                    ->get();

        $data_arr = [];
        $data_arr_index = [];

        foreach($data as $item){
            if(in_array($item->dari, $user_username_arr) && in_array($item->tujuan, $user_username_arr)
                && in_array($item->tujuan, $master_target[$item->master_id])){
                if(!array_key_exists($item->master_id.'--+-+--'.$item->dari, $data_arr_index)){
                    $data_arr_index[$item->master_id.'--+-+--'.$item->dari] = count($data_arr_index);
                    array_push($data_arr, ['master_id' => $item->master_id, 'dari' => $item->dari, 'jumlah' => 0]);
                }
                $data_arr[$data_arr_index[$item->master_id.'--+-+--'.$item->dari]]['jumlah'] += 1;
            }
        }

        return Inertia::render('appreciate/rekap', [
            "user" => User::whereHas('peran', function (Builder $query) use ($satuan_kerja_tahun_kode) {
                    $query->where('tipe_peran', 'satuan_kerja_tahun');
                    $query->whereIn('parent_id', $satuan_kerja_tahun_kode);
                })->orderBy('nama')->get(),
            "data" => $data_arr,
            "master" => $master,
        ]);
    }


}
