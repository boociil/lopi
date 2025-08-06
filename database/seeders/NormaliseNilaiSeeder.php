<?php

namespace Database\Seeders;

use App\Model\Jurnal_Harian\Jurnal_Harian_Item;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NormaliseNilaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        try
        {
            $all_item = Jurnal_Harian_Item::all();
            foreach($all_item as $item){
                $item->reload_nilai();
            }

            Jurnal_Harian_Item::where(function ($query){
                    $query->orWhereNull('nilai_kualitas');
                    $query->orWhere('nilai_kualitas', '');
                    $query->orWhere('nilai_kualitas', 0);
                })
                ->where('status_nilai', 3)
                ->update(['status_nilai' => 1]);
            DB::commit();
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
        }
    }
}
