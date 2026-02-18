<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Arsip_Akses;
use App\Model\Lopi\Arsip_Arsip;
use App\Model\Lopi\Arsip_KlasifikasiArsip;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Exception;

use Inertia\Inertia;

class Arsip_Controller extends Controller
{
    public function index(Request $request)
    {
        $limit = 20;
        $page = 1;
        if($request->has('limit')){
            $limit = $request->input('limit');
        }
        if($request->has('page')){
            $page = $request->input('page');
        }

        $have_arsip_peran = Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'arsip')
            ->first();

        if($request->input('tab') == 'akses'){
            return [
                "akses" => Arsip_Akses::with(["arsip"])
                        ->where('username', Auth::user()->username)
                        ->offset(($page - 1)*$limit)
                        ->limit($limit)
                        ->orderBy('id', 'desc')
                        ->get(),
                 "total_akses" => Arsip_Akses::where('username', Auth::user()->username)
                        ->count(),
            ];
        }else{
            if($request->isMethod('get')){
                return Inertia::render('arsip/index', [
                    "have_arsip_peran" => $have_arsip_peran,
                    "arsip" => Arsip_Arsip::offset(($page - 1)*$limit)
                        ->limit($limit)
                        ->orderBy('id', 'desc')
                        ->get(),
                    "total_arsip" => Arsip_Arsip::count(),
                    "klasifikasi" => Arsip_KlasifikasiArsip::all(),
                    "satuan_kerja_tahun" => Satuan_Kerja_Tahun::where('kode_satuan_kerja', '7600')->get()
                ]);
            }else if($request->isMethod('post')){
                return [
                    "arsip" => Arsip_Arsip::orderBy('id', 'desc')
                        ->offset(($page - 1)*$limit)
                        ->limit($limit)
                        ->get(),
                    "total_arsip" => Arsip_Arsip::count(),
                ];
            }
        }
    }

    public function akses_ajukan(Arsip_Arsip $arsip)
    {
        DB::beginTransaction();
        try{
            Arsip_Akses::where('arsip_id', $arsip->id)->where('username', Auth::user()->username)->delete();

            $arsip_akses = new Arsip_Akses();
            $arsip_akses->arsip_id = $arsip->id;
            $arsip_akses->username = Auth::user()->username;
            $arsip_akses->save();

            DB::commit();
            return [
                "_status" => 'Berhasil ajukan akses arsip!',
                "data" => $arsip_akses
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal ajukan akses arsip!',
            ]);
        }
    }

     public function akses_buka(Arsip_Arsip $arsip)
    {
        $have_arsip_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'arsip')
            ->first();

        $akses = Arsip_Akses::where('arsip_id', $arsip->id)->where('username', Auth::user()->username)->where('status', 'disetujui')->first();

        if(!($akses || $have_arsip_peran)){
            throw ValidationException::withMessages([
                '_status' => 'Gagal buka arsip!',
            ]);
        }

        $headers = [
            'Content-Disposition' => 'attachment; filename="' . $arsip->uraian_arsip . '"',
        ];

        return response()->streamDownload(function () use ($arsip) {
            $stream = Storage::disk('minio')->readStream($arsip->arsip_path);
            fpassthru($stream); // Passes the stream directly to the output buffer
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $arsip->arsip_filename, $headers);
    }

}
