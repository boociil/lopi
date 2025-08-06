<?php

namespace App\Model\Umum;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class User_Prop extends Model
{

    protected $table = 'siakip2_user_prop';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'username', 'prop_name', 'nilai', 'awal_berlaku', 'akhir_berlaku', 'active'];


    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('awal_berlaku', 'desc');
        });
    }

    public static function helper_getByUUID($uuid){
        return User_Prop::where('id', $uuid);
    }


    public static function helper_user(User $user, $data){
        foreach($data as $item_prop) {
            if($item_prop['status'] == 'baru'){
                $baru =  new User_Prop;
                $baru->id = (string) Uuid::generate();
                $baru->username = $user->username;
                $baru->prop_name = $item_prop['prop_name'];
                $baru->nilai = $item_prop['nilai'];
                $baru->awal_berlaku = $item_prop['awal_berlaku'];
                $baru->akhir_berlaku = $item_prop['akhir_berlaku'];
                $baru->active = $item_prop['active'];
                $baru->save();
            }
            if($item_prop['status'] == 'ubah'){
                User_Prop::helper_getByUUID($item_prop['id'])
                    ->update(['nilai' => $item_prop['nilai'],
                        'awal_berlaku' => $item_prop['awal_berlaku'],
                        'akhir_berlaku' => $item_prop['akhir_berlaku'],
                        'active' => $item_prop['active']]);
            }
            if($item_prop['status'] == 'hapus'){
                User_Prop::helper_getByUUID($item_prop['id'])->delete();
            }
        }
    }
}
