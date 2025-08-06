<?php

namespace App\Model\Umum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Webpatser\Uuid\Uuid;

class Pengumuman extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_pengumuman';
    public $incrementing = false;
    protected $keytipe = 'string';
    public $timestamps = true;


    protected $fillable = ['id', 'tipe', 'pemilik', 'parent_id', 'content', 'tahun'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('updated_at', 'desc');
        });
    }

    protected $appends = ['updated_at_tulis'];

    public function getUpdatedAtTulisAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['updated_at'])
            ->format('d M Y, H:i');
    }

    public static function helper_getByUUID($uuid){
        return Pengumuman::where('id', $uuid);
    }

    public static function tutup_notif($uuid)
    {
        Pengumuman::helper_getByUUID($uuid)->delete();
    }

    public static function buat_untuk_peran_satuan_kerja_tahun($tipe, Satuan_Kerja_Tahun $satuan_Kerja_Tahun, Peran $peran){
        // tipe 0: create 1:update 2: delete
        if($tipe == 0){
            $baru = new Pengumuman;
            $baru->id = (string) Uuid::generate();
            $baru->pemilik = $peran->pemilik_username;
            $baru->parent_id = $satuan_Kerja_Tahun->id;
            $baru->tipe = 0;
            $baru->content = 'Kamu mendapat peran <strong>'.$peran->peran.'</strong> pada satuan kerja <strong>'.$satuan_Kerja_Tahun->nama.'</strong>';
            $baru->tahun = $satuan_Kerja_Tahun->tahun;
            $baru->save();
            return $baru;
        }else if($tipe == 2){
            $baru = new Pengumuman;
            $baru->id = (string) Uuid::generate();
            $baru->pemilik = $peran->pemilik_username;
            $baru->parent_id = $satuan_Kerja_Tahun->id;
            $baru->tipe = 2;
            $baru->content = 'Peran <strong>'.$peran->peran.'</strong> dihapus pada satuan kerja <strong>'.$satuan_Kerja_Tahun->nama.'</strong>';
            $baru->tahun = $satuan_Kerja_Tahun->tahun;
            $baru->save();
            return $baru;
        }
    }

    public static function buat_untuk_peran_fungsi($tipe, Fungsi $fungsi, Peran $peran){
        // tipe 0: create 1:update 2: delete
        if($tipe == 0){
            $baru = new Pengumuman;
            $baru->id = (string) Uuid::generate();
            $baru->pemilik = $peran->pemilik_username;
            $baru->parent_id = $fungsi->id;
            $baru->tipe = 0;
            $baru->content = 'Kamu mendapat peran <strong>'.$peran->peran.'</strong> pada fungsi <strong>'.$fungsi->nama.'</strong>';
            $baru->tahun = $fungsi->tahun;
            $baru->save();
            return $baru;
        }else if($tipe == 2){
            $baru = new Pengumuman;
            $baru->id = (string) Uuid::generate();
            $baru->pemilik = $peran->pemilik_username;
            $baru->parent_id = $fungsi->id;
            $baru->tipe = 2;
            $baru->content = 'Peran <strong>'.$peran->peran.'</strong> dihapus pada fungsi <strong>'.$fungsi->nama.'</strong>';
            $baru->tahun = $fungsi->tahun;
            $baru->save();
            return $baru;
        }
    }

    public static function buat_untuk_peran_tim($tipe, Tim $tim, Peran $peran){
        // tipe 0: create 1:update 2: delete
        if($tipe == 0){
            $baru = new Pengumuman;
            $baru->id = (string) Uuid::generate();
            $baru->pemilik = $peran->pemilik_username;
            $baru->parent_id = $tim->id;
            $baru->tipe = 0;
            $baru->content = 'Kamu mendapat peran <strong>'.$peran->peran.'</strong> pada tim <strong>'.$tim->nama.'</strong>';
            $baru->tahun = $tim->tahun;
            $baru->save();
            return $baru;
        }else if($tipe == 2){
            $baru = new Pengumuman;
            $baru->id = (string) Uuid::generate();
            $baru->pemilik = $peran->pemilik_username;
            $baru->parent_id = $tim->id;
            $baru->tipe = 2;
            $baru->content = 'Peran <strong>'.$peran->peran.'</strong> dihapus pada tim <strong>'.$tim->nama.'</strong>';
            $baru->tahun = $tim->tahun;
            $baru->save();
            return $baru;
        }
   }
}
