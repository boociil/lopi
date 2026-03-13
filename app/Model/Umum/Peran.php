<?php

namespace App\Model\Umum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class Peran extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_peran';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;


    protected $fillable = ['id', 'tipe_peran', 'parent_id'
        , 'pemilik_username', 'pemilik_tim_id', 'pemilik_satuan_kerja_tahun_id', 'tahun'
        , 'peran', 'peran_number'];

    // protected static function boot()
    // {
    //     parent::boot();
    //     static::addGlobalScope('default_sort', function (Builder $builder) {
    //         $builder->orderBy('created_at', 'asc');
    //     });
    // }

    public static function helper_getByUUID($uuid){
        return Peran::where('id', $uuid);
    }

    public static function check_user_have_role($tipe_peran, $parent_id, $pemilik, $peran_arr)
    {
        $peran_ada = Peran::where(function($query_peran) use ($tipe_peran, $parent_id, $pemilik, $peran_arr) {
                $query_peran->where('tipe_peran', $tipe_peran)
                    ->where('parent_id', $parent_id)
                    ->where(function($query_pemilik) use ($pemilik) {
                        $query_pemilik->where('pemilik_username', $pemilik)
                            ->orWhere('pemilik_tim_id', $pemilik)
                            ->orWhere('pemilik_satuan_kerja_tahun_id', $pemilik);
                    })
                    ->where('tahun', session('tahun_active'));

                    if($peran_arr !== null){
                        $query_peran->whereIn('peran', $peran_arr);
                    }
            })->orWhere(function($query_peran) use ($tipe_peran, $parent_id, $pemilik, $peran_arr) {
                $query_peran->where(function($query_pemilik) use ($pemilik) {
                        $query_pemilik->where('pemilik_username', $pemilik)
                            ->orWhere('pemilik_tim_id', $pemilik)
                            ->orWhere('pemilik_satuan_kerja_tahun_id', $pemilik);
                    })
                    ->where('tipe_peran', 'global')
                    ->where('peran', 'admin')
                    ->where(function($query_tahun){
                        $query_tahun->where('tahun', session('tahun_active'))
                            ->orWhere('tahun', null);
                    });
            })
            ->count();
        if($peran_ada > 0){
            return true;
        }else{
            return false;
        }
    }

    public static function check_user_is_super_admin()
    {
        $username = Auth::user()->username;
        $peran_ada = Peran::where('pemilik_username', $username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'admin')
            ->where(function($query_tahun){
                $query_tahun->where('tahun', session('tahun_active'))
                    ->orWhere('tahun', null);
            })
            ->count();
        if($peran_ada > 0){
            return true;
        }else{
            return false;
        }
    }

    public static function set_super_admin(User $user)
    {
        $baru = new Peran;
        $baru->id =  (string) Uuid::generate();
        $baru->tipe_peran = 'global';
        $baru->parent_id = null;
        $baru->pemilik_username = $user->username;
        $baru->pemilik_tim_id = null;
        $baru->pemilik_satuan_kerja_tahun_id = null;
        $baru->tahun = null;
        $baru->peran = 'admin';
        $baru->save();

        return $baru;
    }

    public static function release_super_admin(User $user)
    {
        Peran::where('pemilik_username', $user->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'admin')
            ->delete();
    }

    public function pemilik_user(){
        return $this->hasOne(User::class, 'username', 'pemilik_username');
    }

    public function pemilik_user_nama(){
        return $this->hasOne(User::class, 'username', 'pemilik_username');
    }

    public static function get_my_peran ()
    {
        if(!Auth::check()){
            return [];
        }
        return Peran::where(function($query){
                    $query->where('tahun', session('tahun_active'))
                        ->orWhere('tahun', null);
                })->where('pemilik_username', Auth::user()->username)->get();
    }

    public static function get_my_peran_satuan_kerja_kerja_tahun()
    {
        if(!Auth::check()){
            return [];
        }
        return Peran::where('tipe_peran', 'satuan_kerja_tahun')
            ->where('tahun', session('tahun_active'))
            ->where('pemilik_username', Auth::user()->username)
            ->get();
    }

    public function parent_satuan_kerja_tahu()
    {
        return $this->hasOne(Satuan_Kerja_Tahun::class,'id','parent_id');
    }

     public function user()
    {
        return $this->hasOne(User::class,'username','pemilik_username');
    }

}
