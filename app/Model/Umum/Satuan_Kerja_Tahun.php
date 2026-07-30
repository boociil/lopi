<?php

namespace App\Model\Umum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Webpatser\Uuid\Uuid;

class Satuan_Kerja_Tahun extends Model
{
    protected $table = 'siakip2_satuan_kerja_tahun';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'nama', 'tahun', 'satuan_kerja_id', 'kode_satuan_kerja', 'type', 'parent_id'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('created_at', 'asc');
        });
        static::saved(function (Satuan_Kerja_Tahun $user) {
            Satuan_Kerja_Tahun::set_data_all();
        });
        static::deleted(function (Satuan_Kerja_Tahun $user) {
            Satuan_Kerja_Tahun::set_data_all();
        });
    }

    public static function helper_getByUUID($uuid){
        return Satuan_Kerja_Tahun::where('id', $uuid);
    }

    public static function helper_tambah(Satuan_Kerja $satuan_Kerja, $data){
        $baru =  new Satuan_Kerja_Tahun;
        $baru->id =  (string) Uuid::generate();
        $baru->nama = $data->nama;
        $baru->tahun = $data->tahun;
        $baru->satuan_kerja_id = $satuan_Kerja->id;
        $baru->kode_satuan_kerja = $satuan_Kerja->kode;
        $baru->type = $data->type;
        $baru->parent_id = $data->parent_id;
        $baru->save();
        return $baru;
    }

    public function helper_ubah(Satuan_Kerja $satuan_Kerja, $data){
        $this->nama = $data->nama;
        $this->tahun = $data->tahun;
        $this->satuan_kerja_id = $satuan_Kerja->id;
        $this->kode_satuan_kerja = $satuan_Kerja->kode;
        $this->type = $data->type;
        $this->parent_id = $data->parent_id;
        $this->save();
    }

    public function helper_hapus(){
        $this->delete();
    }

    public function fungsi_daftar(): HasMany
    {
        return $this->hasMany(Fungsi::class, 'satuan_kerja_tahun_id', 'id');
    }


    public function fungsi_id_nama(): HasMany
    {
        return $this->hasMany(Fungsi::class, 'satuan_kerja_tahun_id', 'id')->select('id', 'satuan_kerja_id', 'nama');
    }

    public function tim_daftar(): HasMany
    {
        return $this->hasMany(Tim::class, 'satuan_kerja_tahun_id', 'id');
    }
    public function peran_daftar(): HasMany
    {
        return $this->hasMany(Peran::class, 'parent_id', 'id');
    }

    public function tim_daftar_id_nama(): HasMany
    {
        return $this->hasMany(Tim::class, 'satuan_kerja_tahun_id', 'id')->select('id', 'satuan_kerja_id', 'nama');
    }

    public static function set_data_all()
    {
        Cache::forget('arr_satuan_kerja_tahun');
        Cache::forget('dict_satuan_kerja_tahun');
        $arr_all_satuan_kerja_tahun = Satuan_Kerja_Tahun::all()->toArray();
        $dict_all_satuan_kerja_tahun = [];
        foreach($arr_all_satuan_kerja_tahun as $item_satuan_kerja_tahun){
            $dict_all_satuan_kerja_tahun[$item_satuan_kerja_tahun['id']] = $item_satuan_kerja_tahun;
        }

        Cache::rememberForever('arr_satuan_kerja_tahun', function () use ($arr_all_satuan_kerja_tahun){
            return $arr_all_satuan_kerja_tahun;
        });
        Cache::rememberForever('dict_satuan_kerja_tahun', function () use ($dict_all_satuan_kerja_tahun){
            return $dict_all_satuan_kerja_tahun;
        });
    }

    public static function get_data_all($type = 'arr')
    {
        if( !(Cache::has('arr_satuan_kerja_tahun') && Cache::has('dict_satuan_kerja_tahun')) )
        {
            Satuan_Kerja_Tahun::set_data_all();
        }

        if($type == 'arr'){
            return Cache::get('arr_user', []);
        }else if($type == 'dict'){
            return Cache::get('dict_user', []);
        }
        return [];
    }

    public static function find_data($ids, $type = 'arr')
    {
        if(!is_array($ids))
        {
             $ids = [$ids];
        }
        if( !(Cache::has('arr_satuan_kerja_tahun') && Cache::has('dict_satuan_kerja_tahun')) )
        {
            Satuan_Kerja_Tahun::set_data_all();
        }

        $return_data = [];
        $data_dict = Cache::get('dict_satuan_kerja_tahun', []);

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

    public static function getDataDict() {
        $data = self::where('tahun', session('tahun_active'))->get();
        $return_data = [];
        foreach($data as $item){
            $return_data[$item->id] = $item;
        }
        return $return_data;
    }

    public function peran()
    {
        return $this->hasMany(Peran::class,'parent_id','id');
    }

}
