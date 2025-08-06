<?php

namespace App\Model\Umum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Webpatser\Uuid\Uuid;

class Satuan_Kerja extends Model
{
    protected $table = 'siakip2_satuan_kerja';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'kode', 'nama', 'active'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('kode', 'asc');
            $builder->orderBy('created_at', 'asc');
        });
        static::saved(function (Satuan_Kerja $user) {
            Satuan_Kerja::set_data_all();
        });
        static::deleted(function (Satuan_Kerja $user) {
            Satuan_Kerja::set_data_all();
        });
    }

    public static function helper_getByUUID($uuid){
        return Satuan_Kerja::where('id', $uuid);
    }


    public static function helper_tambah($data){
        $baru =  new Satuan_Kerja;
        $baru->id =  (string) Uuid::generate();
        $baru->kode = $data->kode;
        $baru->nama = $data->nama;
        $baru->active = 1;
        $baru->save();
        return $baru;
    }

    public function helper_ubah($data){
        $this->kode = $data->kode;
        $this->nama = $data->nama;
        $this->active = $data->active;
        $this->save();
    }

    // public function helper_hapus(){
    //     $this->delete();
    // }

    public function helper_active(){
        $this->active = 1;
        $this->save();
    }

    public function helper_deactive(){
        $this->active = 0;
        $this->save();
    }

    public static function set_data_all()
    {
        Cache::forget('arr_satuan_kerja');
        Cache::forget('dict_satuan_kerja');
        $arr_all_satuan_kerja = Satuan_Kerja::all()->toArray();
        $dict_all_satuan_kerja = [];
        foreach($arr_all_satuan_kerja as $item_satuan_kerja){
            $dict_all_satuan_kerja[$item_satuan_kerja['id']] = $item_satuan_kerja;
        }

        Cache::rememberForever('arr_satuan_kerja', function () use ($arr_all_satuan_kerja){
            return $arr_all_satuan_kerja;
        });
        Cache::rememberForever('dict_satuan_kerja', function () use ($dict_all_satuan_kerja){
            return $dict_all_satuan_kerja;
        });
    }

    public static function get_data_all($type = 'arr')
    {
        if( !(Cache::has('arr_satuan_kerja') && Cache::has('dict_satuan_kerja')) )
        {
            Satuan_Kerja::set_data_all();
        }

        if($type == 'arr'){
            return Cache::get('arr_satuan_kerja', []);
        }else if($type == 'dict'){
            return Cache::get('dict_satuan_kerja', []);
        }
        return [];
    }

    public static function find_data($ids, $type = 'arr')
    {
        if(!is_array($ids))
        {
             $ids = [$ids];
        }
        if( !(Cache::has('arr_satuan_kerja') && Cache::has('dict_satuan_kerja')) )
        {
            Satuan_Kerja::set_data_all();
        }

        $return_data = [];
        $data_dict = Cache::get('dict_satuan_kerja', []);

        foreach($ids as $id)
        {
            if(array_key_exists($id, $data_dict))
            {
                if($type == 'arr')
                {
                    array_push($return_data, $data_dict[$id]);
                }else if($type == 'dict')
                {
                    $return_data[$id] = $data_dict[$id];
                }
            }
        }
        return $return_data;
    }
}
