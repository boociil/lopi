<?php

namespace App\Http\Controllers\Admin;

use App\Model\Umum\Satuan_Kerja;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use App\Model\Umum\User;
use App\Model\Umum\Pengumuman;
use App\Model\Umum\Peran;
use App\Model\Jurnal_Harian\Jurnal_Harian_Proyek;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Exception;

use Webpatser\Uuid\Uuid;

class Satuan_Kerja_Tahun_Controller extends Controller
{
    private $satuan_kerja_tahun_id, $satuan_kerja_tahun;
    private $peran_id, $peran;
    private $list_peran = ['admin', 'pimpinan', 'anggota'];

    public function load_data_from_param($request)
    {
        $this->satuan_kerja_tahun_id = $request->route('satuan_kerja_tahun_id');
        if($this->satuan_kerja_tahun_id){
            $this->satuan_kerja_tahun = Satuan_Kerja_Tahun::where('id',  $this->satuan_kerja_tahun_id)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
        $this->peran_id = $request->route('peran_id');
        if($this->peran_id){
            $this->peran = Peran::helper_getByUUID($this->peran_id)
                ->where('parent_id', $this->satuan_kerja_tahun_id)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
    }

    private function get_permission_global_satuan_kerja_tahun()
    {
        $ada_global = false;
        $satuan_kerja_tahun_id_as_admin = [];

        $peran_admin_global_atau_satuan_kerja_tahun = Peran::where('pemilik_username', Auth::user()->username)
            ->where(function ($query) {
                $query->orWhere(function ($query_0){
                    $query_0->where('tipe_peran', 'global');
                    $query_0->where('peran', 'admin');
                    $query_0->where('tahun', null);
                });
                $query->orWhere(function ($query_0){
                    $query_0->where('tipe_peran', 'satuan_kerja_tahun');
                    $query_0->where('peran', 'admin');
                    $query_0->where('tahun', session('tahun_active'));
                });
            })
            ->get();
        foreach($peran_admin_global_atau_satuan_kerja_tahun as $item){
            if($item->tipe_peran == 'global' && $item->peran == 'admin' && is_null($item->tahun)){
                $ada_global = true;
            }
            if($item->tipe_peran == 'satuan_kerja_tahun' && $item->peran == 'admin' && $item->tahun == session('tahun_active')){
                array_push($satuan_kerja_tahun_id_as_admin, $item->parent_id);
            }
        }

        return ['ada_global' => $ada_global, 'satuan_kerja_tahun_id' => $satuan_kerja_tahun_id_as_admin];
    }

    public function daftar(Request $request)
    {
        $return_func = $this->get_permission_global_satuan_kerja_tahun();
        if($return_func['ada_global']){
            return Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))->get();
        }else{
            return Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->whereIn('id', $return_func['satuan_kerja_tahun_id'])
                ->get();
        }
    }

    public function tambah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $satuan_kerja = Satuan_Kerja::helper_getByUUID($request->satuan_kerja_id)->first();
            if(!$satuan_kerja){
                abort(404, 'Id tidak ditemukan');
            }
            $baru = Satuan_Kerja_Tahun::helper_tambah($satuan_kerja, $request);
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
        $return_func = $this->get_permission_global_satuan_kerja_tahun();
        return [
            'satuan_kerja_tahun' => Satuan_Kerja_Tahun::where('id', $request->satuan_kerja_tahun_id)->first(),
            'satuan_kerja' => Satuan_Kerja::all(),
            'peran_func' => $return_func
        ];
    }

    public function ubah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $satuan_kerja = Satuan_Kerja::helper_getByUUID($request->satuan_kerja_id)->first();
            if(!$satuan_kerja){
                abort(404, 'Id tidak ditemukan');
            }
            $this->load_data_from_param($request);
            if(!$this->satuan_kerja_tahun){
                abort(404, 'Id tidak ditemukan');
            }
            $this->satuan_kerja_tahun->helper_ubah($satuan_kerja, $request);
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
            $this->load_data_from_param($request);
            if(!$this->satuan_kerja_tahun){
                abort(404, 'Id tidak ditemukan');
            }
            $this->satuan_kerja_tahun->helper_hapus($request);
            Peran::where('parent_id', $this->satuan_kerja_tahun->id)->delete();
            Pengumuman::where('parent_id', $this->satuan_kerja_tahun->id)->delete();
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }

    public function tim_daftar(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->satuan_kerja_tahun){
            abort(404, 'Id tidak ditemukan');
        }
        return Tim::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id)
            ->where('tahun', session('tahun_active'))
            ->get();
    }

    public function peran_daftar(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->satuan_kerja_tahun){
            abort(404, 'Id tidak ditemukan');
        }
        if(!$request->limit || !$request->page ){
            return ['daftar' =>  Peran::with('pemilik_user_nama.photo_url')
                    ->where('tipe_peran', 'satuan_kerja_tahun')
                    ->where('parent_id', $this->satuan_kerja_tahun_id)
                    ->orderBy('peran_number')
                    ->get()
                , 'total' => Peran::where('tipe_peran', 'satuan_kerja_tahun')
                    ->where('parent_id', $this->satuan_kerja_tahun_id)->count()];
        }else{
            $search_term = $request->search_term;
            return ['daftar' =>  Peran::with('pemilik_user_nama.photo_url')
                        ->where('tipe_peran', 'satuan_kerja_tahun')
                        ->where('parent_id', $this->satuan_kerja_tahun_id)
                        ->where(function($query_like) use ($search_term){
                            $query_like->where('pemilik_username', 'LIKE', '%'.$search_term.'%')
                                ->orWhere('pemilik_tim_id', 'LIKE', '%'.$search_term.'%')
                                ->orWhere('pemilik_satuan_kerja_tahun_id', 'LIKE', '%'.$search_term.'%');
                        })
                        ->orderBy('peran_number')
                        ->offset(($request->page - 1)*$request->limit)
                        ->limit($request->limit)->get()
                , 'total' => Peran::where('tipe_peran', 'satuan_kerja_tahun')
                    ->where('parent_id', $this->satuan_kerja_tahun_id)
                    ->where(function($query_like) use ($search_term){
                            $query_like->where('pemilik_username', 'LIKE', '%'.$search_term.'%')
                                ->orWhere('pemilik_tim_id', 'LIKE', '%'.$search_term.'%')
                                ->orWhere('pemilik_satuan_kerja_tahun_id', 'LIKE', '%'.$search_term.'%');
                        })->count()];
        }

    }

    public function peran_format_unduh(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->satuan_kerja_tahun){
            abort(404, 'Id tidak ditemukan');
        }
        $file_awal= Storage::disk('local')->path('/data_awal/'.'peran_satuan_kerja.xls');
        $file_akhir= Storage::disk('local')->path('/data_process/'.((string) Uuid::generate()).'.xls');

        copy($file_awal, $file_akhir);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_awal);
        $sheet_user = $spreadsheet->getSheetByName("user_semua");

        $all_user = User::select('username', 'nama')->orderBy('nip_lama')->get();

        $index = 2;
        foreach($all_user as $item){
            $sheet_user->setCellValue("A".$index, $item->username);
            $sheet_user->setCellValue("B".$index, $item->nama);
            $index = $index + 1;
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save($file_akhir);


        $headers = ['Content-Type' => 'application/xls'];

        return Response::download($file_akhir, 'format_users.xls', $headers)->deleteFileAfterSend(true);
    }

    public function peran_format_unggah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $this->load_data_from_param($request);
            if(!$this->satuan_kerja_tahun){
                abort(404, 'Id tidak ditemukan');
            }
            $file_name = ((string)Uuid::generate()).'.xls';

            Storage::disk('local')->putFileAs(
                '/data_upload/',
                $request->file,
                $file_name
            );
            $inputFileName = Storage::disk('local')->path("/data_upload/".$file_name);

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($inputFileName);
            $reader->setLoadSheetsOnly('peran');
            $spreadsheet = $reader->load($inputFileName);

            // Log::info($spreadsheet->getActiveSheet()->toArray());
            // Log::info($this->satuan_kerja_tahun);

            $peran_daftar = Peran::where('tipe_peran', 'satuan_kerja_tahun')
                    ->where('parent_id', $this->satuan_kerja_tahun_id)
                    ->get();

            $peran_daftar_key = [];
            $pimpinan = '';
            foreach($peran_daftar as $item_peran){
                array_push($peran_daftar_key, $this->satuan_kerja_tahun_id.'_'.$item_peran->pemilik_username.'_'.$item_peran->peran);
                if($item_peran->peran == 'pimpinan'){
                    $pimpinan = $item_peran->pemilik_username;
                }
            }

            $pertama = true;
            foreach($spreadsheet->getActiveSheet()->toArray() as $item){
                if($pertama){
                    $pertama = false;
                }else{
                    if(in_array(trim($item[1]), $this->list_peran)
                        && !in_array($this->satuan_kerja_tahun_id.'_'.trim($item[0]).'_'.trim($item[1]), $peran_daftar_key)){

                            if(!($item[1] == 'pimpinan' && $pimpinan!= '')){
                                $baru =  new Peran;
                                $baru->id =  (string) Uuid::generate();
                                $baru->tipe_peran = 'satuan_kerja_tahun';
                                $baru->parent_id = $this->satuan_kerja_tahun_id;
                                $baru->pemilik_username = trim($item[0]);
                                $baru->pemilik_tim_id = null;
                                $baru->pemilik_satuan_kerja_tahun_id = null;
                                $baru->tahun = $this->satuan_kerja_tahun->tahun;
                                $baru->peran = trim($item[1]);
                                if(trim($item[1]) == 'pimpinan'){
                                    $baru->peran_number = 11;
                                }else  if(trim($item[1]) == 'admin'){
                                    $baru->peran_number = 14;
                                }else  if(trim($item[1]) == 'anggota'){
                                    $baru->peran_number = 15;
                                }
                                $baru->save();

                                Pengumuman::buat_untuk_peran_satuan_kerja_tahun(0, $this->satuan_kerja_tahun, $baru);
                            }
                    }
                }
            }
            unlink($inputFileName);
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function peran_hapus(Request $request, $username)
    {
        DB::beginTransaction();
        try
        {
            $this->load_data_from_param($request);
            if(!$this->peran){
                abort(404, 'Id tidak ditemukan');
            }
            if(!$this->satuan_kerja_tahun){
                abort(404, 'Id tidak ditemukan');
            }
            $count_not_eq_id = Peran::where('parent_id', $this->satuan_kerja_tahun_id)->where('id', '!=', $this->peran_id)->count();
            if($count_not_eq_id == 0){
                Jurnal_Harian_Proyek::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id)->delete();
                Tim::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id)->delete();
            }
            $this->peran->delete();
            Pengumuman::buat_untuk_peran_satuan_kerja_tahun(2, $this->satuan_kerja_tahun, $this->peran);
            DB::commit();
            return $this->peran;
        }catch(Exception $ex){
            DB::rollBack();
            return abort(500, $ex->getMessage());
        }
    }

}
