<?php

namespace App\Model\Monitoring_Kegiatan;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Webpatser\Uuid\Uuid;

class Monitoring_Kegiatan_Pelaksana extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_monitoring_kegiatan_pelaksana';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'monitoring_master_id', 'bulan', 'tahun', 'monitoring_kegiatan_id'
        , 'pelaksana_satuan_kerja_tahun_id', 'pelaksana_tim_id', 'status'
        , 'tanggal_awal', 'tanggal_akhir', 'tanggal_realisasi', 'target', 'realisasi'
        , 'nilai_kuantitas', 'nilai_kualitas', 'nilai_waktu', 'nilai', 'bukti_url'];

    protected $withCount = ['berkas'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('bulan', 'asc');
            $builder->orderBy('created_at', 'asc');
        });
    }

    public static function helper_tambah(Monitoring_Kegiatan $monitoring_Kegiatan, $data)
    {
        $baru =  new Monitoring_Kegiatan_Pelaksana;
        $baru->id =  (string) Uuid::generate();
        $baru->monitoring_master_id = $monitoring_Kegiatan->monitoring_master_id;
        $baru->bulan = $monitoring_Kegiatan->bulan;
        $baru->tahun = $monitoring_Kegiatan->tahun;
        $baru->monitoring_kegiatan_id = $monitoring_Kegiatan->id;

        $baru->pelaksana_satuan_kerja_tahun_id = $data->pelaksana_satuan_kerja_tahun_id;
        $baru->pelaksana_tim_id = $data->pelaksana_tim_id;

        $baru->status = $data->status;
        $baru->tanggal_awal = $data->tanggal_awal;
        $baru->tanggal_akhir = $data->tanggal_akhir;

        if($baru->tanggal_awal && $baru->tanggal_akhir){
            if(\Carbon\Carbon::parse($baru->tanggal_awal) > \Carbon\Carbon::parse($baru->tanggal_akhir)){
                throw new Exception("Tanggal awal tidak boleh lebih dari tanggal akhir");
            }
        }
        $baru->tanggal_realisasi = null;
        $baru->target = $data->target;
        $baru->realisasi = $data->realisasi;

        // $baru->nilai_kuantitas = $data->nilai_kuantitas;
        $baru->nilai_kualitas = $data->nilai_kualitas;
        // $baru->nilai_waktu = $data->nilai_waktu;
        $baru->nilai = $data->nilai;
        $baru->bukti_url = $data->bukti_url;
        $baru->save();
        $baru->reload_nilai();
        return $baru;
    }

    public static function helper_tambah_ubah_hapus(Monitoring_Kegiatan $monitoring_Kegiatan, $data_arr, $status_baru_ubah)
    {
        if($status_baru_ubah == 'baru'){
            foreach($data_arr as $item_data){
                $baru =  new Monitoring_Kegiatan_Pelaksana;
                $baru->id =  (string) Uuid::generate();
                $baru->monitoring_master_id = $monitoring_Kegiatan->monitoring_master_id;
                $baru->bulan = $monitoring_Kegiatan->bulan;
                $baru->tahun = $monitoring_Kegiatan->tahun;
                $baru->monitoring_kegiatan_id = $monitoring_Kegiatan->id;

                $baru->pelaksana_satuan_kerja_tahun_id = $item_data['pelaksana_satuan_kerja_tahun_id'];
                $baru->pelaksana_tim_id = $item_data['pelaksana_tim_id'];

                $baru->status =  $item_data['status'];
                $baru->tanggal_awal =  $item_data['tanggal_awal'];
                $baru->tanggal_akhir =  $item_data['tanggal_akhir'];

                if($baru->tanggal_awal && $baru->tanggal_akhir){
                    if(\Carbon\Carbon::parse($baru->tanggal_awal) > \Carbon\Carbon::parse($baru->tanggal_akhir)){
                        throw new Exception("Tanggal awal tidak boleh lebih dari tanggal akhir");
                    }
                }
                $baru->tanggal_realisasi =  $item_data['tanggal_realisasi'];
                $baru->target =  $item_data['target'];
                $baru->realisasi =  $item_data['realisasi'];

                $baru->nilai_kuantitas = null;
                $baru->nilai_kualitas =  $item_data['nilai_kualitas'];
                $baru->nilai_waktu =  null;
                $baru->nilai = null;
                $baru->bukti_url = null;
                $baru->save();
                $baru->reload_nilai();
            }
        }else if($status_baru_ubah == 'ubah'){
            foreach($data_arr as $item_data){
                $baru = Monitoring_Kegiatan_Pelaksana::where('id', $item_data['id'])
                        ->where('tahun', session('tahun_active'))
                        ->first();

                if($baru->status != "selesai" && $item_data['status'] == "selesai"){
                    Monitoring_Kegiatan_Pelaksana_Berkas::where('type_berkas', 'pengajuan')
                        ->where('monitoring_kegiatan_pelaksana_id', $baru->id)
                        ->update(['status_approval' => 3]);
                }

                $baru->status =  $item_data['status'];
                $baru->tanggal_awal =  $item_data['tanggal_awal'];
                $baru->tanggal_akhir =  $item_data['tanggal_akhir'];

                if($baru->tanggal_awal && $baru->tanggal_akhir){
                    if(\Carbon\Carbon::parse($baru->tanggal_awal) > \Carbon\Carbon::parse($baru->tanggal_akhir)){
                        throw new Exception("Tanggal awal tidak boleh lebih dari tanggal akhir");
                    }
                }
                $baru->tanggal_realisasi =  $item_data['tanggal_realisasi'];
                $baru->target =  $item_data['target'];
                $baru->realisasi =  $item_data['realisasi'];

                $baru->nilai_kuantitas =null;
                $baru->nilai_kualitas =  $item_data['nilai_kualitas'];
                $baru->nilai_waktu =  null;
                $baru->nilai =  null;
                $baru->save();
                $baru->reload_nilai();
            }
        }
    }

    public function penyelesaian($realisasi, $tanggal_realisasi, $kualitas){
        $this->realisasi = $realisasi;
        $this->tanggal_realisasi = $tanggal_realisasi;
        $this->nilai_kualitas = $kualitas;
        $this->reload_nilai();
    }

    public function reload_nilai($monitoring_kegiatan = null){
        if(!$monitoring_kegiatan){
            $monitoring_kegiatan = Monitoring_Kegiatan::where('id', $this->monitoring_kegiatan_id)->first();
        }
        // Log::info($this->target);
        if($this->target == 0 || $this->target == null || $this->target == ''){
            $this->nilai_kuantitas = 0;
        }else{
            if($monitoring_kegiatan->pengaturan['kondisi_capaian'] == 'normal'){
                if($this->realisasi >= $this->target){
                    $this->nilai_kuantitas = round(100, 4);
                }else{
                    $this->nilai_kuantitas = round($this->realisasi/$this->target * 100, 4);
                }
            }else if($monitoring_kegiatan->pengaturan['kondisi_capaian'] == 'khusus'){
                if($this->realisasi <= $this->target){
                    $this->nilai_kuantitas = round(100, 4);
                }else{
                    $this->nilai_kuantitas = round((1+(1-($this->realisasi/$this->target))) * 100, 4);
                }
            }
        }

        if($this->nilai_kuantitas < 0){
            $this->nilai_kuantitas = 0;
        }

        if($this->status == 'belum' || $this->status == 'pengajuan' || $this->status == 'tolak'){
            $this->nilai_kualitas = round(0, 4);
            $this->tanggal_realisasi = null;
            $this->nilai_waktu = round(0, 4);

            $this->nilai = round( (($this->nilai_kuantitas + $this->nilai_kualitas + $this->nilai_waktu) / 3), 4);
            $this->save();
            return;
        }

        if($this->nilai_kualitas >= 100){
            $this->nilai_kualitas = round(100, 4);
        }

        if($this->nilai_kualitas > 100){
            $this->nilai_kualitas = round(100, 4);
        }else{
            $this->nilai_kualitas = $this->nilai_kualitas;
        }

        $this->nilai_waktu = round(100, 4);
        if($this->tanggal_akhir && $this->tanggal_realisasi){
            $last_day = $this->tanggal_akhir;
            if(!$last_day){
                $last_day = date_create(date($this->tahun."-".$this->bulan."-t"));
            }
            $start_day = $this->tanggal_awal;
            if(!$start_day){
                $start_day = date_create(date($this->tahun."-".$this->bulan."-1"));
            }

            if($this->tanggal_realisasi <= $this->tanggal_akhir){
                $this->nilai_waktu = round(100, 4);
            }else{
                $waktu_exp = date_diff(date_create($start_day), date_create($last_day))->days;
                $waktu_real = date_diff(date_create($start_day), date_create($this->tanggal_realisasi))->days;
                if($waktu_real <= $waktu_exp){
                    $this->nilai_waktu = 100;
                }else{
                    $this->nilai_waktu = 100 - ($waktu_real - $waktu_exp);
                }
                // if($waktu_real <= $waktu_exp){
                //     $this->nilai_waktu = round(100, 4);
                // }else{
                //     $this->nilai_waktu = round((1+(1-($waktu_real/$waktu_exp))) * 100, 4);
                // }
            }
        }
        $this->nilai = round( (($this->nilai_kuantitas + $this->nilai_kualitas + $this->nilai_waktu) / 3), 4);
        $this->save();
    }

    public function helper_hapus()
    {
        Monitoring_Kegiatan_Pelaksana_Berkas::where('monitoring_kegiatan_id', $this->id)->delete();
        $this->delete();
    }

    public function master()
    {
        return $this->hasOne(Monitoring_Master::class, 'id', 'monitoring_master_id');
    }

    public function kegiatan()
    {
        return $this->hasOne(Monitoring_Kegiatan::class, 'id', 'monitoring_kegiatan_id');
    }

    public function berkas()
    {
        return $this->hasMany(Monitoring_Kegiatan_Pelaksana_Berkas::class, 'monitoring_kegiatan_pelaksana_id', 'id');
    }

}
