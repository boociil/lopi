<?php

namespace App\Http\Controllers\Admin;

use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
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

class Tim_Controller extends Controller
{
    private $tim_id, $tim;
    private $peran_id, $peran;
    private $list_peran = ['admin', 'ketua', 'anggota'];


    public function load_data_from_param($request)
    {
        $this->tim_id = $request->route('tim_id');
        if($this->tim_id){
            $this->tim = Tim::helper_getByUUID($this->tim_id)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
        $this->peran_id = $request->route('peran_id');
        if($this->peran_id){
            $this->peran = Peran::helper_getByUUID($this->peran_id)
                ->where('parent_id', $this->tim_id)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
    }

    private function get_permission_global_satuan_kerja_tahun_tim()
    {
        $ada_global = false;
        $satuan_kerja_tahun_id_as_admin = [];
        $tim_id_as_admin = [];
        $satuan_kerja_tahun_id_as_bukan_admin = [];

        $peran_admin_global_atau_satuan_kerja_tahun_tim = Peran::where('pemilik_username', Auth::user()->username)
            ->where(function ($query) {
                $query->orWhere(function ($query_0){
                    $query_0->where('tipe_peran', 'global');
                    $query_0->where('peran', 'admin');
                    $query_0->where('tahun', null);
                });
                $query->orWhere(function ($query_0){
                    $query_0->where('tipe_peran', 'satuan_kerja_tahun');
                    $query_0->where('tahun', session('tahun_active'));
                });
                $query->orWhere(function ($query_0){
                    $query_0->where('tipe_peran', 'tim');
                    $query_0->where('peran', 'admin');
                    $query_0->where('tahun', session('tahun_active'));
                });
            })
            ->get();
        foreach($peran_admin_global_atau_satuan_kerja_tahun_tim as $item){
            if($item->tipe_peran == 'global' && $item->peran == 'admin' && is_null($item->tahun)){
                $ada_global = true;
            }
            if($item->tipe_peran == 'satuan_kerja_tahun' && $item->peran == 'admin' && $item->tahun == session('tahun_active')){
                array_push($satuan_kerja_tahun_id_as_admin, $item->parent_id);
            }
            if($item->tipe_peran == 'tim' && $item->peran == 'admin' && $item->tahun == session('tahun_active')){
                array_push($tim_id_as_admin, $item->parent_id);
            }
            if($item->tipe_peran == 'satuan_kerja_tahun' && $item->peran != 'admin' && $item->tahun == session('tahun_active')){
                array_push($satuan_kerja_tahun_id_as_bukan_admin, $item->parent_id);
            }
        }

        return [
            'ada_global' => $ada_global,
            'satuan_kerja_tahun_id' => $satuan_kerja_tahun_id_as_admin,
            'tim_id' => $tim_id_as_admin,
            'satuan_kerja_tahun_id_bukan_admin' => $satuan_kerja_tahun_id_as_bukan_admin
        ];
    }


    public function daftar(Request $request)
    {
        $return_func = $this->get_permission_global_satuan_kerja_tahun_tim();
        if($return_func['ada_global']){
            return [
                'satuan_kerja_tahun' => Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))->get(),
                'tim' => Tim::where('tahun', session('tahun_active'))->get(),
                'peran_func' => $return_func
            ];
        }else{
            $tim = Tim::where('tahun', session('tahun_active'))
                ->where(function ($query_1) use ($return_func) {
                    $query_1->orWhereIn('id', $return_func['tim_id']);
                    $query_1->orWhereIn('satuan_kerja_tahun_id', $return_func['satuan_kerja_tahun_id']);
                })
                ->get();
            $satuan_kerja_tahun_id_arr = [];
            foreach($tim as $item){
                array_push($satuan_kerja_tahun_id_arr, $item->satuan_kerja_tahun_id);
            }
            return [
                'tim' => $tim,
                'satuan_kerja_tahun' => Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                        ->where(function ($query) use ($return_func, $satuan_kerja_tahun_id_arr){
                            $query->orWhereIn('id', $satuan_kerja_tahun_id_arr);
                            $query->orWhereIn('id', $return_func['satuan_kerja_tahun_id']);
                        })
                        ->get(),
                'peran_func' => $return_func
            ];
        }

    }

    public function tambah(Request $request)
    {
        $this->load_data_from_param($request);
        DB::beginTransaction();
        try
        {
            $return_func = $this->get_permission_global_satuan_kerja_tahun_tim();
            if(!$return_func['ada_global']){
                if(!in_array($request->satuan_kerja_tahun_id, $return_func['satuan_kerja_tahun_id'])){
                    return abort(403);
                }
            }

            $satuan_kerja_tahun = Satuan_Kerja_Tahun::helper_getByUUID($request->satuan_kerja_tahun_id)->first();
            if(!$satuan_kerja_tahun){
                abort(404, 'Id tidak ditemukan');
            }
            $baru = Tim::helper_tambah($request, $satuan_kerja_tahun, null);
            DB::commit();
            return $baru;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function lihat(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->tim){
            abort(404, 'Id tidak ditemukan');
        }
        $return_func = $this->get_permission_global_satuan_kerja_tahun_tim();
        if($return_func['ada_global']){
            return [
                'satuan_kerja_tahun' => Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))->get(),
                'tim' => $this->tim,
                'peran_func' => $return_func
            ];
        }else{
            return [
                'satuan_kerja_tahun' => Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                        ->where(function($query) use ($return_func){
                            $query->orWhereIn('id', $return_func['satuan_kerja_tahun_id']);
                            $query->orWhere('id', $this->tim->satuan_kerja_tahun_id);
                        })
                        ->get(),
                'tim' => $this->tim,
                'peran_func' => $return_func
            ];
        }
    }

    public function ubah(Request $request)
    {
        $this->load_data_from_param($request);
        DB::beginTransaction();
        try
        {
            if(!$this->tim){
                abort(404, 'Id tidak ditemukan');
            }
            $return_func = $this->get_permission_global_satuan_kerja_tahun_tim();
            if(!$return_func['ada_global']){
                if(!in_array($request->satuan_kerja_tahun_id, $return_func['satuan_kerja_tahun_id'])){
                    return abort(403);
                }
            }
            $satuan_kerja_tahun = Satuan_Kerja_Tahun::helper_getByUUID($request->satuan_kerja_tahun_id)->first();
            if(!$satuan_kerja_tahun){
                abort(404, 'Id tidak ditemukan');
            }
            $this->tim->helper_ubah($request, $satuan_kerja_tahun, null);
            // Kegiatan_Internal_Kegiatan::where('pemberi_tim_id', $this->tim_id)
            //     ->update(['pemberi_fungsi_id' => $this->tim->fungsi_id]);
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function hapus(Request $request)
    {
        $this->load_data_from_param($request);
        DB::beginTransaction();
        try
        {
            if(!$this->tim){
                abort(404, 'Id tidak ditemukan');
            }
            $this->tim->delete();
            Peran::where('parent_id', $this->tim->id)->delete();
            Pengumuman::where('parent_id', $this->tim->id)->delete();
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function peran_daftar(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->tim){
            abort(404, 'Id tidak ditemukan');
        }
        if(!$request->limit || !$request->page ){
            return ['daftar' =>  Peran::with('pemilik_user_nama.photo_url')
                    ->where('tipe_peran', 'tim')
                    ->where('parent_id', $this->tim_id)
                    ->orderBy('peran_number')
                    ->get()
                , 'total' => Peran::where('tipe_peran', 'tim')
                    ->where('parent_id', $this->tim_id)->count()];
        }else{
            $search_term = $request->search_term;
            return ['daftar' =>  Peran::with('pemilik_user_nama.photo_url')
                        ->where('tipe_peran', 'tim')
                        ->where('parent_id', $this->tim_id)
                        ->where(function($query_like) use ($search_term){
                            $query_like->where('pemilik_username', 'LIKE', '%'.$search_term.'%')
                                ->orWhere('pemilik_tim_id', 'LIKE', '%'.$search_term.'%')
                                ->orWhere('pemilik_satuan_kerja_tahun_id', 'LIKE', '%'.$search_term.'%');
                        })
                        ->orderBy('peran_number')
                        ->offset(($request->page - 1)*$request->limit)
                        ->limit($request->limit)->get()
                , 'total' => Peran::where('tipe_peran', 'tim')
                    ->where('parent_id', $this->tim_id)
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
        if(!$this->tim){
            abort(404, 'Id tidak ditemukan');
        }
        $file_awal= Storage::disk('local')->path('/data_awal/'.'peran_satuan_kerja.xls');
        $file_akhir= Storage::disk('local')->path('/data_process/'.((string) Uuid::generate()).'.xls');

        copy($file_awal, $file_akhir);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_awal);
        $sheet_user = $spreadsheet->getSheetByName("user_semua");

        // $all_user = User::all(['username', 'nama']);
        $all_user = Peran::with('pemilik_user')->where('parent_id', $this->tim->satuan_kerja_tahun_id)->orderBy('peran_number')->get();

        $index = 2;
        foreach($all_user as $item){
            $sheet_user->setCellValue("A".$index, $item->pemilik_user->username);
            $sheet_user->setCellValue("B".$index, $item->pemilik_user->nama);
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
            if(!$this->tim){
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

            $peran_daftar = Peran::where('tipe_peran', 'tim')
                    ->where('parent_id', $this->tim_id)
                    ->get();

            $peran_daftar_key = [];
            $ketua = '';
            foreach($peran_daftar as $item_peran){
                array_push($peran_daftar_key, $this->tim_id.'_'.$item_peran->pemilik_username.'_'.$item_peran->peran);
                if($item_peran->peran == 'ketua'){
                    $ketua = $item_peran->pemilik_username;
                }
            }

            $all_peran_satuan_kerja_tahun = Peran::where('parent_id', $this->tim->satuan_kerja_tahun_id)->get();
            $user_satuan_kerja_tahun_array = [];
            foreach($all_peran_satuan_kerja_tahun as $item_peran){
                array_push($user_satuan_kerja_tahun_array, $item_peran->pemilik_username);
            }

            $pertama = true;
            foreach($spreadsheet->getActiveSheet()->toArray() as $item){
                if($pertama){
                    $pertama = false;
                }else{
                    if(in_array($item[1], $this->list_peran)
                        && !in_array($this->tim_id.'_'.trim($item[0]).'_'.trim($item[1]), $peran_daftar_key)
                        && in_array($item[0], $user_satuan_kerja_tahun_array)){

                            if(!($item[1] == 'ketua' && $ketua!= '')){
                                $baru =  new Peran;
                                $baru->id =  (string) Uuid::generate();
                                $baru->tipe_peran = 'tim';
                                $baru->parent_id = $this->tim_id;
                                $baru->pemilik_username = $item[0];
                                $baru->pemilik_tim_id = null;
                                $baru->pemilik_satuan_kerja_tahun_id = null;
                                $baru->tahun = $this->tim->tahun;
                                $baru->peran = $item[1];
                                if(trim($item[1]) == 'ketua'){
                                    $baru->peran_number = 31;
                                }else  if(trim($item[1]) == 'admin'){
                                    $baru->peran_number = 34;
                                }else  if(trim($item[1]) == 'anggota'){
                                    $baru->peran_number = 35;
                                }
                                $baru->save();

                                Pengumuman::buat_untuk_peran_tim(0, $this->tim, $baru);
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

    public function peran_hapus(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $this->load_data_from_param($request);
            if(!$this->tim){
                abort(404, 'Id tidak ditemukan');
            }
            if(!$this->peran){
                abort(404, 'Id tidak ditemukan');
            }
            $count_not_eq_id = Peran::where('parent_id', $this->tim_id)->where('id', '!=', $this->peran_id)->count();
            if($count_not_eq_id == 0){
                Jurnal_Harian_Proyek::where('tim_id', $this->tim_id)->delete();
            }
            $this->peran->delete();
            Pengumuman::buat_untuk_peran_tim(2, $this->tim, $this->peran);
            DB::commit();
            return $this->peran;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }
}

