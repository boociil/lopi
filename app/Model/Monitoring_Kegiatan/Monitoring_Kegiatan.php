<?php

namespace App\Model\Monitoring_Kegiatan;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webpatser\Uuid\Uuid;

class Monitoring_Kegiatan extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_monitoring_kegiatan';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'monitoring_master_id', 'bulan', 'tahun'
        , 'pengaturan', 'pemberi_satuan_kerja_tahun_id', 'pemberi_tim_id'
        , 'nama', 'satuan', 'tanggal_awal', 'tanggal_akhir'];

    protected $casts = [
        'pengaturan' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('bulan', 'asc');
            $builder->orderBy('tahun', 'asc');
            $builder->orderBy('created_at', 'asc');
        });
    }

    public static function helper_tambah(Monitoring_Master $monitoring_Master, $satuan_Kerja_Tahun_id, $tim_id, $data)
    {
        $baru =  new Monitoring_Kegiatan;
        $baru->id =  (string) Uuid::generate();
        $baru->monitoring_master_id = $monitoring_Master->id;
        $baru->bulan = $monitoring_Master->bulan;
        $baru->tahun = $monitoring_Master->tahun;

        $baru->pengaturan = $data->pengaturan;
        $baru->pemberi_satuan_kerja_tahun_id = $satuan_Kerja_Tahun_id;
        $baru->pemberi_tim_id = $tim_id;
        $baru->nama = $data->nama;
        $baru->satuan = $data->satuan;
        $baru->tanggal_awal = $data->tanggal_awal;
        $baru->tanggal_akhir = $data->tanggal_akhir;
        $baru->save();
        return $baru;
    }

    public function helper_ubah($satuan_Kerja_Tahun_id, $tim_id, $data)
    {
        $this->pemberi_satuan_kerja_tahun_id = $satuan_Kerja_Tahun_id;
        $this->pemberi_tim_id = $tim_id;
        $this->pengaturan = $data->pengaturan;
        $this->nama = $data->nama;
        $this->satuan = $data->satuan;
        $this->tanggal_awal = $data->tanggal_awal;
        $this->tanggal_akhir = $data->tanggal_akhir;
        $this->save();
    }

    public function helper_hapus()
    {
        Monitoring_Kegiatan_Pelaksana::where('monitoring_kegiatan_id', $this->id)->delete();
        Monitoring_Kegiatan_Pelaksana_Berkas::where('monitoring_kegiatan_id', $this->id)->delete();
        $this->delete();
    }

    public function pelaksana()
    {
        return $this->hasMany(Monitoring_Kegiatan_Pelaksana::class, 'monitoring_kegiatan_id', 'id');
    }

    public function pelaksana_target_kosong()
    {
        return $this->pelaksana()->where('target','>',0);
    }
}
