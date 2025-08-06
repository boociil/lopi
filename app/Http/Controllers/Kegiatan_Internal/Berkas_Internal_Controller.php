<?php

namespace App\Http\Controllers\Kegiatan_Internal;

use App\Http\Controllers\Controller;
use App\Model\Kegiatan_Internal\Kegiatan_Internal_Berkas;
use App\Model\Kegiatan_Internal\Kegiatan_Internal_Kegiatan;
use App\Model\Umum\Peran;
use App\Model\Umum\Tim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;

class Berkas_Internal_Controller extends Controller
{
    private $kegiatan_internal_id, $kegiatan_internal;
    private $id_berkas, $berkas;
    private $have_auth = ['lihat' => true, 'text' => true, 'pengajuan' => false, 'persetujuan' => false];

    public function load_data_from_param($request)
    {
        $this->kegiatan_internal_id = $request->route('kegiatan_internal_id');
        $this->kegiatan_internal = Kegiatan_Internal_Kegiatan::with(['master'])
                        ->where('id', $this->kegiatan_internal_id)
                        ->where('tahun', session('tahun_active'))
                        ->first();

        if(!$this->kegiatan_internal){
            abort(404, 'id tidak ditemukan');
        }

        $this->id_berkas = $request->route('id_berkas');
        $this->berkas = Kegiatan_Internal_Berkas::where('id', $this->id_berkas)
                        ->where('kegiatan_internal_kegiatan_id', $this->kegiatan_internal_id)
                        ->first();

        $pengajuan = false;
        $persetujuan = false;

        if($this->kegiatan_internal->pemberi_satuan_kerja_tahun_id &&
            Peran::check_user_have_role('satuan_kerja_tahun', $this->kegiatan_internal->pemberi_satuan_kerja_tahun_id
                , Auth::user()->username, ['admin', 'pimpinan'])){
            $pengajuan = true;
            $persetujuan = true;
        }
        if($this->kegiatan_internal->pemberi_fungsi_id &&
            Peran::check_user_have_role('fungsi', $this->kegiatan_internal->pemberi_fungsi_id
                , Auth::user()->username, ['pj'])){
            $pengajuan = true;
            $persetujuan = true;
        }
        if(Peran::check_user_have_role('tim', $this->kegiatan_internal->pemberi_tim_id
            , Auth::user()->username, ['admin', 'ketua'])){
            $pengajuan = true;
            $persetujuan = true;
        }

        if(!$pengajuan){
            if(Peran::check_user_have_role('tim', $this->kegiatan_internal->pelaksana_tim_id
                , Auth::user()->username, ['admin', 'ketua', 'anggota'])){
                $pengajuan = true;
            }
        }

        $this->have_auth['pengajuan'] = $pengajuan;
        $this->have_auth['persetujuan'] = $persetujuan;
    }

    public function daftar(Request $request)
    {
        $this->load_data_from_param($request);
        $kegiatan_internal_pelaksana_tim_id = Tim::where('id', $this->kegiatan_internal->pelaksana_tim_id)->first();
        return ['kegiatan_internal' => $this->kegiatan_internal
            , 'daftar_berkas' => Kegiatan_Internal_Berkas::with(['file'])
                        ->where('kegiatan_internal_kegiatan_id', $this->kegiatan_internal_id)
                        ->where('tahun', session('tahun_active'))
                        ->orderBy('created_at', 'asc')
                        ->get()
            , 'kegiatan_internal_pelaksana_tim_id' => $kegiatan_internal_pelaksana_tim_id
            , 'have_auth' => $this->have_auth];
    }

    public function tambah(Request $request)
    {
        $this->load_data_from_param($request);
        DB::beginTransaction();
        try
        {

            if($request->type_berkas == 'persetujuan'){
                abort(403);
            }

            Kegiatan_Internal_Berkas::helper_tambah($this->kegiatan_internal, $request, $this->have_auth);

            DB::commit();
            return ['kegiatan_internal' => $this->kegiatan_internal
                , 'daftar_berkas' => Kegiatan_Internal_Berkas::with(['file'])
                            ->where('kegiatan_internal_kegiatan_id', $this->kegiatan_internal_id)
                            ->where('tahun', session('tahun_active'))
                            ->orderBy('created_at', 'asc')
                            ->get()
                , 'have_auth' => $this->have_auth];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function setujui(Request $request)
    {
        $this->load_data_from_param($request);
        DB::beginTransaction();
        try
        {
            if(!$this->berkas){
                abort(404, 'id tidak ditemukan');
            }
            if(!$this->have_auth['persetujuan']){
                abort(403);
            }
            $this->berkas->helper_approval($request->kualitas);
            $this->kegiatan_internal->status = 'selesai';
            $this->kegiatan_internal->penyelesaian($this->berkas->nilai_baru['realisasi']
                , $this->berkas->nilai_baru['tanggal_realisasi'], $this->berkas->nilai_baru['kualitas']);
            DB::commit();
            return ['kegiatan_internal' => $this->kegiatan_internal
                , 'daftar_berkas' => Kegiatan_Internal_Berkas::with(['file'])
                            ->where('kegiatan_internal_kegiatan_id', $this->kegiatan_internal_id)
                            ->where('tahun', session('tahun_active'))
                            ->orderBy('created_at', 'asc')
                            ->get()
                , 'have_auth' => $this->have_auth];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }
    public function tolak(Request $request)
    {
        $this->load_data_from_param($request);
        DB::beginTransaction();
        try
        {
            if(!$this->berkas){
                abort(404, 'id tidak ditemukan');
            }
            if(!$this->have_auth['persetujuan']){
                abort(403);
            }
            $this->kegiatan_internal->status = 'tolak';
            $this->kegiatan_internal->save();
            $this->berkas->status_approval = 1;
            $this->berkas->save();
            DB::commit();
            return ['kegiatan_internal' => $this->kegiatan_internal
                , 'daftar_berkas' => Kegiatan_Internal_Berkas::with(['file'])
                            ->where('kegiatan_internal_kegiatan_id', $this->kegiatan_internal_id)
                            ->where('tahun', session('tahun_active'))
                            ->orderBy('created_at', 'asc')
                            ->get()
                , 'have_auth' => $this->have_auth];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }
}
