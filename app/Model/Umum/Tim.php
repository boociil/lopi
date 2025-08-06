<?php

namespace App\Model\Umum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Webpatser\Uuid\Uuid;

class Tim extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_tim';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'nama', 'sk_url_id', 'photo_url_id', 'satuan_kerja_tahun_id', 'fungsi_id', 'tahun'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('created_at', 'asc');
        });
        static::saved(function (Tim $user) {
            Tim::set_data_all();
        });
        static::deleted(function (Tim $user) {
            Tim::set_data_all();
        });
    }

    public static function helper_getByUUID($uuid)
    {
        return Tim::where('id', $uuid);
    }


    public static function helper_tambah($data, Satuan_Kerja_Tahun $satuan_Kerja, Fungsi $fungsi = null)
    {
        $baru =  new Tim;
        $baru->id =  (string) Uuid::generate();
        $baru->nama = $data->nama;
        // $baru->sk_url_id = $data->sk_url_id;
        // $baru->photo_url_id = $data->photo_url_id;
        $baru->satuan_kerja_tahun_id = $satuan_Kerja->id;
        if ($fungsi){
            $baru->fungsi_id = $fungsi->id;
        }
        $baru->tahun = $satuan_Kerja->tahun;
        $baru->save();
        return $baru;
    }

    public function helper_ubah($data, Satuan_Kerja_Tahun $satuan_Kerja, Fungsi $fungsi = null)
    {
        $this->nama = $data->nama;
        // $this->sk_url_id = $data->sk_url_id;
        // $this->photo_url_id = $data->photo_url_id;
        $this->satuan_kerja_tahun_id = $satuan_Kerja->id;
        if ($fungsi){
            $this->fungsi_id = $fungsi->id;
        }else{
            $this->fungsi_id = null;
        }
        $this->tahun = $satuan_Kerja->tahun;
        $this->save();
    }

    public function helper_hapus()
    {
        $this->delete();
    }

    public function satuan_kerja_tahun()
    {
        return $this->hasOne(Satuan_Kerja_Tahun::class,'id','satuan_kerja_tahun_id');
    }
    public function fungsi()
    {
        return $this->hasOne(Fungsi::class,'id','fungsi_id');
    }

    public static function set_data_all()
    {
        Cache::forget('arr_tim');
        Cache::forget('dict_tim');
        $arr_all_tim = Tim::all()->toArray();
        $dict_all_tim = [];
        foreach($arr_all_tim as $item_tim){
            $dict_all_tim[$item_tim['id']] = $item_tim;
        }

        Cache::rememberForever('arr_tim', function () use ($arr_all_tim){
            return $arr_all_tim;
        });
        Cache::rememberForever('dict_tim', function () use ($dict_all_tim){
            return $dict_all_tim;
        });
    }

    public static function get_data_all($type = 'arr')
    {
        if( !(Cache::has('arr_tim') && Cache::has('dict_tim')) )
        {
            Tim::set_data_all();
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
        if( !(Cache::has('arr_tim') && Cache::has('dict_tim')) )
        {
            Tim::set_data_all();
        }

        $return_data = [];
        $data_dict = Cache::get('dict_tim', []);

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
