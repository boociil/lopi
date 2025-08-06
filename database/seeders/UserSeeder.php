<?php

namespace Database\Seeders;

use App\Model\Umum\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $inputFileType = 'Xls';
        $inputFileName = 'storage/data_awal/awal_users.xls';
        $sheetname = 'awal_users';

        /**  Create a new Reader of the type defined in $inputFileType  **/
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        /**  Advise the Reader of which WorkSheets we want to load  **/
        $reader->setLoadSheetsOnly($sheetname);
        /**  Load $inputFileName to a Spreadsheet Object  **/
        $spreadsheet = $reader->load($inputFileName);

        // Log::info($spreadsheet->getActiveSheet()->toArray());

        $pertama = true;
        foreach($spreadsheet->getActiveSheet()->toArray() as $item){
            if($pertama){
                $pertama = false;
            }else{
                $baru =  new User;
                $baru->username = trim($item[1]);
                $baru->email = trim($item[2]);
                $baru->nip_baru = trim($item[3]);
                $baru->nip_lama = trim($item[4]);
                $baru->nama = trim($item[5]);
                $baru->password = Hash::make(trim($item[4]));

                $baru->photo_url_id = null;
                $baru->active = 1;
                $baru->save();
            }
        }
    }
}
