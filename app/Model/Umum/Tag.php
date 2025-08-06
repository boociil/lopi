<?php

namespace App\Model\Umum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class Tag extends Model
{
    protected $table = 'siakip2_tag';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'tag_id', 'nama', 'warna', 'pemilik_id', 'tahun'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('created_at', 'asc');
        });
    }

    public static function helper_getByUUID($uuid){
        return Tag::where('id', $uuid);
    }

    public static function helper_getByPemilik_Id($uuid){
        return Tag::where('pemilik_id', $uuid);
    }

    public static function helper_tambah($data)
    {
        $baru =  new Tag;
        $baru->id =  (string) Uuid::generate();
        if($data->tag_id){
            $baru->tag_id = $data->tag_id;
        }else{
            $baru->tag_id = (string) Uuid::generate();
        }
        $baru->nama = $data->nama;
        $baru->warna = $data->warna;
        $baru->pemilik_id = $data->pemilik_id;
        $baru->satuan_kerja_tahun_id = session('tahun_active');
        $baru->save();
        return $baru;
    }

    public function helper_hapus(){
        $this->delete();
    }

    public static function helper_ubah_tag_id($data, $tag_id)
    {
        Tag::where('tag_id', $tag_id)
            ->update(['nama'=>$data->nama, 'warna' => $data->warna]);
    }

    public static function helper_hapus_tag_id($tag_id)
    {
        Tag::where('tag_id', $tag_id)
            ->delete();
    }
}
