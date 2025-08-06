<?php

namespace Database\Seeders;

use App\Model\Umum\Fungsi;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use Illuminate\Database\Seeder;
use Webpatser\Uuid\Uuid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateYear extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public $satuan_kerja_tahun = [];
    public $tim_tahun = [];
    public $peran = [];

    public $satuan_kerja_tahun_id = [];
    public $tim_tahun_id = [];
    public $peran_id = [];

    public $from_tahun = 2024;
    public $to_tahun = 2025;

    public function run()
    {
        $this->copy_satuan_kerja_tahun();
        $this->copy_tim_tahun();
        $this->copy_peran();

        foreach($this->satuan_kerja_tahun as $item){
            $item->save();
        }
        foreach($this->tim_tahun as $item){
            $item->save();
        }
        foreach($this->peran as $item){
            $item->save();
        }
    }

    public function copy_satuan_kerja_tahun(){
        $satuan_kerja_tahun_to_copy = Satuan_Kerja_Tahun::where('tahun', $this->from_tahun)->get();
        foreach($satuan_kerja_tahun_to_copy as $item_to_copy){
            $satuan_kerja_tahun_copy = $item_to_copy->replicate();
            $satuan_kerja_tahun_copy->id = (string) Uuid::generate();
            $satuan_kerja_tahun_copy->created_at = Carbon::now();
            $satuan_kerja_tahun_copy->updated_at = Carbon::now();
            $satuan_kerja_tahun_copy->tahun = $this->to_tahun;
            $satuan_kerja_tahun_copy->nama = str_replace($this->from_tahun, $this->to_tahun, $satuan_kerja_tahun_copy->nama);

            array_push($this->satuan_kerja_tahun, $satuan_kerja_tahun_copy);
            $this->satuan_kerja_tahun_id[$item_to_copy->id] = $satuan_kerja_tahun_copy->id;
        }

        foreach($this->satuan_kerja_tahun as &$item){
            if($item->parent_id){
                $item->parent_id = $this->satuan_kerja_tahun_id[$item->parent_id];
            }
        }
    }

    public function copy_tim_tahun(){
        // $tim_to_copy = Tim::where('tahun', $this->from_tahun)
        //                 ->where(function($query){
        //                     $query->where('nama', 'LIKE', '%Fungsi%');
        //                     $query->orWhere('nama', 'LIKE', '%Bidang%');
        //                     $query->orWhere('nama', 'LIKE', '%Bagian%');
        //                 })->get();
        $tim_to_copy = Tim::where('tahun', $this->from_tahun)->get();

        foreach($tim_to_copy as $item_to_copy){
            $tim_copy = $item_to_copy->replicate();
            $tim_copy->id = (string) Uuid::generate();
            $tim_copy->created_at = Carbon::now();
            $tim_copy->updated_at = Carbon::now();
            $tim_copy->tahun = $this->to_tahun;
            $tim_copy->nama = str_replace($this->from_tahun, $this->to_tahun, $tim_copy->nama);

            if($tim_copy->satuan_kerja_tahun_id){
                $tim_copy->satuan_kerja_tahun_id = $this->satuan_kerja_tahun_id[$tim_copy->satuan_kerja_tahun_id];
            }else{
                continue;
            }

            array_push($this->tim_tahun, $tim_copy);
            $this->tim_tahun_id[$item_to_copy->id] = $tim_copy->id;
        }
    }
    public function copy_peran(){
        $peran_to_copy = Peran::where('tahun', $this->from_tahun)->get();
        foreach($peran_to_copy as $item_to_copy){
            $peran_copy = $item_to_copy->replicate();
            $peran_copy->id = (string) Uuid::generate();
            $peran_copy->created_at = Carbon::now();
            $peran_copy->updated_at = Carbon::now();
            $peran_copy->tahun = $this->to_tahun;

            if($peran_copy->parent_id){
                $ada = false;
                if(array_key_exists($peran_copy->parent_id, $this->tim_tahun_id)){
                    $peran_copy->parent_id = $this->tim_tahun_id[$peran_copy->parent_id];
                    $ada = true;
                }
                if(array_key_exists($peran_copy->parent_id, $this->satuan_kerja_tahun_id)){
                    $peran_copy->parent_id = $this->satuan_kerja_tahun_id[$peran_copy->parent_id];
                    $ada = true;
                }
                if(!$ada){
                    continue;
                }
            }

            if($peran_copy->pemilik_tim_id){
                if(array_key_exists($peran_copy->pemilik_tim_id, $this->tim_tahun_id)){
                    $peran_copy->pemilik_tim_id = $this->tim_tahun_id[$peran_copy->pemilik_tim_id];
                }else{
                    continue;
                }
            }

            if($peran_copy->pemilik_satuan_kerja_tahun_id){
                if(array_key_exists($peran_copy->pemilik_satuan_kerja_tahun_id, $this->satuan_kerja_tahun_id)){
                    $peran_copy->pemilik_satuan_kerja_tahun_id = $this->satuan_kerja_tahun_id[$peran_copy->pemilik_satuan_kerja_tahun_id];
                }else{
                    continue;
                }
            }

            array_push($this->peran, $peran_copy);
        }
    }
}
