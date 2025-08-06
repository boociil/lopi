<?php

namespace App\Http\Controllers\Jurnal_Harian;

use App\Http\Controllers\Controller;
use App\Model\Jurnal_Harian\Jurnal_Harian_Berkas;
use App\Model\Jurnal_Harian\Jurnal_Harian_Item;
use App\Model\Umum\Peran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;

class Berkas_Jurnal_Harian_Controller extends Controller
{
    private $jurnal_harian_item_id, $jurnal_harian_item;
    private $id_berkas, $berkas;
    private $have_auth = ['lihat' => true, 'text' => false, 'pengajuan' => false, 'persetujuan' => false];

    public function load_pertama($request)
    {
        $this->jurnal_harian_item_id = $request->route('jurnal_harian_item_id');
        $this->jurnal_harian_item = Jurnal_Harian_Item::with('proyek')->where('id', $this->jurnal_harian_item_id)
                        ->where('tahun', session('tahun_active'))
                        ->first();

        if(!$this->jurnal_harian_item){
            abort(404, 'id tidak ditemukan');
        }

        $this->id_berkas = $request->route('id_berkas');
        $this->berkas = Jurnal_Harian_Berkas::where('id', $this->id_berkas)
                        ->where('jurnal_harian_item_id', $this->jurnal_harian_item_id)
                        ->first();

        $pengajuan = false;
        $persetujuan = false;

        $peran_count = Peran::where('pemilik_username', Auth::user()->username)
            ->where(function($query){
                $query->orWhere('parent_id', $this->jurnal_harian_item->satuan_kerja_tahun_id);
                $query->orWhere('parent_id', $this->jurnal_harian_item->fungsi_id);
                $query->orWhere('parent_id', $this->jurnal_harian_item->tim_id);
                $query->orWhere('parent_id', $this->jurnal_harian_item->jurnal_harian_proyek_id);
            })
            ->count();
        
        if($peran_count > 0){
            $this->have_auth['text'] = true;
        }
        if($this->jurnal_harian_item->pelaksana_username == Auth::user()->username){
            $this->have_auth['text'] = true;
        }

        $this->have_auth['pengajuan'] = $pengajuan;
        $this->have_auth['persetujuan'] = $persetujuan;
    }

    public function daftar(Request $request)
    {
        $this->load_pertama($request);
        return ['jurnal_harian_item' => $this->jurnal_harian_item
            , 'daftar_berkas' => Jurnal_Harian_Berkas::with(['file'])
                        ->where('jurnal_harian_item_id', $this->jurnal_harian_item_id)
                        ->where('tahun', session('tahun_active'))
                        ->orderBy('created_at', 'asc')
                        ->get()
            , 'have_auth' => $this->have_auth];
    }

    public function tambah(Request $request)
    {
        $this->load_pertama($request);
        DB::beginTransaction();
        try
        {
            Jurnal_Harian_Berkas::helper_tambah($this->jurnal_harian_item, $request, $this->have_auth);

            DB::commit();
            return ['jurnal_harian_item' => $this->jurnal_harian_item
                , 'daftar_berkas' => Jurnal_Harian_Berkas::with(['file'])
                            ->where('jurnal_harian_item_id', $this->jurnal_harian_item_id)
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
