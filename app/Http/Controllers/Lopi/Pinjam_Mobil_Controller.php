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

class Pinjam_Mobil_Controller extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'tanggal_awal'      => 'date',
            'tanggal_akhir'     => 'date|after_or_equal:tanggal_awal',
        ]);

        $tanggal_awal = null;
        $tanggal_akhir = null;

        if($request->has('tanggal_awal') && $request->has('tanggal_akhir')){
            $tanggal_awal = $request->input('tanggal_awal');
            $tanggal_akhir = $request->input('tanggal_akhir');
        }

        if(! ($tanggal_awal && $tanggal_akhir) ){
            $date = Carbon::today();
            $tanggal_awal = $date->startOfWeek(Carbon::SUNDAY);
            $tanggal_akhir = $date->endOfWeek(Carbon::SUNDAY);
        }

        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'mobil')
            ->whereNull('tahun')
            ->first();

        $user = User::all();

        return Inertia::render('mobil', [
            "tanggal_awal" => $request->input('tanggal_awal'),
            "tanggal_akhir" => $request->input('tanggal_akhir'),
            "have_mobil_peran" => $have_mobil_peran ? true : false,
            "user" => $user,
            "data" =>  Pinjam_Mobil::orWhereBetween('tanggal_awal', [$request->input('tanggal_awal'), $request->input('tanggal_akhir')])
                            ->orWhereBetween('tanggal_akhir', [$request->input('tanggal_awal'), $request->input('tanggal_akhir')])
                            ->get(),
        ]);
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal_awal'      => 'required|date',
            'tanggal_akhir'     => 'required|date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',
        ]);

        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'mobil')
            ->whereNull('tahun', null)
            ->first();

        DB::beginTransaction();
        try
        {
            if($request->input('tipe') == 'sekali' && $request->input('tanggal_awal') ==  $request->input('tanggal_akhir')){
                $pinjam_mobil = new Pinjam_Mobil();
                $pinjam_mobil->id = Str::uuid();
                $pinjam_mobil->kegiatan = $request->input('kegiatan');
                $pinjam_mobil->tipe = $request->input('tipe');

                $pinjam_mobil->berulang_id = null;
                $pinjam_mobil->tanggal_awal = $request->input('tanggal_awal');
                $pinjam_mobil->tanggal_akhir = $request->input('tanggal_akhir');
                $pinjam_mobil->waktu_awal = $request->input('waktu_awal');
                $pinjam_mobil->waktu_akhir = $request->input('waktu_akhir');
                $pinjam_mobil->status = 'menunggu';
                $pinjam_mobil->pembuat_username = Auth::user()->username;

                if($have_mobil_peran){
                    $pinjam_mobil->status = 'disetujui';
                    $pinjam_mobil->confirm_username = Auth::user()->username;
                }
                $pinjam_mobil->save();

            }else if($request->input('tipe') == 'berulang'){
                $berulang_id = Str::uuid();

                $startDate = Carbon::parse($request->input('tanggal_awal'));
                $endDate = Carbon::parse($request->input('waktu_akhir'));
                $period = CarbonPeriod::create($startDate, $endDate);

                foreach ($period as $date) {
                    $pinjam_mobil = new Pinjam_Mobil();
                    $pinjam_mobil->id = Str::uuid();
                    $pinjam_mobil->kegiatan = $request->input('kegiatan');
                    $pinjam_mobil->tipe = $request->input('tipe');

                    $pinjam_mobil->berulang_id = $berulang_id;
                    $pinjam_mobil->tanggal_awal = $date->format('Y-m-d');
                    $pinjam_mobil->tanggal_akhir = $date->format('Y-m-d');
                    $pinjam_mobil->waktu_awal = $request->input('waktu_awal');
                    $pinjam_mobil->waktu_akhir = $request->input('waktu_akhir');
                    $pinjam_mobil->status = 'menunggu';
                    $pinjam_mobil->pembuat_username = Auth::user()->username;

                    if($have_mobil_peran){
                        $pinjam_mobil->status = 'disetujui';
                        $pinjam_mobil->confirm_username = Auth::user()->username;
                    }
                    $pinjam_mobil->save();
                }
            }
            DB::commit();
            return redirect()->route('pinjam-mobil-index')->with('success', 'Berhasil membuat peminjaman mobil!');
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return redirect()->route('pinjam-mobil-index')->with('error', 'Gagal membuat peminjaman mobil!');
        }
    }

    public function ubah(Request $request, Pinjam_Mobil $pinjamMobil)
    {
        $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal_awal'      => 'required|date',
            'tanggal_akhir'     => 'required|date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',
        ]);

        $have_mobil_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'mobil')
            ->whereNull('tahun', null)
            ->first();

        if(! ($have_mobil_peran || $pinjamMobil->pembuat_username == Auth::user()->username) ){
            return redirect()->route('pinjam-mobil-index')->with('error', 'Gagal mengubah peminjaman mobil!');
        }

        DB::beginTransaction();
        try
        {
            $pinjamMobil->delete();
            if($pinjamMobil->berulang_id){
                Pinjam_Mobil::where('berulang_id', $pinjamMobil->berulang_id)->delete();
            }

            if($request->input('tipe') == 'sekali' && $request->input('tanggal_awal') ==  $request->input('tanggal_akhir')){
                $pinjam_mobil = new Pinjam_Mobil();
                $pinjam_mobil->id = Str::uuid();
                $pinjam_mobil->kegiatan = $request->input('kegiatan');
                $pinjam_mobil->tipe = $request->input('tipe');

                $pinjam_mobil->berulang_id = null;
                $pinjam_mobil->tanggal_awal = $request->input('tanggal_awal');
                $pinjam_mobil->tanggal_akhir = $request->input('tanggal_akhir');
                $pinjam_mobil->waktu_awal = $request->input('waktu_awal');
                $pinjam_mobil->waktu_akhir = $request->input('waktu_akhir');
                $pinjam_mobil->status = 'menunggu';
                $pinjam_mobil->pembuat_username = Auth::user()->username;

                if($have_mobil_peran){
                    $pinjam_mobil->status = 'disetujui';
                    $pinjam_mobil->confirm_username = Auth::user()->username;
                }
                $pinjam_mobil->save();

            }else if($request->input('tipe') == 'berulang'){
                $berulang_id = Str::uuid();

                $startDate = Carbon::parse($request->input('tanggal_awal'));
                $endDate = Carbon::parse($request->input('waktu_akhir'));
                $period = CarbonPeriod::create($startDate, $endDate);

                foreach ($period as $date) {
                    $pinjam_mobil = new Pinjam_Mobil();
                    $pinjam_mobil->id = Str::uuid();
                    $pinjam_mobil->kegiatan = $request->input('kegiatan');
                    $pinjam_mobil->tipe = $request->input('tipe');

                    $pinjam_mobil->berulang_id = $berulang_id;
                    $pinjam_mobil->tanggal_awal = $date->format('Y-m-d');
                    $pinjam_mobil->tanggal_akhir = $date->format('Y-m-d');
                    $pinjam_mobil->waktu_awal = $request->input('waktu_awal');
                    $pinjam_mobil->waktu_akhir = $request->input('waktu_akhir');
                    $pinjam_mobil->status = 'menunggu';
                    $pinjam_mobil->pembuat_username = Auth::user()->username;

                    if($have_mobil_peran){
                        $pinjam_mobil->status = 'disetujui';
                        $pinjam_mobil->confirm_username = Auth::user()->username;
                    }
                    $pinjam_mobil->save();
                }
            }
            DB::commit();
            return redirect()->route('pinjam-mobil-index')->with('success', 'Berhasil mengubah peminjaman mobil!');
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return redirect()->route('pinjam-mobil-index')->with('error', 'Gagal mengubah peminjaman mobil!');
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
            ->whereNull('tahun', null)
            ->first();

        if(! ($have_mobil_peran || $pinjamMobil->pembuat_username == Auth::user()->username) ){
            return redirect()->route('pinjam-mobil-index')->with('error', 'Gagal menghapus peminjaman mobil!');
        }

        DB::beginTransaction();
        try
        {
            $pinjamMobil->delete();
            if($pinjamMobil->berulang_id){
                Pinjam_Mobil::where('berulang_id', $pinjamMobil->berulang_id)->delete();
            }
            DB::commit();
            return redirect()->route('pinjam-mobil-index')->with('success', 'Berhasil membuat peminjaman mobil!');
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return redirect()->route('pinjam-mobil-index')->with('error', 'Gagal membuat peminjaman mobil!');
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
            ->whereNull('tahun', null)
            ->first();

        if(! ($have_mobil_peran) ){
            return redirect()->route('pinjam-mobil-index')->with('error', 'Gagal approval peminjaman mobil!');
        }


        DB::beginTransaction();
        try
        {
            $pinjamMobil->status = $request->input('status');
            $pinjamMobil->confirm_username = Auth::user()->username;
            $pinjamMobil->save();
            DB::commit();
            return redirect()->route('pinjam-mobil-index')->with('success', 'Berhasil approval peminjaman mobil!');
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return redirect()->route('pinjam-mobil-index')->with('error', 'Gagal approval peminjaman mobil!');
        }
    }
}
