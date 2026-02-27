<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Umum\Peran;
use App\Model\Umum\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Exception;

use Inertia\Inertia;

class User_Controller extends Controller
{
    public function daftar(Request $request)
    {
        $page = 1;
        $limit = 25;
        if($request->page ){
            $page = $request->page;
        }
        if($request->limit){
            $limit = $request->limit;
        }
        $search_term = "";
        if($request->search_term){
            $search_term = $request->search_term;
        }

        if($request->isMethod('get')){
            return Inertia::render('admin/pengguna', [
                'daftar_' =>  User::with("photo_url")
                    ->where('nama', 'LIKE', '%'.$search_term.'%')
                    ->offset(($page - 1)*$limit)
                    ->limit($limit)
                    ->orderBy('nama', 'asc')
                    ->get(),
                'total_' => User::where('nama', 'LIKE', '%'.$search_term.'%')->count()
            ]);
        }else if($request->isMethod('post')){
            return [
                'daftar_' =>  User::with("photo_url")
                    ->where('nama', 'LIKE', '%'.$search_term.'%')
                    ->offset(($page - 1)*$limit)
                    ->limit($limit)
                    ->orderBy('nama', 'asc')
                    ->get(),
                'total_' => User::where('nama', 'LIKE', '%'.$search_term.'%')->count()
            ];
        }
    }

    public function format_unduh(Request $request)
    {
        $file= Storage::disk('local')->path('/format/'.'format_users.xls');
        // $file= public_path().'/format/'.'format_users.xls';

        $headers = ['Content-Type' => 'application/xls'];

        return Response::download($file, 'format_users.xls', $headers);
    }

    public function format_unggah(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $file_name = ((string)Str::uuid()).'.xls';

            Storage::disk('local')->putFileAs(
                '/data_upload/',
                $request->file,
                $file_name
            );
            $inputFileType = 'Xls';
            $inputFileName = Storage::disk('local')->path("/data_upload/".$file_name);

            /**  Create a new Reader of the type defined in $inputFileType  **/
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            /**  Advise the Reader of which WorkSheets we want to load  **/
            $reader->setLoadSheetsOnly('user');
            /**  Load $inputFileName to a Spreadsheet Object  **/
            $spreadsheet = $reader->load($inputFileName);

            // Log::info($spreadsheet->getActiveSheet()->toArray());

            $username_in_db = collect(User::select('username')->get())->pluck('username')->toArray();

            $pertama = true;
            foreach($spreadsheet->getActiveSheet()->toArray() as $item){
                if($pertama){
                    $pertama = false;
                }else{
                    if(!in_array(trim($item[1]) , $username_in_db)){
                        $baru =  new User;
                        $baru->username = trim($item[1]);
                        $baru->email = trim($item[2]);
                        if($item[3] != ''){
                            $baru->nip_baru = trim($item[3]);
                        }
                        if($item[4] != ''){
                            $baru->nip_lama = trim($item[4]);
                            $baru->password = Hash::make(trim($item[4]));
                        }else{
                            throw new Exception('Nip Lama Harus ada isi');
                        }

                        $baru->nama = trim($item[5]);
                        $baru->photo_url_id = null;
                        $baru->active = 1;
                        $baru->save();
                    }
                }
            }
            Storage::disk('local')->delete("/data_upload/".$file_name);
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
            $username = $request->route('username');
            $user = null;
            if($username){
                $user = User::helper_getBy_Username($username)->first();
            }

            if(!$user){
                abort(404, 'Id tidak ditemukan');
            }
            $user->helper_hapus($request);
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }

    public function set_super_admin(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $user = User::where('username', $request->route('username'))->first();
            if(!$user){
                abort(404, 'Id tidak ditemukan');
            }

            Peran::set_super_admin($user);
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }

    public function release_super_admin(Request $request, $username)
    {
        DB::beginTransaction();
        try
        {
            $user = User::where('username', $request->route('username'))->first();
            if(!$user){
                abort(404, 'Id tidak ditemukan');
            }

            Peran::release_super_admin($user);
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500, $ex->getMessage());
        }
    }
}
