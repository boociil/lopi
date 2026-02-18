<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Arsip_Akses;
use App\Model\Lopi\Arsip_Arsip;
use App\Model\Lopi\Arsip_KlasifikasiArsip;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\User;
use Carbon\Carbon;
use Exception;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class Arsip_AdminController extends Controller
{

    private function check_auth(){
        $have_arsip_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'arsip')
            ->first();

        if(! ($have_arsip_peran) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal!',
            ]);
        }
    }

    public function arsip_tambah(Request $request)
    {
        $this->check_auth();

        $validated = $request->validate([
            'klasifikasi_arsip_id' => 'exists:siakip2_arsip_klasifikasi,id',
            'unit_cipta_arsip' => 'exists:siakip2_satuan_kerja_tahun,id',
            'tingkat_perkembangan' => 'in:Asli,Salinan,Copy',
            'media_arsip' => 'in:Digital,Kertas,Foto',
            'kondisi_arsip' => 'in:baik,kurang_baik',
            'uraian_arsip' => 'required',
            'kurun_waktu_awal' => 'required|digits:4',
            'kurun_waktu_akhir' => 'nullable|digits:4',
            'file'  => 'required|file|max:51200|mimes:jpeg,png,pdf',
            // 'jumlah' => 'nullable|string|max:50',
            // 'lokasi_simpan' => 'nullable|string|max:255',
        ]);

        if (!$request->file('file')->isValid()) {
             throw ValidationException::withMessages([
                '_status' => 'Gagal!',
            ]);
        }

        DB::beginTransaction();
        try{
            $file_name = Str::uuid().'--'.$request->file('file')->getClientOriginalName();
            $file_path = Carbon::today()->format('Y-m-d'). '/'. $file_name;

            $request->file->storeAs(Carbon::today()->format('Y-m-d'), $file_name, 'minio');

            $arsip = new Arsip_Arsip();
            $arsip->klasifikasi_arsip_id = $request->input('klasifikasi_arsip_id');
            $arsip->unit_cipta_arsip = $request->input('unit_cipta_arsip');
            $arsip->tingkat_perkembangan = $request->input('tingkat_perkembangan');
            $arsip->media_arsip = $request->input('media_arsip');
            $arsip->kondisi_arsip = $request->input('kondisi_arsip');
            $arsip->uraian_arsip = $request->input('uraian_arsip');
            $arsip->kurun_waktu_awal = $request->input('kurun_waktu_awal');
            $arsip->kurun_waktu_akhir = $request->input('kurun_waktu_akhir');
            $arsip->arsip_filename = $file_name;
            $arsip->arsip_path = $file_path;
            $arsip->save();



            DB::commit();
            return [
                "_status" => 'Berhasil membuat arsip!',
                "data" => $arsip
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal membuat arsip!',
            ]);
        }

    }

    public function arsip_ubah(Request $request, Arsip_Arsip $arsip)
    {
         $this->check_auth();

        $validated = $request->validate([
            'klasifikasi_arsip_id' => 'exists:siakip2_arsip_klasifikasi,id',
            'unit_cipta_arsip' => 'exists:siakip2_satuan_kerja_tahun,id',
            'tingkat_perkembangan' => 'in:Asli,Salinan,Copy',
            'media_arsip' => 'in:Digital,Kertas,Foto',
            'kondisi_arsip' => 'in:baik,kurang_baik',
            'uraian_arsip' => 'required',
            'kurun_waktu_awal' => 'required|digits:4',
            'kurun_waktu_akhir' => 'nullable|digits:4',
            // 'file'  => 'required|file|max:51200|mimes:jpeg,png,pdf',
            // 'jumlah' => 'nullable|string|max:50',
            // 'lokasi_simpan' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try{
            $arsip->klasifikasi_arsip_id = $request->input('klasifikasi_arsip_id');
            $arsip->unit_cipta_arsip = $request->input('unit_cipta_arsip');
            $arsip->tingkat_perkembangan = $request->input('tingkat_perkembangan');
            $arsip->media_arsip = $request->input('media_arsip');
            $arsip->kondisi_arsip = $request->input('kondisi_arsip');
            $arsip->uraian_arsip = $request->input('uraian_arsip');
            $arsip->kurun_waktu_awal = $request->input('kurun_waktu_awal');
            $arsip->kurun_waktu_akhir = $request->input('kurun_waktu_akhir');
            $arsip->save();

            DB::commit();
            return [
                "_status" => 'Berhasil membuat arsip!',
                "data" => $arsip
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal membuat arsip!',
            ]);
        }

    }

    public function arsip_hapus(Request $request, Arsip_Arsip $arsip)
    {
        $this->check_auth();

        DB::beginTransaction();
        try{
            Storage::disk('minio')->delete(
                $arsip->arsip_path
            );
            Arsip_Akses::where('arsip_id', $arsip->id)->delete();
            $arsip->delete();
            DB::commit();
            return [
                "_status" => 'Berhasil menghapus arsip!',
                "data" => $arsip
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal menghapus arsip!',
            ]);
        }
    }


    public function klasifikasi_index(Request $request)
    {
        $this->check_auth();

        return Inertia::render('arsip/klasifikasi', [
            "arsip_klasifikasi_" => Arsip_KlasifikasiArsip::all(),
        ]);

    }

    public function klasifikasi_tambah(Request $request)
    {
        $this->check_auth();
        $klasifikasi = new Arsip_KlasifikasiArsip();
        $klasifikasi->kode = $request->kode;
        $klasifikasi->nama = $request->nama;
        $klasifikasi->save();

        return response()->json([
            'message' => 'Data klasifikasi berhasil diubah',
            'data' => $klasifikasi
        ], 200);
    }
    public function klasifikasi_ubah(Arsip_KlasifikasiArsip $klasifikasi, Request $request)
    {
        $this->check_auth();
        $klasifikasi->kode = $request->kode;
        $klasifikasi->nama = $request->nama;
        $klasifikasi->save();

        return response()->json([
            'message' => 'Data klasifikasi berhasil diubah',
            'data' => $klasifikasi
        ], 200);
    }
    public function klasifikasi_hapus(Arsip_KlasifikasiArsip $klasifikasi)
    {
        $this->check_auth();
        $klasifikasi->delete();

        return response()->json([
            'message' => 'Data klasifikasi berhasil dihapus',
            'data' => $klasifikasi
        ], 200);
    }

    public function akses_daftar(Request $request){
        $this->check_auth();

        $limit = 20;
        $page = 1;
        if($request->has('limit')){
            $limit = $request->input('limit');
        }
        if($request->has('page')){
            $page = $request->input('page');
        }

         if($request->isMethod('get')){
            return Inertia::render('arsip/akses', [
                "akses" => Arsip_Akses::with(["arsip"])->offset(($page - 1)*$limit)
                    ->limit($limit)->orderBy('id', 'desc')->get(),
                "total_akses" => Arsip_Akses::count(),
                "klasifikasi" => Arsip_KlasifikasiArsip::all(),
                "satuan_kerja_tahun" => Satuan_Kerja_Tahun::where('kode_satuan_kerja', '7600')->get(),
                "user" => User::all()
            ]);
        }else if($request->isMethod('post')){
            return [
                "akses" => Arsip_Akses::with(["arsip"])->offset(($page - 1)*$limit)
                    ->limit($limit)->get(),
                "total_akses" => Arsip_Akses::count(),
            ];
        }
    }

    public function akses_approval(Arsip_Akses $akses, Request $request){
        $validated = $request->validate([
            'status'       => 'in:disetujui,ditolak',
        ]);

        $akses->status = $request->input('status');
        $akses->save();

        return response()->json([
            'message' => 'Data berhasil diaprove',
            'data' => $akses
        ], 200);
    }
}
