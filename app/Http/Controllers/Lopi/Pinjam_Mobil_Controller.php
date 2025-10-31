<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use Inertia\Inertia;

use App\Model\Umum\Peran;
use App\Model\Lopi\Pinjam_Mobil;
use App\Model\Umum\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use function PHPUnit\Framework\isNull;

class Pinjam_Mobil_Controller extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'selectedDate'  => 'date',
            'view'          => 'string',
            'view'          => Rule::in(['day', 'week', 'month', 'year', 'agenda'])
        ]);

        $selectedDate = null;
        $view = null;

        if($request->has('selectedDate') && $request->has('view')){
            $selectedDate = $request->input('selectedDate');
            $view = $request->input('view');
        }else if(session()->has('pinjam_mobil_index')){
            $selectedDate = Carbon::today()->format('Y-m-d');
            $view = session('pinjam_mobil_index')['view'];
        }else{
            $selectedDate = Carbon::today()->format('Y-m-d');
            $view = 'week';
        }

        session()->put('pinjam_mobil_index', ['view' => $view]);

        $tanggal_awal = null;
        $tanggal_akhir = null;

        if($view == 'day'){
            $tanggal_awal = Carbon::parse($selectedDate);
            $tanggal_akhir = Carbon::parse($selectedDate);
        }else  if($view == 'week'){
            $tanggal_awal = Carbon::parse($selectedDate)->startOfWeek(Carbon::SUNDAY);
            $tanggal_akhir = Carbon::parse($selectedDate)->endOfWeek(Carbon::SUNDAY);
        }else if($view == 'month' || $view == 'agenda'){
            $tanggal_awal = Carbon::parse($selectedDate)->startOfMonth();
            $tanggal_akhir = Carbon::parse($selectedDate)->endOfMonth();
        }else if($view == 'year'){
            $tanggal_awal = Carbon::parse($selectedDate)->startOfYear();
            $tanggal_akhir = Carbon::parse($selectedDate)->endOfYear();
        }


        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'mobil')
            ->whereNull('tahun')
            ->first();

        if($request->isMethod('get')){
            return Inertia::render('mobil', [
                "user" => User::all(),
                'have_mobil_peran' => $have_mobil_peran,
                "data" =>  Pinjam_Mobil::orWhereBetween('tanggal', [$tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d')])->get(),
            ]);
        }else if($request->isMethod('post')){
             return [
                "data" =>  Pinjam_Mobil::orWhereBetween('tanggal', [$tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d')])->get(),
            ];
        }
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal'           => 'date',
            'tanggal_awal'      => 'date',
            'tanggal_akhir'     => 'date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',
        ]);

        if(!isNull($request->input('tanggal')) || (!isNull($request->input('tanggal_awal')) && !isNull($request->input('tanggal_akhir')))){
            throw ValidationException::withMessages([
                '_status' => 'Peran tidak dapat dibuat',
            ]);
        }

        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'mobil')
            ->whereNull('tahun')
            ->first();

        DB::beginTransaction();
        try
        {
            $return_data = [];
            if($request->input('tipe') == 'sekali'){
                $pinjam_mobil = new Pinjam_Mobil();
                $pinjam_mobil->id = Str::uuid();
                $pinjam_mobil->kegiatan = $request->input('kegiatan');
                $pinjam_mobil->tipe = $request->input('tipe');

                $pinjam_mobil->berulang_id = null;
                $pinjam_mobil->tanggal = $request->input('tanggal');
                $pinjam_mobil->waktu_awal = $request->input('waktu_awal');
                $pinjam_mobil->waktu_akhir = $request->input('waktu_akhir');
                $pinjam_mobil->status = 'menunggu';
                $pinjam_mobil->pembuat_username = Auth::user()->username;

                if($have_mobil_peran){
                    $pinjam_mobil->status = 'disetujui';
                    $pinjam_mobil->confirm_username = Auth::user()->username;
                }
                $pinjam_mobil->save();
                array_push($return_data, $pinjam_mobil);

            }else if($request->input('tipe') == 'berulang'){
                $berulang_id = Str::uuid();

                $startDate = Carbon::parse($request->input('tanggal_awal'));
                $endDate = Carbon::parse($request->input('tanggal_akhir'));
                $period = CarbonPeriod::create($startDate, $endDate);

                foreach ($period as $date) {
                    $pinjam_mobil = new Pinjam_Mobil();
                    $pinjam_mobil->id = Str::uuid();
                    $pinjam_mobil->kegiatan = $request->input('kegiatan');
                    $pinjam_mobil->tipe = $request->input('tipe');

                    $pinjam_mobil->berulang_id = $berulang_id;
                    $pinjam_mobil->tanggal = $date->format('Y-m-d');
                    $pinjam_mobil->waktu_awal = $request->input('waktu_awal');
                    $pinjam_mobil->waktu_akhir = $request->input('waktu_akhir');
                    $pinjam_mobil->status = 'menunggu';
                    $pinjam_mobil->pembuat_username = Auth::user()->username;

                    if($have_mobil_peran){
                        $pinjam_mobil->status = 'disetujui';
                        $pinjam_mobil->confirm_username = Auth::user()->username;
                    }
                    $pinjam_mobil->save();
                    array_push($return_data, $pinjam_mobil);
                }
            }
            if(count($return_data) == 0){
                throw new Exception('Return Kosong');
            }
            DB::commit();
            return [
                "_status" => 'Berhasil membuat peminjaman mobil!',
                "data" => $return_data
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal membuat peminjaman mobil!',
            ]);
        }
    }

    public function ubah(Request $request, Pinjam_Mobil $pinjamMobil)
    {
        $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal'           => 'date',
            'tanggal_awal'      => 'date',
            'tanggal_akhir'     => 'date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',
        ]);

        if(!isNull($request->input('tanggal')) || (!isNull($request->input('tanggal_awal')) && !isNull($request->input('tanggal_akhir')))){
            throw ValidationException::withMessages([
                '_status' => 'Peran tidak dapat dibuat',
            ]);
        }

        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'mobil')
            ->whereNull('tahun')
            ->first();

        if(! ($have_mobil_peran || $pinjamMobil->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal mengubah peminjaman mobil!',
            ]);
        }

        DB::beginTransaction();
        try
        {
            $return_data = [];
            $pinjamMobil->delete();
            if($pinjamMobil->berulang_id){
                Pinjam_Mobil::where('berulang_id', $pinjamMobil->berulang_id)->delete();
            }

            if($request->input('tipe') == 'sekali'){
                $pinjam_mobil = new Pinjam_Mobil();
                $pinjam_mobil->id = Str::uuid();
                $pinjam_mobil->kegiatan = $request->input('kegiatan');
                $pinjam_mobil->tipe = $request->input('tipe');

                $pinjam_mobil->berulang_id = null;
                $pinjam_mobil->tanggal = $request->input('tanggal');
                $pinjam_mobil->waktu_awal = $request->input('waktu_awal');
                $pinjam_mobil->waktu_akhir = $request->input('waktu_akhir');
                $pinjam_mobil->status = 'menunggu';
                $pinjam_mobil->pembuat_username = Auth::user()->username;

                if($have_mobil_peran){
                    $pinjam_mobil->status = 'disetujui';
                    $pinjam_mobil->confirm_username = Auth::user()->username;
                }
                $pinjam_mobil->save();
                array_push($return_data, $pinjam_mobil);
            }else if($request->input('tipe') == 'berulang'){
                $berulang_id = Str::uuid();

                $startDate = Carbon::parse($request->input('tanggal_awal'));
                $endDate = Carbon::parse($request->input('tanggal_akhir'));
                $period = CarbonPeriod::create($startDate, $endDate);

                foreach ($period as $date) {
                    $pinjam_mobil = new Pinjam_Mobil();
                    $pinjam_mobil->id = Str::uuid();
                    $pinjam_mobil->kegiatan = $request->input('kegiatan');
                    $pinjam_mobil->tipe = $request->input('tipe');

                    $pinjam_mobil->berulang_id = $berulang_id;
                    $pinjam_mobil->tanggal = $date->format('Y-m-d');
                    $pinjam_mobil->waktu_awal = $request->input('waktu_awal');
                    $pinjam_mobil->waktu_akhir = $request->input('waktu_akhir');
                    $pinjam_mobil->status = 'menunggu';
                    $pinjam_mobil->pembuat_username = Auth::user()->username;

                    if($have_mobil_peran){
                        $pinjam_mobil->status = 'disetujui';
                        $pinjam_mobil->confirm_username = Auth::user()->username;
                    }
                    $pinjam_mobil->save();
                    array_push($return_data, $pinjam_mobil);
                }
            }
            DB::commit();
            return [
                "_status" => 'Berhasil mengubah peminjaman mobil!',
                "data" => $return_data
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal mengubah peminjaman mobil!',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function hapus(Pinjam_Mobil $pinjamMobil)
    {
        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'mobil')
            ->whereNull('tahun')
            ->first();

        if(! ($have_mobil_peran || $pinjamMobil->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal menghapus peminjaman mobil!',
            ]);
        }

        DB::beginTransaction();
        try
        {
            $pinjamMobil->delete();
            if($pinjamMobil->berulang_id){
                Pinjam_Mobil::where('berulang_id', $pinjamMobil->berulang_id)->delete();
            }
            DB::commit();
            return [
                "_status" => 'Berhasil menghapus peminjaman mobil!'
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal menghapus peminjaman mobil!',
            ]);
        }
    }

    /**
     * Approve a pinjam mobil request
     */
    public function approval(Request $request, Pinjam_Mobil $pinjamMobil)
    {
        $validated = $request->validate([
            'status'              => 'required|in:disetujui,ditolak',
        ]);

        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'mobil')
            ->whereNull('tahun')
            ->first();

        if(! ($have_mobil_peran) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman mobil!',
            ]);
        }


        DB::beginTransaction();
        try
        {
            $pinjamMobil->status = $request->input('status');
            $pinjamMobil->confirm_username = Auth::user()->username;
            $pinjamMobil->save();
            if($pinjamMobil->berulang_id){
                Pinjam_Mobil::where('berulang_id', $pinjamMobil->berulang_id)->update([
                    'status' => $request->input('status'),
                    'confirm_username' => Auth::user()->username,
                ]);
            }
            DB::commit();
            return [
                "_status" => 'Berhasil approval peminjaman mobil!',
                "data" => Pinjam_Mobil::where('id', $pinjamMobil->id)->orWhere('berulang_id', $pinjamMobil->berulang_id)->get()
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman mobil!',
            ]);
        }
    }
}
