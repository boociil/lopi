<?php

namespace App\Model\Monitoring_Kegiatan;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webpatser\Uuid\Uuid;

class Monitoring_Master extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_monitoring_master';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'bulan', 'tahun', 'target_tanggal_awal', 'target_tanggal_akhir'
        , 'realisasi_tanggal_awal', 'realisasi_tanggal_akhir'];


    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('bulan', 'asc');
            $builder->orderBy('tahun', 'desc');
            $builder->orderBy('created_at', 'asc');
        });
    }

    public static function helper_tambah($data)
    {
        $bulan_tahun_master_count = Monitoring_Master::where('bulan', $data->bulan)
                ->where('tahun', session('tahun_active'))
                ->count();

        if($bulan_tahun_master_count > 0){
            throw new Exception("Master sudah ada tidak sesuai");
        }

        $baru =  new Monitoring_Master;
        $baru->id =  (string) Uuid::generate();
        $baru->bulan = $data->bulan;
        $baru->tahun = session('tahun_active');;

        $baru->target_tanggal_awal = $data->target_tanggal_awal;
        $baru->target_tanggal_akhir = $data->target_tanggal_akhir;
        $baru->realisasi_tanggal_awal = $data->realisasi_tanggal_awal;
        $baru->realisasi_tanggal_akhir = $data->realisasi_tanggal_akhir;
        $baru->save();
        return $baru;
    }

    public function helper_ubah($data)
    {
        $this->target_tanggal_awal = $data->target_tanggal_awal;
        $this->target_tanggal_akhir = $data->target_tanggal_akhir;
        $this->realisasi_tanggal_awal = $data->realisasi_tanggal_awal;
        $this->realisasi_tanggal_akhir = $data->realisasi_tanggal_akhir;
        $this->save();
    }

    public function helper_hapus()
    {
        Monitoring_Kegiatan_Pelaksana_Berkas::where('monitoring_master_id', $this->id)->delete();
        Monitoring_Kegiatan_Pelaksana::where('monitoring_master_id', $this->id)->delete();
        Monitoring_Kegiatan::where('monitoring_master_id', $this->id)->delete();
        $this->delete();
    }

}
