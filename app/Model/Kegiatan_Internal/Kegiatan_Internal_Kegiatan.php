<?php

namespace App\Model\Kegiatan_Internal;

use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Fungsi;
use App\Model\Umum\Tim;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Webpatser\Uuid\Uuid;

class Kegiatan_Internal_Kegiatan extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_kegiatan_internal_kegiatan';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'kegiatan_internal_master_id', 'bulan', 'tahun'
        , 'pemberi_satuan_kerja_tahun_id', 'pemberi_fungsi_id', 'pemberi_tim_id', 'pemberi_username'
        , 'pelaksana_satuan_kerja_tahun_id', 'pelaksana_tim_id', 'pengaturan', 'nama', 'satuan', 'target', 'realisasi'
        , 'status', 'tanggal_awal', 'tanggal_akhir', 'tanggal_realisasi'
        , 'nilai_kuantitas', 'nilai_kualitas', 'nilai_waktu', 'nilai', 'bukti_url'];

    protected $withCount = ['berkas'];

    protected $casts = [
        'pengaturan' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('bulan', 'asc');
            $builder->orderBy('created_at', 'asc');
        });
    }

    public static function helper_tambah(Kegiatan_Internal_Master $monitoring_Kegiatan, $pemberi_type
        , Satuan_Kerja_Tahun $pemberi_satuan_kerja_tahun, ?Fungsi $pemberi_fungsi, ?Tim $pemberi_tim, Tim $penerima_tim, $data)
    {
        $baru =  new Kegiatan_Internal_Kegiatan;
        $baru->id =  (string) Uuid::generate();
        $baru->kegiatan_internal_master_id = $monitoring_Kegiatan->id;
        $baru->bulan = $monitoring_Kegiatan->bulan;
        $baru->tahun = $monitoring_Kegiatan->tahun;

        if($pemberi_type == 'satuan_kerja_tahun'){
            $baru->pemberi_satuan_kerja_tahun_id = $pemberi_satuan_kerja_tahun->id;
            $baru->pemberi_fungsi_id = null;
            $baru->pemberi_tim_id = null;
        }else if($pemberi_type == 'fungsi'){
            $baru->pemberi_satuan_kerja_tahun_id = $pemberi_fungsi->satuan_kerja_tahun_id;
            $baru->pemberi_fungsi_id = $pemberi_fungsi->id;
            $baru->pemberi_tim_id = null;
        }else if($pemberi_type == 'tim'){
            $baru->pemberi_satuan_kerja_tahun_id = $pemberi_tim->satuan_kerja_tahun_id;
            $baru->pemberi_fungsi_id = $pemberi_tim->fungsi_id;
            $baru->pemberi_tim_id = $pemberi_tim->id;
        }
        $baru->pemberi_username = Auth::user()->username;

        $baru->pelaksana_satuan_kerja_tahun_id = $penerima_tim->satuan_kerja_tahun_id;
        $baru->pelaksana_tim_id = $penerima_tim->id;
        $baru->pengaturan = $data->pengaturan;
        $baru->nama = $data->nama;
        $baru->satuan = $data->satuan;
        $baru->target = $data->target;
        $baru->realisasi = $data->realisasi;

        $baru->status = $data->status;
        $baru->tanggal_awal = $data->tanggal_awal;
        $baru->tanggal_akhir = $data->tanggal_akhir;

        if($baru->tanggal_awal && $baru->tanggal_akhir){
            if(\Carbon\Carbon::parse($baru->tanggal_awal) > \Carbon\Carbon::parse($baru->tanggal_akhir)){
                throw new Exception("Tanggal awal tidak boleh lebih dari tanggal akhir");
            }
        }
        $baru->tanggal_realisasi = $data->tanggal_realisasi;

        // $baru->nilai_kuantitas = $data->nilai_kuantitas;
        $baru->nilai_kualitas = $data->nilai_kualitas;
        // $baru->nilai_waktu = $data->nilai_waktu;
        // $baru->nilai = $data->nilai;
        $baru->bukti_url = $data->bukti_url;
        $baru->save();
        $baru->reload_nilai();
        return $baru;
    }

    public function helper_ubah(Kegiatan_Internal_Master $monitoring_Kegiatan, $pemberi_type
        , Satuan_Kerja_Tahun $pemberi_satuan_kerja_tahun, ?Fungsi $pemberi_fungsi, ?Tim $pemberi_tim, Tim $penerima_tim, $data)
    {
        if($this->status != "selesai" && $data->status == "selesai"){
            Kegiatan_Internal_Berkas::where('type_berkas', 'pengajuan')
                ->where('kegiatan_internal_kegiatan_id', $this->id)
                ->update(['status_approval' => 3]);
        }
        $this->kegiatan_internal_master_id = $monitoring_Kegiatan->id;
        $this->bulan = $monitoring_Kegiatan->bulan;
        $this->tahun = $monitoring_Kegiatan->tahun;

        if($pemberi_type == 'satuan_kerja_tahun'){
            $this->pemberi_satuan_kerja_tahun_id = $pemberi_satuan_kerja_tahun->id;
            $this->pemberi_fungsi_id = null;
            $this->pemberi_tim_id = null;
        }else if($pemberi_type == 'fungsi'){
            $this->pemberi_satuan_kerja_tahun_id = $pemberi_fungsi->satuan_kerja_tahun_id;
            $this->pemberi_fungsi_id = $pemberi_fungsi->id;
            $this->pemberi_tim_id = null;
        }else if($pemberi_type == 'tim'){
            $this->pemberi_satuan_kerja_tahun_id = $pemberi_tim->satuan_kerja_tahun_id;
            $this->pemberi_fungsi_id = $pemberi_tim->fungsi_id;
            $this->pemberi_tim_id = $pemberi_tim->id;
        }
        $this->pemberi_username = Auth::user()->username;

        $this->pelaksana_satuan_kerja_tahun_id = $penerima_tim->satuan_kerja_tahun_id;
        $this->pelaksana_tim_id = $penerima_tim->id;
        $this->pengaturan = $data->pengaturan;
        $this->nama = $data->nama;
        $this->satuan = $data->satuan;
        $this->target = $data->target;
        $this->realisasi = $data->realisasi;

        $this->status = $data->status;
        $this->tanggal_awal = $data->tanggal_awal;
        $this->tanggal_akhir = $data->tanggal_akhir;

        if($this->tanggal_awal && $this->tanggal_akhir){
            if(\Carbon\Carbon::parse($this->tanggal_awal) > \Carbon\Carbon::parse($this->tanggal_akhir)){
                throw new Exception("Tanggal awal tidak boleh lebih dari tanggal akhir");
            }
        }
        $this->tanggal_realisasi = $data->tanggal_realisasi;

        // $this->nilai_kuantitas = $data->nilai_kuantitas;
        $this->nilai_kualitas = $data->nilai_kualitas;
        // $this->nilai_waktu = $data->nilai_waktu;
        // $this->nilai = $data->nilai;
        $this->bukti_url = $data->bukti_url;
        $this->save();
        $this->reload_nilai();
    }

    public function helper_hapus()
    {
        Kegiatan_Internal_Berkas::where('kegiatan_internal_kegiatan_id', $this->id)->delete();
        $this->delete();
    }


    public function penyelesaian($realisasi, $tanggal_realisasi, $kualitas){
        $this->realisasi = $realisasi;
        $this->tanggal_realisasi = $tanggal_realisasi;
        $this->nilai_kualitas = $kualitas;
        $this->reload_nilai();
    }

    public function reload_nilai(){
        if($this->target == 0 || $this->target == null || $this->target == ''){
            $this->nilai_kuantitas = 0;
        }else{
            if($this->pengaturan['kondisi_capaian'] == 'normal'){
                if($this->realisasi >= $this->target){
                    $this->nilai_kuantitas = round(100, 4);
                }else{
                    $this->nilai_kuantitas = round($this->realisasi/$this->target * 100, 4);
                }
            }else if($this->pengaturan['kondisi_capaian'] == 'khusus'){
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

    public function master()
    {
        return $this->hasOne(Kegiatan_Internal_Master::class, 'id', 'kegiatan_internal_master_id');
    }

    public function berkas()
    {
        return $this->hasMany(Kegiatan_Internal_Berkas::class, 'kegiatan_internal_kegiatan_id', 'id');
    }

}
