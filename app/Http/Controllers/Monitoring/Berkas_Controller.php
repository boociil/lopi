<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Model\Monitoring_Kegiatan\Monitoring_Kegiatan_Pelaksana;
use App\Model\Monitoring_Kegiatan\Monitoring_Kegiatan_Pelaksana_Berkas;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class Berkas_Controller extends Controller
{
    private $id_monitoring_kegiatan_pelaksana, $monitoring_kegiatan_pelaksana;
    private $id_berkas, $berkas;
    private $have_auth = ['lihat' => true, 'text' => true, 'pengajuan' => false, 'persetujuan' => false];

    public function load_data_from_param($request)
    {
        $this->id_monitoring_kegiatan_pelaksana = $request->route('monitoring_kegiatan_pelaksana_id');
        $this->monitoring_kegiatan_pelaksana = Monitoring_Kegiatan_Pelaksana::with(['master', 'kegiatan'])
                        ->where('id', $this->id_monitoring_kegiatan_pelaksana)
                        ->where('tahun', session('tahun_active'))
                        ->first();

        if(!$this->monitoring_kegiatan_pelaksana){
            abort(404, 'id tidak ditemukan');
        }

        $this->id_berkas = $request->route('id_berkas');
        $this->berkas = Monitoring_Kegiatan_Pelaksana_Berkas::where('id', $this->id_berkas)
                        ->where('monitoring_kegiatan_pelaksana_id', $this->id_monitoring_kegiatan_pelaksana)
                        ->first();

        $pengajuan = false;
        $persetujuan = false;

        if(Peran::check_user_have_role('satuan_kerja_tahun', $this->monitoring_kegiatan_pelaksana->kegiatan->pemberi_satuan_kerja_tahun_id
            , Auth::user()->username, ['admin', 'pimpinan'])){
            $pengajuan = true;
            $persetujuan = true;
        }
        if(Peran::check_user_have_role('tim', $this->monitoring_kegiatan_pelaksana->kegiatan->pemberi_tim_id
            , Auth::user()->username, ['admin', 'ketua'])){
            $pengajuan = true;
            $persetujuan = true;
        }

        if(!$pengajuan){
            if(Peran::check_user_have_role('satuan_kerja_tahun', $this->monitoring_kegiatan_pelaksana->pelaksana_satuan_kerja_tahun_id
                , Auth::user()->username, ['admin', 'pimpinan'])){
                $pengajuan = true;
            }
            if(Peran::check_user_have_role('tim', $this->monitoring_kegiatan_pelaksana->pelaksana_tim_id
                , Auth::user()->username, ['admin', 'ketua'])){
                $pengajuan = true;
            }
        }

        $this->have_auth['pengajuan'] = $pengajuan;
        $this->have_auth['persetujuan'] = $persetujuan;
    }

    public function daftar(Request $request)
    {
        $this->load_data_from_param($request);
        $satuan_kerja_tahun_pelaksana = Satuan_Kerja_Tahun::where('id', $this->monitoring_kegiatan_pelaksana->pelaksana_satuan_kerja_tahun_id)->first();
        return ['monitoring_kegiatan_pelaksana' => $this->monitoring_kegiatan_pelaksana
            , 'daftar_berkas' => Monitoring_Kegiatan_Pelaksana_Berkas::with(['file'])
                        ->where('monitoring_kegiatan_pelaksana_id', $this->id_monitoring_kegiatan_pelaksana)
                        ->where('tahun', session('tahun_active'))
                        ->orderBy('created_at', 'asc')
                        ->get()
            , 'satuan_kerja_tahun_pelaksana' => $satuan_kerja_tahun_pelaksana
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

            Monitoring_Kegiatan_Pelaksana_Berkas::helper_tambah($this->monitoring_kegiatan_pelaksana, $request, $this->have_auth);

            DB::commit();
            return ['monitoring_kegiatan_pelaksana' => $this->monitoring_kegiatan_pelaksana
                , 'daftar_berkas' => Monitoring_Kegiatan_Pelaksana_Berkas::with(['file'])
                            ->where('monitoring_kegiatan_pelaksana_id', $this->id_monitoring_kegiatan_pelaksana)
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
            $this->monitoring_kegiatan_pelaksana->status = 'selesai';
            $this->monitoring_kegiatan_pelaksana->penyelesaian($this->berkas->nilai_baru['realisasi']
                , $this->berkas->nilai_baru['tanggal_realisasi'], $this->berkas->nilai_baru['kualitas']);
            DB::commit();
            return ['monitoring_kegiatan_pelaksana' => $this->monitoring_kegiatan_pelaksana
                , 'daftar_berkas' => Monitoring_Kegiatan_Pelaksana_Berkas::with(['file'])
                            ->where('monitoring_kegiatan_pelaksana_id', $this->id_monitoring_kegiatan_pelaksana)
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
            $this->monitoring_kegiatan_pelaksana->status = 'tolak';
            $this->monitoring_kegiatan_pelaksana->save();
            $this->berkas->status_approval = 1;
            $this->berkas->save();
            DB::commit();
            return ['monitoring_kegiatan_pelaksana' => $this->monitoring_kegiatan_pelaksana
                , 'daftar_berkas' => Monitoring_Kegiatan_Pelaksana_Berkas::with(['file'])
                            ->where('monitoring_kegiatan_pelaksana_id', $this->id_monitoring_kegiatan_pelaksana)
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
