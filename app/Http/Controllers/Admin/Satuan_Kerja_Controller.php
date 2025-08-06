<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Umum\Satuan_Kerja;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class Satuan_Kerja_Controller extends Controller
{
    private $satuan_kerja_id, $satuan_kerja;

    public function load_pertama($request)
    {
        $this->satuan_kerja_id = $request->route('satuan_kerja_id');
        if($this->satuan_kerja_id){
            $this->satuan_kerja = Satuan_Kerja::where('id',  $this->satuan_kerja_id)
                ->first();
        }
    }


    public function daftar(Request $request)
    {
        return Satuan_Kerja::all();
    }

    public function tambah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $baru = Satuan_Kerja::helper_tambah($request);
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
        if(!$this->satuan_kerja){
            abort(404, 'Id tidak ditemukan');
        }
        return $this->satuan_kerja;
    }

    public function ubah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $this->load_pertama($request);
            if(!$this->satuan_kerja){
                abort(404, 'Id tidak ditemukan');
            }
            $this->satuan_kerja->helper_ubah($request);
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }

    public function hapus(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $this->load_pertama($request);
            if(!$this->satuan_kerja){
                abort(404, 'Id tidak ditemukan');
            }
            $this->satuan_kerja->helper_hapus($request);
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }

    public function active(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $this->load_pertama($request);
            if(!$this->satuan_kerja){
                abort(404, 'Id tidak ditemukan');
            }
            $this->satuan_kerja->helper_active();
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }
    public function deactive(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $this->load_pertama($request);
            if(!$this->satuan_kerja){
                abort(404, 'Id tidak ditemukan');
            }
            $this->satuan_kerja->helper_deactive();
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }
}
