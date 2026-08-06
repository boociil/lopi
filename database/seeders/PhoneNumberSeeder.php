<?php

namespace Database\Seeders;

use App\Model\Lopi\User_Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PhoneNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile(database_path('seeders\phone.xlsx'));
        $reader->setLoadSheetsOnly('sheet1');
        $spreadsheet = $reader->load(database_path('seeders\phone.xlsx'));

        $head = true;
        foreach($spreadsheet->getActiveSheet()->toArray() as $item){
            $no_hp = str_replace('"', '', str_replace('+', '', $item[1]));
            $username = $item[3];


            if($head){
                 Log::alert($username);
                $head = false;
                continue;
            }

            $profile_no_wa = User_Setting::where('category', 'profile')
                ->where('key', 'no_wa')
                ->where('username', $username)
                ->first();

            if(!$profile_no_wa){
                $pro = new User_Setting();
                $pro->id = Str::uuid();
                $pro->category = 'profile';
                $pro->key = 'no_wa';
                $pro->username = $username;
                $pro->data = $no_hp;
                $pro->save();
            }
        }
    }
}
