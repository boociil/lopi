<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Model\Jurnal_Harian\Jurnal_Harian_Proyek;
use App\Model\Umum\Peran;

class Repair_Jurnal_Harian extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proyek_deleted = Jurnal_Harian_Proyek::onlyTrashed()->get();
        foreach($proyek_deleted as $item_delete){
            Peran::where('parent_id', $item_delete->id)->delete();
        }
    }
}
