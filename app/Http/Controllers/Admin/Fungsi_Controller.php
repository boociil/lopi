<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Umum\Fungsi;
use App\Model\Umum\Pengumuman;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;
use Webpatser\Uuid\Uuid;

class Fungsi_Controller extends Controller
{
    private $fungsi_id, $fungsi;
    private $peran = 'pj';

    public function load_pertama($request)
    {
        $this->fungsi_id = $request->route('fungsi_id');
        if($this->fungsi_id){
            $this->fungsi = Fungsi::with('penanggung_jawab')
                ->where('id',  $this->fungsi_id)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
    }

    public function daftar(Request $request)
    {
       return Fungsi::with(['satuan_kerja_tahun', 'penanggung_jawab.pemilik_user_nama'])
            ->where('tahun', session('tahun_active'))
            ->get();
    }

    public function tambah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $satuan_kerja_tahun = Satuan_Kerja_Tahun::helper_getByUUID($request->satuan_kerja_tahun_id)->first();
            if(!$satuan_kerja_tahun){
                abort(404, 'Id tidak ditemukan');
            }
            if($satuan_kerja_tahun->type != 'prov'){
                abort(404, 'Id a tidak ditemukan');
            }
            $baru = Fungsi::helper_tambah($request, $satuan_kerja_tahun);

            if($request->penanggung_jawab){
                $baru_peran =  new Peran();
                $baru_peran->id =  (string) Uuid::generate();
                $baru_peran->tipe_peran = 'fungsi';
                $baru_peran->parent_id = $baru->id;
                $baru_peran->pemilik_username = $request->penanggung_jawab;
                $baru_peran->pemilik_tim_id = null;
                $baru_peran->pemilik_satuan_kerja_tahun_id = null;
                $baru_peran->tahun = $satuan_kerja_tahun->tahun;
                $baru_peran->peran = $this->peran;
                $baru_peran->save();

                Pengumuman::buat_untuk_peran_fungsi(0, $baru, $baru_peran);
            }

            DB::commit();
            return $baru;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }

    public function lihat(Request $request)
    {
        $this->load_pertama($request);
        if(!$this->fungsi){
            abort(404, 'Id tidak ditemukan');
        }
        return ['fungsi' => $this->fungsi
            , 'daftar_satuan_kerja_tahun' => Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))->get()
            , 'daftar_peran' =>  Peran::with('pemilik_user_nama')
                ->where('tipe_peran', 'satuan_kerja_tahun')
                ->where('parent_id', $this->fungsi->satuan_kerja_tahun_id)
                ->get()];
    }

    public function ubah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $satuan_kerja_tahun = Satuan_Kerja_Tahun::helper_getByUUID($request->satuan_kerja_tahun_id)->first();
            if(!$satuan_kerja_tahun){
                abort(404, 'Id a tidak ditemukan');
            }
            if($satuan_kerja_tahun->type != 'prov'){
                abort(404, 'Id a tidak ditemukan');
            }
            $this->load_pertama($request);
            if(!$this->fungsi){
                abort(404, 'Id b tidak ditemukan');
            }
            $this->fungsi->helper_ubah($request, $satuan_kerja_tahun);
            if($this->fungsi->penanggung_jawab || $request->penanggung_jawab){
                if($this->fungsi->penanggung_jawab
                    && $this->fungsi->penanggung_jawab->pemilik_username != $request->penanggung_jawab){
                    $peran_lama = $this->fungsi->penanggung_jawab;
                    $peran_lama->delete();
                    Pengumuman::buat_untuk_peran_fungsi(2, $this->fungsi, $peran_lama);

                    $baru_peran =  new Peran();
                    $baru_peran->id =  (string) Uuid::generate();
                    $baru_peran->tipe_peran = 'fungsi';
                    $baru_peran->parent_id = $this->fungsi->id;
                    $baru_peran->pemilik_username = $request->penanggung_jawab;
                    $baru_peran->pemilik_tim_id = null;
                    $baru_peran->pemilik_satuan_kerja_tahun_id = null;
                    $baru_peran->tahun = $satuan_kerja_tahun->tahun;
                    $baru_peran->peran = $this->peran;
                    $baru_peran->save();
                    Pengumuman::buat_untuk_peran_fungsi(0, $this->fungsi, $baru_peran);
                }else if(!$this->fungsi->penanggung_jawab && $request->penanggung_jawab){
                    $baru_peran =  new Peran();
                    $baru_peran->id =  (string) Uuid::generate();
                    $baru_peran->tipe_peran = 'fungsi';
                    $baru_peran->parent_id = $this->fungsi->id;
                    $baru_peran->pemilik_username = $request->penanggung_jawab;
                    $baru_peran->pemilik_tim_id = null;
                    $baru_peran->pemilik_satuan_kerja_tahun_id = null;
                    $baru_peran->tahun = $satuan_kerja_tahun->tahun;
                    $baru_peran->peran = $this->peran;
                    $baru_peran->save();
                    Pengumuman::buat_untuk_peran_fungsi(0, $this->fungsi, $baru_peran);
                }
            }
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }

    public function hapus(Request $request, $username)
    {
        DB::beginTransaction();
        try
        {
            $this->load_pertama($request);
            if(!$this->fungsi){
                abort(404, 'Id tidak ditemukan');
            }
            $this->fungsi->helper_hapus($request);
            Tim::where('fungsi_id', $this->fungsi->id)->update(['fungsi_id' => null]);
            Peran::where('parent_id', $this->fungsi->id)->delete();
            Pengumuman::where('parent_id', $this->fungsi->id)->delete();
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }
}
