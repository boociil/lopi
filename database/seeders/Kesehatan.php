<?php

namespace Database\Seeders;

use App\Model\Lopi\Kesehatan as LopiKesehatan;
use DateTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;


class Kesehatan extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile(database_path('seeders\_kesehatan.xlsx'));
        $reader->setLoadSheetsOnly('Sheet3');
        $spreadsheet = $reader->load(database_path('seeders\_kesehatan.xlsx'));

        $all_user = [];

        foreach($spreadsheet->getActiveSheet()->toArray() as $item){
            $all_user[$item[1]] = $item[0];
        }

        // Log::info($all_user);

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile(database_path('seeders\_kesehatan.xlsx'));
        $reader->setLoadSheetsOnly('Sheet2');
        $spreadsheet = $reader->load(database_path('seeders\_kesehatan.xlsx'));

        foreach($spreadsheet->getActiveSheet()->toArray() as $item){
            $kesehatan = new LopiKesehatan();
            if(!array_key_exists($item[1], $all_user)){
                continue;
            }
            $kesehatan->username = $all_user[$item[1]];


            $datetime = DateTime::createFromFormat('m/d/Y', $item[5]);
            // Log::info( $datetime->format('Y-m-d'));

            $kesehatan->tanggal =  $datetime->format('Y-m-d') ;
            if($item[4] && $item[4] != '-' && is_numeric(str_replace(',','.',$item[4]))){
                $kesehatan->tinggi_badan = str_replace(',','.',$item[4]);
            }
            if($item[8] && $item[8] != '-' && is_numeric(str_replace(',','.',$item[8]))){
                $kesehatan->berat_badan = str_replace(',','.',$item[8]);
            }
            if($item[10] && $item[10] != '-' && is_numeric(str_replace(',','.',$item[10]))){
                $kesehatan->linkar_perut = str_replace(',','.',$item[10]);
            }
            if($item[11] && $item[11] != '-' ){
                $kesehatan->tekanan_darah = $item[11];
            }
            if($item[14] && $item[14] != '-' && is_numeric(str_replace(',','.',$item[14]))){
                $kesehatan->asam_urat = str_replace(',','.',$item[14]);
            }
            if($item[15] && $item[16] && $item[15] != '-' && $item[16] != 'Tidak diukur' && is_numeric(str_replace(',','.',$item[15]))){
                $kesehatan->gula_darah = str_replace(',','.',$item[15]) ;
                if('Gula Darah Sewaktu (GDS)'){
                    $kesehatan->gula_darah_tipe = 'gds';
                }else if('Gula Darah Puasa (GDP)'){
                    $kesehatan->gula_darah_tipe = 'gdp';
                }
            }
            if($item[17] && $item[17] != '-' && is_numeric(str_replace(',','.',$item[17]))){
                $kesehatan->kolestrol = str_replace(',','.',$item[17]);
            }
            if(!$kesehatan->username){
                continue;
            }
            $kesehatan->save();
        }

    }
}
