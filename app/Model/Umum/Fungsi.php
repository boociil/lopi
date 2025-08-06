<?php

namespace App\Model\Umum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webpatser\Uuid\Uuid;

class Fungsi extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_fungsi';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'nama', 'sk_url_id', 'photo_url_id', 'satuan_kerja_tahun_id', 'tahun'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('created_at', 'asc');
        });
    }

    public static function helper_getByUUID($uuid)
    {
        return Fungsi::where('id', $uuid);
    }


    public static function helper_tambah($data, Satuan_Kerja_Tahun $satuan_Kerja_tahun)
    {
        $baru =  new Fungsi;
        $baru->id =  (string) Uuid::generate();
        $baru->nama = $data->nama;
        // $baru->sk_url_id = $data->sk_url_id;
        // $baru->photo_url_id = $data->photo_url_id;
        $baru->satuan_kerja_tahun_id = $satuan_Kerja_tahun->id;
        $baru->tahun = $satuan_Kerja_tahun->tahun;
        $baru->save();
        return $baru;
    }

    public function helper_ubah($data, Satuan_Kerja_Tahun $satuan_Kerja_tahun)
    {
        $this->nama = $data->nama;
        if($satuan_Kerja_tahun){
            $this->satuan_kerja_tahun_id = $satuan_Kerja_tahun->id;
            $this->tahun = $satuan_Kerja_tahun->tahun;
        }
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

    public function penanggung_jawab()
    {
        return $this->hasOne(Peran::class,'parent_id','id')->where('peran', 'pj');
    }

}
