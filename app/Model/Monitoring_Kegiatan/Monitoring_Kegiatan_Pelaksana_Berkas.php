<?php

namespace App\Model\Monitoring_Kegiatan;

use App\Model\Umum\URL_Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class Monitoring_Kegiatan_Pelaksana_Berkas extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_monitoring_kegiatan_pelaksana_berkas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'monitoring_master_id', 'bulan', 'tahun'
        , 'monitoring_kegiatan_id', 'monitoring_kegiatan_pelaksana_id', 'username_pengirim', 'username_approval', 'status_approval'
        , 'type_berkas', 'text', 'nilai_baru', 'file_id'];

    protected $casts = [
        'nilai_baru' => 'array',
    ];

    protected $appends = ['updated_at_tulis'];

    public function getUpdatedAtTulisAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['updated_at'])
            ->format('d M Y, H:i');
    }

    public static function helper_tambah(Monitoring_Kegiatan_Pelaksana $monitoring_Kegiatan_Pelaksana, $data, $have_auth){
        $baru =  new Monitoring_Kegiatan_Pelaksana_Berkas;

        $baru->id =  (string) Uuid::generate();
        $baru->monitoring_master_id = $monitoring_Kegiatan_Pelaksana->monitoring_master_id;
        $baru->bulan = $monitoring_Kegiatan_Pelaksana->bulan;
        $baru->tahun = $monitoring_Kegiatan_Pelaksana->tahun;
        $baru->monitoring_kegiatan_id = $monitoring_Kegiatan_Pelaksana->monitoring_kegiatan_id;
        $baru->monitoring_kegiatan_pelaksana_id = $monitoring_Kegiatan_Pelaksana->id;


        $baru->username_pengirim = Auth::user()->username;
        $baru->username_approval = null;

        $baru->type_berkas = $data->type_berkas;

        if($baru->type_berkas == 'text'){
            if(!$have_auth['text']){
                abort(403);
            }
            $baru->text = $data->text;
        }else if($baru->type_berkas == 'url'){
            if(!$have_auth['text']){
                abort(403);
            }
            if(!$data->file){
                abort(400, 'input kurang');
            }
            $url_baru = URL_Model::helper_tambah($data);
            $baru->file_id = $url_baru->id;
        }else if($baru->type_berkas == 'realisasi'){
            $time_now_carbon = \Carbon\Carbon::today();

            if(!($have_auth['pengajuan'] && (
                    (\Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->tanggal_awal) <= $time_now_carbon && $time_now_carbon <= \Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->tanggal_akhir))
                    || (\Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->master->realisasi_tanggal_awal) <= $time_now_carbon && $time_now_carbon <= \Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->master->realisasi_tanggal_akhir))
                ))
                && !$have_auth['persetujuan']){
                    abort(403);
            }

            if(!($monitoring_Kegiatan_Pelaksana->status == 'belum' || $monitoring_Kegiatan_Pelaksana->status == 'tolak')){
                abort(403);
            }

            if(!$data->nilai_baru){
                abort(400);
            }

            if(!(array_key_exists('realisasi', $data->nilai_baru))){
                abort(400);
            }
            $baru->nilai_baru = $data->nilai_baru;

            $monitoring_Kegiatan_Pelaksana->realisasi =  $data->nilai_baru['realisasi'];
            $monitoring_Kegiatan_Pelaksana->reload_nilai($monitoring_Kegiatan_Pelaksana->kegiatan);
        }else if($baru->type_berkas == 'pengajuan'){
            $time_now_carbon = \Carbon\Carbon::today();

            if(!($have_auth['pengajuan'] && (
                    (\Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->tanggal_awal) <= $time_now_carbon && $time_now_carbon <= \Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->tanggal_akhir))
                    || (\Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->master->realisasi_tanggal_awal) <= $time_now_carbon && $time_now_carbon <= \Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->master->realisasi_tanggal_akhir))
                ))
                 && !$have_auth['persetujuan']){
                    abort(403);
            }

            if(!$data->nilai_baru){
                abort(400);
            }

            if(!(array_key_exists('realisasi', $data->nilai_baru) && array_key_exists('tanggal_realisasi', $data->nilai_baru))){
                abort(400);
            }
            $baru->nilai_baru = $data->nilai_baru;

            Monitoring_Kegiatan_Pelaksana_Berkas::where('type_berkas', 'pengajuan')
                ->where('monitoring_kegiatan_pelaksana_id', $monitoring_Kegiatan_Pelaksana->id)
                ->update(['status_approval' => 3]);

            if($data->type_berkas == 'pengajuan'){
                $monitoring_Kegiatan_Pelaksana->status = 'pengajuan';
                $monitoring_Kegiatan_Pelaksana->save();
            }

        }else if($baru->type_berkas == 'bukti'){
            $time_now_carbon = \Carbon\Carbon::today();
            if(!($have_auth['pengajuan'] && (
                    (\Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->tanggal_awal) <= $time_now_carbon && $time_now_carbon <= \Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->tanggal_akhir))
                    || (\Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->master->realisasi_tanggal_awal) <= $time_now_carbon && $time_now_carbon <= \Carbon\Carbon::parse($monitoring_Kegiatan_Pelaksana->master->realisasi_tanggal_akhir))
                ))
                && !$have_auth['persetujuan']){
                    abort(403);
            }

            if(!$data->nilai_baru){
                abort(400);
            }
            if(!(array_key_exists('url', $data->nilai_baru) )){
                abort(400);
            }
            $baru->nilai_baru = $data->nilai_baru;

            $monitoring_Kegiatan_Pelaksana->bukti_url = $data->nilai_baru['url'];
            $monitoring_Kegiatan_Pelaksana->save();
        }else{
            abort(400);
        }

        $baru->save();

        return $baru;
    }

    public function helper_approval($kualitas){
        if(!(is_numeric($kualitas) && 0 <= $kualitas && $kualitas <= 100)){
            abort(400);
        }
        $this->nilai_baru = ['realisasi' => $this->nilai_baru['realisasi']
            , 'tanggal_realisasi' => $this->nilai_baru['tanggal_realisasi']
            , 'kualitas' => $kualitas];
        $this->username_approval = Auth::user()->username;
        $this->status_approval = 2;
        $this->save();
    }

    public function helper_hapus(){
        $this->delete();
    }

    public function file()
    {
        return $this->hasOne(URL_Model::class, 'id', 'file_id');
    }

    public function monitoring_kegiatan_pelaksana()
    {
        return $this->hasOne(Monitoring_Kegiatan_Pelaksana::class, 'id', 'monitoring_kegiatan_pelaksana_id');
    }

}
