<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

use App\Model\Umum\Peran;
use App\Model\Lopi\Pinjam_Mobil;
use App\Model\Umum\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            $view = 'month';
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

        if($tanggal_awal->format('Y-m-d') == $tanggal_akhir->format('Y-m-d') && $view == 'week'){
            $tanggal_akhir = Carbon::parse($tanggal_awal)->addDays(6);
        }

        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'mobil')
            ->first();

        if($request->isMethod('get')){
            return Inertia::render('pinjam/mobil', [
                "user" => User::all(),
                'have_mobil_peran' => $have_mobil_peran,
                "data" =>  Pinjam_Mobil::orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir){
                            $query->where('tipe', 'sekali')
                                ->whereBetween('tanggal', [$tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d')]);
                        })
                        ->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir) {
                            $query->where('tipe', 'berulang')
                                ->where('berulang_tanggal_awal', '<=', $tanggal_akhir)
                                ->where('berulang_tanggal_akhir', '>=', $tanggal_awal);
                        })
                    ->get(),
            ]);
        }else if($request->isMethod('post')){
             return [
                "data" =>  Pinjam_Mobil::orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir){
                            $query->where('tipe', 'sekali')
                                ->whereBetween('tanggal', [$tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d')]);
                        })
                        ->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir) {
                            $query->where('tipe', 'berulang')
                                ->where('berulang_tanggal_awal', '<=', $tanggal_akhir)
                                ->where('berulang_tanggal_akhir', '>=', $tanggal_awal);
                        })
                    ->get(),
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

        if(!$request->input('tanggal') || (!$request->input('tanggal_awal') && !$request->input('tanggal_akhir'))){
            throw ValidationException::withMessages([
                '_status' => 'Peran tidak dapat dibuat',
            ]);
        }

        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'mobil')
            ->first();

        if($have_mobil_peran){
             if($request->input('tipe') == 'sekali'){
                if(Pinjam_Mobil::staticCheckClash($request->input('tanggal'), $request->input('tanggal')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash' => 'Sudah terdapat peminjaman di tanggal dan waktu tersebut',
                    ]);
                }
             }else{
                if(Pinjam_Mobil::staticCheckClash($request->input('tanggal_awal'), $request->input('tanggal_akhir')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash' => 'Sudah terdapat peminjaman di tanggal dan waktu tersebut',
                    ]);
                }
             }
        }

        DB::beginTransaction();
        try
        {
            $return_data = [];
            if($request->input('tipe') == 'sekali'){
                $pinjam_mobil = new Pinjam_Mobil();
                $pinjam_mobil->id = Str::uuid();
                $pinjam_mobil->kegiatan = $request->input('kegiatan');
                $pinjam_mobil->tipe = $request->input('tipe');

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
                $pinjam_mobil = new Pinjam_Mobil();
                $pinjam_mobil->id = Str::uuid();
                $pinjam_mobil->kegiatan = $request->input('kegiatan');
                $pinjam_mobil->tipe = $request->input('tipe');

                $pinjam_mobil->berulang_tanggal_awal = $request->input('tanggal_awal');
                $pinjam_mobil->berulang_tanggal_akhir = $request->input('tanggal_akhir');
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
            if(count($return_data) != 1){
                throw new Exception('Return Kosong');
            }
            DB::commit();
            return [
                "_status" => 'Berhasil membuat peminjaman mobil!',
                "data" => $return_data[0]
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

        if(!$request->input('tanggal') || (!$request->input('tanggal_awal') && !$request->input('tanggal_akhir'))){
            throw ValidationException::withMessages([
                '_status' => 'Peran tidak dapat dibuat',
            ]);
        }

        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'mobil')
            ->first();

        if(! ($have_mobil_peran || $pinjamMobil->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal mengubah peminjaman mobil!',
            ]);
        }

        if($have_mobil_peran){
             if($request->input('tipe') == 'sekali'){
                if(Pinjam_Mobil::staticCheckClash($request->input('tanggal'), $request->input('tanggal')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash' => 'Sudah terdapat peminjaman di tanggal dan waktu tersebut',
                    ]);
                }
             }else{
                if(Pinjam_Mobil::staticCheckClash($request->input('tanggal_awal'), $request->input('tanggal_akhir')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash' => 'Sudah terdapat peminjaman di tanggal dan waktu tersebut',
                    ]);
                }
             }
        }

        DB::beginTransaction();
        try
        {
            if($request->input('tipe') == 'sekali'){
                $pinjamMobil->kegiatan = $request->input('kegiatan');
                $pinjamMobil->tipe = $request->input('tipe');

                $pinjamMobil->tanggal = $request->input('tanggal');
                $pinjamMobil->berulang_tanggal_awal = null;
                $pinjamMobil->berulang_tanggal_akhir = null;

                $pinjamMobil->waktu_awal = $request->input('waktu_awal');
                $pinjamMobil->waktu_akhir = $request->input('waktu_akhir');
                $pinjamMobil->status = 'menunggu';
                $pinjamMobil->pembuat_username = Auth::user()->username;

                if($have_mobil_peran){
                    $pinjamMobil->status = 'disetujui';
                    $pinjamMobil->confirm_username = Auth::user()->username;
                }
                $pinjamMobil->save();

            }else if($request->input('tipe') == 'berulang'){
                $pinjamMobil->kegiatan = $request->input('kegiatan');
                $pinjamMobil->tipe = $request->input('tipe');

                $pinjamMobil->tanggal = null;
                $pinjamMobil->berulang_tanggal_awal = $request->input('tanggal_awal');
                $pinjamMobil->berulang_tanggal_akhir = $request->input('tanggal_akhir');

                $pinjamMobil->waktu_awal = $request->input('waktu_awal');
                $pinjamMobil->waktu_akhir = $request->input('waktu_akhir');
                $pinjamMobil->status = 'menunggu';
                $pinjamMobil->pembuat_username = Auth::user()->username;

                if($have_mobil_peran){
                    $pinjamMobil->status = 'disetujui';
                    $pinjamMobil->confirm_username = Auth::user()->username;
                }
                $pinjamMobil->save();
            }
            DB::commit();
            return [
                "_status" => 'Berhasil mengubah peminjaman mobil!',
                "data" => $pinjamMobil
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
            ->where('peran', 'mobil')
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
            ->where('peran', 'mobil')
            ->first();

        if(! ($have_mobil_peran) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman mobil!',
            ]);
        }

         if($have_mobil_peran){
           if($pinjamMobil->checkClash()){
                throw ValidationException::withMessages([
                    'error_clash' => 'Sudah terdapat peminjaman di tanggal dan waktu tersebut',
                ]);
            }
        }


        DB::beginTransaction();
        try
        {
            $pinjamMobil->status = $request->input('status');
            $pinjamMobil->confirm_username = Auth::user()->username;
            $pinjamMobil->save();
            DB::commit();
            return [
                "_status" => 'Berhasil approval peminjaman mobil!',
                "data" => $pinjamMobil
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
