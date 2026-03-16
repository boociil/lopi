<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Umum\Peran;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class KMS_Controller extends Controller
{

    public function index(Request $request)
    {
         return Inertia::render('cek_cuti');
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'password'          => 'required|string',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal'           => 'date',
            'tanggal_awal'      => 'date',
            'tanggal_akhir'     => 'date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',
            'zoom_akun'         => 'nullable|in:statsulbar,sp2020'
        ]);


        DB::beginTransaction();
        try
        {



            DB::commit();
            return [
                "_status" => 'Berhasil membuat peminjaman zoom!',
                "data" => $pinjam_zoom
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal membuat peminjaman zoom!',
            ]);
        }
    }

    public function ubah(Request $request, Pinjam_Zoom $pinjamZoom)
    {
        $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal'           => 'date',
            'tanggal_awal'      => 'date',
            'tanggal_akhir'     => 'date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',
            'zoom_akun'         => 'nullable|in:statsulbar,sp2020'
        ]);



        DB::beginTransaction();
        try
        {

            DB::commit();
            return [
                "_status" => 'Berhasil mengubah peminjaman zoom!',
                "data" => $pinjamZoom
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal mengubah peminjaman zoom!',
            ]);
        }
    }


    public function hapus(Pinjam_Zoom $pinjamZoom)
    {
        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        DB::beginTransaction();
        try
        {
            $pinjamZoom->helper_hapus($have_zoom_peran);
            DB::commit();
            return [
                "_status" => 'Berhasil menghapus peminjaman zoom!'
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal menghapus peminjaman zoom!',
            ]);
        }
    }


}
