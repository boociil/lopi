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
use App\Model\Lopi\Pinjam_Ruang;
use App\Model\Umum\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use function PHPUnit\Framework\isNull;

class Pinjam_Ruang_Controller extends Controller
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
        }else if(session()->has('pinjam_ruang_index')){
            $selectedDate = Carbon::today()->format('Y-m-d');
            $view = session('pinjam_ruang_index')['view'];
        }else{
            $selectedDate = Carbon::today()->format('Y-m-d');
            $view = 'week';
        }

        session()->put('pinjam_ruang_index', ['view' => $view]);

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

        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

        if($request->isMethod('get')){
            return Inertia::render('pinjam_ruang', [
                "user" => User::all(),
                'have_ruang_peran' => $have_ruang_peran,
                "data" =>  Pinjam_Ruang::orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir){
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
                "data" =>  Pinjam_Ruang::orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir){
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
            'ruang'             => 'required|in:aula,pokja',
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

        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

        DB::beginTransaction();
        try
        {
            $return_data = [];
            if($request->input('tipe') == 'sekali'){
                $pinjam_ruang = new Pinjam_Ruang();
                $pinjam_ruang->id = Str::uuid();
                $pinjam_ruang->kegiatan = $request->input('kegiatan');
                $pinjam_ruang->ruang = $request->input('ruang');
                $pinjam_ruang->tipe = $request->input('tipe');

                $pinjam_ruang->tanggal = $request->input('tanggal');
                $pinjam_ruang->waktu_awal = $request->input('waktu_awal');
                $pinjam_ruang->waktu_akhir = $request->input('waktu_akhir');
                $pinjam_ruang->status = 'menunggu';
                $pinjam_ruang->pembuat_username = Auth::user()->username;

                if($have_ruang_peran){
                    $pinjam_ruang->status = 'disetujui';
                    $pinjam_ruang->confirm_username = Auth::user()->username;
                }
                $pinjam_ruang->save();
                array_push($return_data, $pinjam_ruang);

            }else if($request->input('tipe') == 'berulang'){
                $pinjam_ruang = new Pinjam_Ruang();
                $pinjam_ruang->id = Str::uuid();
                $pinjam_ruang->kegiatan = $request->input('kegiatan');
                $pinjam_ruang->ruang = $request->input('ruang');
                $pinjam_ruang->tipe = $request->input('tipe');

                $pinjam_ruang->berulang_tanggal_awal = $request->input('tanggal_awal');
                $pinjam_ruang->berulang_tanggal_akhir = $request->input('tanggal_akhir');
                $pinjam_ruang->waktu_awal = $request->input('waktu_awal');
                $pinjam_ruang->waktu_akhir = $request->input('waktu_akhir');
                $pinjam_ruang->status = 'menunggu';
                $pinjam_ruang->pembuat_username = Auth::user()->username;

                if($have_ruang_peran){
                    $pinjam_ruang->status = 'disetujui';
                    $pinjam_ruang->confirm_username = Auth::user()->username;
                }
                $pinjam_ruang->save();
                array_push($return_data, $pinjam_ruang);
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

    public function ubah(Request $request, Pinjam_Ruang $pinjamRuang)
    {
        $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'ruang'             => 'required|in:aula,pokja',
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

        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

        if(! ($have_ruang_peran || $pinjamRuang->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal mengubah peminjaman mobil!',
            ]);
        }

        DB::beginTransaction();
        try
        {
            if($request->input('tipe') == 'sekali'){
                $pinjamRuang->kegiatan = $request->input('kegiatan');
                $pinjamRuang->ruang = $request->input('ruang');
                $pinjamRuang->tipe = $request->input('tipe');

                $pinjamRuang->tanggal = $request->input('tanggal');
                $pinjamRuang->waktu_awal = $request->input('waktu_awal');
                $pinjamRuang->waktu_akhir = $request->input('waktu_akhir');
                $pinjamRuang->status = 'menunggu';
                $pinjamRuang->pembuat_username = Auth::user()->username;

                if($have_ruang_peran){
                    $pinjamRuang->status = 'disetujui';
                    $pinjamRuang->confirm_username = Auth::user()->username;
                }
                $pinjamRuang->save();

            }else if($request->input('tipe') == 'berulang'){
                $pinjamRuang->kegiatan = $request->input('kegiatan');
                $pinjamRuang->ruang = $request->input('ruang');
                $pinjamRuang->tipe = $request->input('tipe');

                $pinjamRuang->berulang_tanggal_awal = $request->input('tanggal_awal');
                $pinjamRuang->berulang_tanggal_akhir = $request->input('tanggal_akhir');
                $pinjamRuang->waktu_awal = $request->input('waktu_awal');
                $pinjamRuang->waktu_akhir = $request->input('waktu_akhir');
                $pinjamRuang->status = 'menunggu';
                $pinjamRuang->pembuat_username = Auth::user()->username;

                if($have_ruang_peran){
                    $pinjamRuang->status = 'disetujui';
                    $pinjamRuang->confirm_username = Auth::user()->username;
                }
                $pinjamRuang->save();
            }
            DB::commit();
            return [
                "_status" => 'Berhasil mengubah peminjaman mobil!',
                "data" => $pinjamRuang
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
    public function hapus(Pinjam_Ruang $pinjamRuang)
    {
        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

        if(! ($have_ruang_peran || $pinjamRuang->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal menghapus peminjaman mobil!',
            ]);
        }

        DB::beginTransaction();
        try
        {
            $pinjamRuang->delete();
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
    public function approval(Request $request, Pinjam_Ruang $pinjamRuang)
    {
        $validated = $request->validate([
            'status'              => 'required|in:disetujui,ditolak',
        ]);

        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

        if(! ($have_ruang_peran) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman mobil!',
            ]);
        }


        DB::beginTransaction();
        try
        {
            $pinjamRuang->status = $request->input('status');
            $pinjamRuang->confirm_username = Auth::user()->username;
            $pinjamRuang->save();
            DB::commit();
            return [
                "_status" => 'Berhasil approval peminjaman mobil!',
                "data" => $pinjamRuang
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
