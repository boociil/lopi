<?php

namespace App\Model\Jurnal_Harian;

use App\Model\Umum\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class Jurnal_Harian_Item extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_jurnal_harian_item';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'tahun', 'jurnal_harian_proyek_id', 'type'
        , 'satuan_kerja_tahun_id', 'fungsi_id', 'tim_id', 'pembuat_username', 'pelaksana_username'
        , 'pengaturan', 'parent_id', 'status_pekerjaan', 'status_nilai'
        , 'nama', 'satuan', 'target', 'realisasi'
        , 'tanggal_deadline', 'tanggal_realisasi', 'jam_realisasi_awal', 'jam_realisasi_akhir'
        , 'nilai_kuantitas', 'nilai_kualitas', 'nilai_waktu', 'nilai', 'bukti_url'];

    protected $withCount = ['berkas'];

    protected $casts = [
        'pengaturan' => 'array',
    ];

    // protected static function boot()
    // {
    //     parent::boot();
    //     static::addGlobalScope('default_sort', function (Builder $builder) {
    //         $builder->orderBy('tanggal_realisasi', 'asc');
    //         $builder->orderBy('tanggal_deadline', 'asc');
    //     });
    // }

    public static function helper_tambah_personal(?Jurnal_Harian_Proyek $jurnal_Harian_proyek, $data, $tambahan_data)
    {
        if($data->other['state_tambah'] == 1){
            $baru = Jurnal_Harian_Item::where('id', $data->other['parent_id'])
                        ->where('jurnal_harian_proyek_id', $jurnal_Harian_proyek->id)
                        ->whereIn('type', ['1', '2', '3'])
                        ->where('pelaksana_username', Auth::user()->username)
                        ->first();
            $baru->status_pekerjaan = 2;
            $baru->bukti_url = $data->bukti_url;
            $baru->realisasi = $data->realisasi;
            $baru->tanggal_realisasi = $data->tanggal_realisasi;
            $baru->jam_realisasi_awal = $data->jam_realisasi_awal;
            $baru->jam_realisasi_akhir = $data->jam_realisasi_akhir;
            $baru->jam_realisasi_akhir = $data->jam_realisasi_akhir;
            $baru->status_nilai = 1;
            $baru->save();
            $baru->reload_nilai();

            Jurnal_Harian_Item::where('parent_id', $data->other['parent_id'])
                ->where('tanggal_realisasi', $data->tanggal_realisasi)
                ->delete();
            return $baru;
        }else if($data->other['state_tambah'] == 2){
            $lama = Jurnal_Harian_Item::where('id', $data->other['parent_id'])
                ->where('jurnal_harian_proyek_id', $jurnal_Harian_proyek->id)
                ->whereIn('type', ['1', '2', '3'])
                ->where('pelaksana_username', Auth::user()->username)
                ->first();
            $lama->makeHidden(['berkas_count']);
            $lama->status_pekerjaan = 1;
            $lama->save();

            $baru = new Jurnal_Harian_Item();
            foreach($lama->fillable as $item_field){
                $baru->{$item_field} =  $lama->{$item_field};
            }
            $baru->id = (string) Uuid::generate();
            $baru->type = '0';
            $baru->pembuat_username = Auth::user()->username;
            $baru->pelaksana_username = Auth::user()->username;
            $baru->parent_id = $lama->id;
            $baru->status_pekerjaan = 1;

            $baru->pengaturan = $data->pengaturan;
            $baru->satuan = $data->satuan;
            $baru->target = $data->target;
            $baru->realisasi = $data->realisasi;
            $baru->bukti_url = $data->bukti_url;

            $baru->tanggal_deadline = $data->tanggal_realisasi;
            $baru->tanggal_realisasi = $data->tanggal_realisasi;
            $baru->jam_realisasi_awal = $data->jam_realisasi_awal;
            $baru->jam_realisasi_akhir = $data->jam_realisasi_akhir;
            $baru->status_nilai = 1;
            $baru->save();

            $baru->reload_nilai();
            return $baru;
        }else if($data->other['state_tambah'] == 3){
            $baru =  new Jurnal_Harian_Item;
            $baru->id =  (string) Uuid::generate();
            $baru->tahun = $jurnal_Harian_proyek->tahun;
            $baru->jurnal_harian_proyek_id = $jurnal_Harian_proyek->id;
            $baru->type = '0';

            $baru->satuan_kerja_tahun_id = $jurnal_Harian_proyek->satuan_kerja_tahun_id;
            $baru->fungsi_id = $jurnal_Harian_proyek->fungsi_id;
            $baru->tim_id = $jurnal_Harian_proyek->tim_id;
            $baru->pembuat_username = Auth::user()->username;
            $baru->pelaksana_username = Auth::user()->username;

            $baru->pengaturan = $data->pengaturan;
            $baru->parent_id = null;
            if($data->status_pekerjaan == 0){
                $baru->status_pekerjaan = 0;
                $baru->status_nilai = 0;
                $baru->bukti_url = null;

                $baru->nama = $data->nama;
                $baru->satuan = $data->satuan;
                $baru->target = $data->target;
                $baru->realisasi = 0;

                $baru->tanggal_deadline = $data->tanggal_deadline;
                // $baru->tanggal_realisasi = $data->tanggal_realisasi;
                // $baru->jam_realisasi_awal = $data->jam_realisasi_awal;
                // $baru->jam_realisasi_akhir = $data->jam_realisasi_akhir;

                $baru->save();
                $baru->reload_nilai();
            }else if($data->status_pekerjaan == 2){
                $baru->status_pekerjaan = 2;
                $baru->status_nilai = 1;
                $baru->bukti_url = $data->bukti_url;

                $baru->nama = $data->nama;
                $baru->satuan = $data->satuan;
                $baru->target = $data->target;
                $baru->realisasi = $data->realisasi;

                $baru->tanggal_deadline = $data->tanggal_realisasi;
                $baru->tanggal_realisasi = $data->tanggal_realisasi;
                $baru->jam_realisasi_awal = $data->jam_realisasi_awal;
                $baru->jam_realisasi_akhir = $data->jam_realisasi_akhir;

                $baru->save();
                $baru->reload_nilai();
            }else {
                throw new Exception();
            }

            return $baru;

        }else if($data->other['state_tambah'] == 4){
            $baru =  new Jurnal_Harian_Item;
            $baru->id =  (string) Uuid::generate();
            $baru->tahun = session('tahun_active');
            $baru->jurnal_harian_proyek_id = null;
            $baru->type = '4';

            $baru->satuan_kerja_tahun_id = $tambahan_data['satuan_kerja_tahun_id'];
            $baru->fungsi_id = $tambahan_data['fungsi_id'];
            $baru->tim_id = $tambahan_data['tim_id'];
            $baru->pembuat_username = Auth::user()->username;
            $baru->pelaksana_username = Auth::user()->username;

            $baru->pengaturan = $data->pengaturan;
            $baru->parent_id = null;

            if($data->status_pekerjaan == 0){
                $baru->status_pekerjaan = 0;
                $baru->status_nilai = 0;
                $baru->bukti_url = null;

                $baru->nama = $data->nama;
                $baru->satuan = $data->satuan;
                $baru->target = $data->target;
                $baru->realisasi = 0;

                $baru->tanggal_deadline = $data->tanggal_deadline;
                // $baru->tanggal_realisasi = $data->tanggal_realisasi;
                // $baru->jam_realisasi_awal = $data->jam_realisasi_awal;
                // $baru->jam_realisasi_akhir = $data->jam_realisasi_akhir;

                $baru->save();
                $baru->reload_nilai();
            }else if($data->status_pekerjaan == 2){
                $baru->status_pekerjaan = 2;
                $baru->status_nilai = 1;
                $baru->bukti_url = $data->bukti_url;

                $baru->nama = $data->nama;
                $baru->satuan = $data->satuan;
                $baru->target = $data->target;
                $baru->realisasi = $data->realisasi;

                $baru->tanggal_deadline = $data->tanggal_realisasi;
                $baru->tanggal_realisasi = $data->tanggal_realisasi;
                $baru->jam_realisasi_awal = $data->jam_realisasi_awal;
                $baru->jam_realisasi_akhir = $data->jam_realisasi_akhir;

                $baru->save();
                $baru->reload_nilai();
            }else {
                throw new Exception();
            }
            return $baru;
        }
    }

    public function helper_ubah_personal_2($data, $tambahan_data = null)
    {
        if($this->status_nilai == 3){
            $this->bukti_url = $data->bukti_url;
            $this->save();
            $this->reload_nilai();
            return;
        }

        if(!is_null($tambahan_data)){
            $this->type = '4';

            $this->satuan_kerja_tahun_id = $tambahan_data['satuan_kerja_tahun_id'];
            $this->fungsi_id = $tambahan_data['fungsi_id'];
            $this->tim_id = $tambahan_data['tim_id'];
        }

        if($data->other['state_tambah'] == 1){
            $this->bukti_url = $data->bukti_url;
            $this->realisasi = $data->realisasi;

            $this->tanggal_realisasi = $data->tanggal_realisasi;
            $this->jam_realisasi_awal = $data->jam_realisasi_awal;
            $this->jam_realisasi_akhir = $data->jam_realisasi_akhir;
            $this->status_nilai = 1;
            $this->save();
            $this->reload_nilai();
        }else if($data->other['state_tambah'] == 2){
            $this->pengaturan = $data->pengaturan;
            $this->bukti_url = $data->bukti_url;

            $this->satuan = $data->satuan;
            $this->target = $data->target;
            $this->realisasi = $data->realisasi;

            $this->tanggal_deadline = $data->tanggal_realisasi;
            $this->tanggal_realisasi = $data->tanggal_realisasi;
            $this->jam_realisasi_awal = $data->jam_realisasi_awal;
            $this->jam_realisasi_akhir = $data->jam_realisasi_akhir;
            $this->status_nilai = 1;
            $this->save();
            $this->reload_nilai();
        }else if($data->other['state_tambah'] == 3 || $data->other['state_tambah'] == 4){
            $this->pengaturan = $data->pengaturan;
            $this->parent_id = null;

            if($data->status_pekerjaan == 0){
                $this->status_pekerjaan = 0;
                $this->status_nilai = 0;
                $this->bukti_url = null;

                $this->nama = $data->nama;
                $this->satuan = $data->satuan;
                $this->target = $data->target;
                $this->realisasi = 0;

                $this->tanggal_deadline = $data->tanggal_deadline;
                $this->tanggal_realisasi = null;
                $this->jam_realisasi_awal = null;
                $this->jam_realisasi_akhir = null;

                $this->save();
                $this->reload_nilai();
            }else if($data->status_pekerjaan == 2){
                $this->status_pekerjaan = 2;
                $this->status_nilai = 1;
                $this->bukti_url = $data->bukti_url;

                $this->nama = $data->nama;
                $this->satuan = $data->satuan;
                $this->target = $data->target;
                $this->realisasi = $data->realisasi;

                $this->tanggal_deadline = $data->tanggal_realisasi;
                $this->tanggal_realisasi = $data->tanggal_realisasi;
                $this->jam_realisasi_awal = $data->jam_realisasi_awal;
                $this->jam_realisasi_akhir = $data->jam_realisasi_akhir;

                $this->save();
                $this->reload_nilai();
            }else {
                throw new Exception();
            }
        }
    }

    public static function helper_tambah_penugasan_tambahan($data, $tambahan_data)
    {
        $baru =  new Jurnal_Harian_Item;
        $baru->id =  (string) Uuid::generate();
        $baru->tahun = session('tahun_active');
        $baru->jurnal_harian_proyek_id = null;
        $baru->type = '4';

        $baru->satuan_kerja_tahun_id = $tambahan_data['satuan_kerja_tahun_id'];
        $baru->fungsi_id = $tambahan_data['fungsi_id'];
        $baru->tim_id = $tambahan_data['tim_id'];
        $baru->pembuat_username = Auth::user()->username;
        $baru->pelaksana_username = $data->pelaksana_username;

        $baru->pengaturan = $data->pengaturan;
        $baru->parent_id = null;

        if($data->status_pekerjaan == 0){
            $baru->status_pekerjaan = 0;
            $baru->status_nilai = 0;
            $baru->bukti_url = null;

            $baru->nama = $data->nama;
            $baru->satuan = $data->satuan;
            $baru->target = $data->target;
            $baru->realisasi = 0;

            $baru->tanggal_deadline = $data->tanggal_deadline;
            // $baru->tanggal_realisasi = $data->tanggal_realisasi;
            // $baru->jam_realisasi_awal = $data->jam_realisasi_awal;
            // $baru->jam_realisasi_akhir = $data->jam_realisasi_akhir;

            $baru->save();
            $baru->reload_nilai();
        }else if($data->status_pekerjaan == 2){
            $baru->status_pekerjaan = 2;
            $baru->status_nilai = 1;
            $baru->bukti_url = $data->bukti_url;

            $baru->nama = $data->nama;
            $baru->satuan = $data->satuan;
            $baru->target = $data->target;
            $baru->realisasi = $data->realisasi;

            $baru->tanggal_deadline = $data->tanggal_realisasi;
            $baru->tanggal_realisasi = $data->tanggal_realisasi;
            $baru->jam_realisasi_awal = $data->jam_realisasi_awal;
            $baru->jam_realisasi_akhir = $data->jam_realisasi_akhir;

            $baru->save();
            $baru->reload_nilai();
        }else {
            throw new Exception();
        }
        return $baru;
    }

    public function helper_ubah_penugasan_tambahan($data, $tambahan_data = null)
    {
        if($this->status_nilai == 3){
            $this->bukti_url = $data->bukti_url;
            $this->save();
            $this->reload_nilai();
            return;
        }

        if(!is_null($tambahan_data)){
            $this->type = '4';

            $this->satuan_kerja_tahun_id = $tambahan_data['satuan_kerja_tahun_id'];
            $this->fungsi_id = $tambahan_data['fungsi_id'];
            $this->tim_id = $tambahan_data['tim_id'];
        }


        $this->pengaturan = $data->pengaturan;
        $this->parent_id = null;
        $this->pelaksana_username = $data->pelaksana_username;

        if($data->status_pekerjaan == 0){
            $this->status_pekerjaan = 0;
            $this->status_nilai = 0;
            $this->bukti_url = null;

            $this->nama = $data->nama;
            $this->satuan = $data->satuan;
            $this->target = $data->target;
            $this->realisasi = 0;

            $this->tanggal_deadline = $data->tanggal_deadline;
            $this->tanggal_realisasi = null;
            $this->jam_realisasi_awal = null;
            $this->jam_realisasi_akhir = null;

            $this->save();
            $this->reload_nilai();
        }else if($data->status_pekerjaan == 2){
            $this->status_pekerjaan = 2;
            $this->status_nilai = 1;
            $this->bukti_url = $data->bukti_url;

            $this->nama = $data->nama;
            $this->satuan = $data->satuan;
            $this->target = $data->target;
            $this->realisasi = $data->realisasi;

            $this->tanggal_deadline = $data->tanggal_realisasi;
            $this->tanggal_realisasi = $data->tanggal_realisasi;
            $this->jam_realisasi_awal = $data->jam_realisasi_awal;
            $this->jam_realisasi_akhir = $data->jam_realisasi_akhir;

            $this->save();
            $this->reload_nilai();
        }else {
            throw new Exception();
        }
    }

    public static function helper_tambah_with_proyek($type, Jurnal_Harian_Proyek $jurnal_Harian_proyek, $data, $have_auth_penilaian_as_ketua)
    {
        $baru =  new Jurnal_Harian_Item;
        $baru->id =  (string) Uuid::generate();
        $baru->tahun = $jurnal_Harian_proyek->tahun;
        $baru->jurnal_harian_proyek_id = $jurnal_Harian_proyek->id;
        $baru->type = $type;

        $baru->satuan_kerja_tahun_id = $jurnal_Harian_proyek->satuan_kerja_tahun_id;
        $baru->fungsi_id = $jurnal_Harian_proyek->fungsi_id;
        $baru->tim_id = $jurnal_Harian_proyek->tim_id;
        $baru->pembuat_username = Auth::user()->username;
        $baru->pelaksana_username = $data->pelaksana_username;

        $baru->pengaturan = $data->pengaturan;
        $baru->parent_id = null;
        $baru->status_pekerjaan = $data->status_pekerjaan;

        $baru->nama = $data->nama;
        $baru->satuan = $data->satuan;
        $baru->target = $data->target;
        $baru->realisasi = $data->realisasi;

        $baru->tanggal_deadline = $data->tanggal_deadline;
        $baru->bukti_url = $data->bukti_url;
        $baru->status_nilai = 0;

        if($data->status_pekerjaan == 2){
            $baru->status_nilai = 1;
            $baru->tanggal_realisasi = $data->tanggal_realisasi;
            $baru->jam_realisasi_awal = $data->jam_realisasi_awal;
            $baru->jam_realisasi_akhir = $data->jam_realisasi_akhir;

            if($have_auth_penilaian_as_ketua){
                if(!is_null($data->nilai_kualitas) && $data->nilai_kualitas > 0){
                    $baru->status_nilai = 3;
                    $baru->nilai_kualitas = $data->nilai_kualitas;
                }
            }
        }

        $baru->save();
        $baru->reload_nilai();
        return $baru;
    }


    public function helper_ubah_with_proyek($data, $have_auth_penilaian_as_ketua, $jurnal_Harian_proyek = null)
    {
        if($this->status_nilai == 3){
            $this->bukti_url = $data->bukti_url;
            $this->save();
            $this->reload_nilai();
            return;
        }

        if(!is_null($jurnal_Harian_proyek)){
            $this->satuan_kerja_tahun_id = $jurnal_Harian_proyek->satuan_kerja_tahun_id;
            $this->fungsi_id = $jurnal_Harian_proyek->fungsi_id;
            $this->tim_id = $jurnal_Harian_proyek->tim_id;

            $this->tahun = $jurnal_Harian_proyek->tahun;
            $this->jurnal_harian_proyek_id = $jurnal_Harian_proyek->id;

            if(!is_null($jurnal_Harian_proyek->tim_id)){
                $this->type = 1;
            }else if(!is_null($jurnal_Harian_proyek->fungsi_id)){
                $this->type = 2;
            }else if(!is_null($jurnal_Harian_proyek->satuan_kerja_tahun_id)){
                $this->type = 3;
            }
        }

        $this->pelaksana_username = $data->pelaksana_username;

        $this->pengaturan = $data->pengaturan;
        $this->parent_id = null;
        $this->status_pekerjaan = $data->status_pekerjaan;

        $this->nama = $data->nama;
        $this->satuan = $data->satuan;
        $this->target = $data->target;
        $this->realisasi = $data->realisasi;

        $this->tanggal_deadline = $data->tanggal_deadline;
        $this->bukti_url = $data->bukti_url;

        if($this->status_pekerjaan == 2){
            $this->status_nilai = 1;
            $this->tanggal_realisasi = $data->tanggal_realisasi;
            $this->jam_realisasi_awal = $data->jam_realisasi_awal;
            $this->jam_realisasi_akhir = $data->jam_realisasi_akhir;

            if($have_auth_penilaian_as_ketua){
                if(!is_null($data->nilai_kualitas) && $data->nilai_kualitas > 0){
                    $this->status_nilai = 3;
                    $this->nilai_kualitas = $data->nilai_kualitas;
                }else{
                    $this->status_nilai = 1;
                    $this->nilai_kualitas = null;
                }
            }
        }else{
            $this->status_nilai = 0;
            $this->tanggal_realisasi = null;
            $this->jam_realisasi_awal = null;
            $this->jam_realisasi_akhir = null;

            $this->nilai_kualitas = null;
        }

        $this->save();
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

        if($this->nilai_kuantitas > 100){
            $this->nilai_kuantitas = round(100, 4);
        }

        if($this->tanggal_realisasi){
            $this->nilai_waktu = round(100, 4);
            if($this->tanggal_deadline && $this->tanggal_realisasi){
                $last_day = $this->tanggal_deadline;
                if(!$last_day){
                    $last_day = date_create(date($this->tahun."-".$this->bulan."-t"));
                }

                if($this->tanggal_realisasi <= $this->tanggal_deadline){
                    $this->nilai_waktu = round(100, 4);
                }else{
                    if(date_create($this->tanggal_realisasi) <= date_create($last_day)){
                        $this->nilai_waktu = 100;
                    }else{
                        $this->nilai_waktu = 100 - date_diff(date_create($this->tanggal_realisasi), date_create($last_day))->days;
                    }
                }
            }
        }else{
            $this->nilai_waktu = 0;
        }

        if($this->status_nilai == '3'){
            if($this->nilai_kualitas >= 100){
                $this->nilai_kualitas = round(100, 4);
            }

            if($this->nilai_kualitas > 100){
                $this->nilai_kualitas = round(100, 4);
            }else{
                $this->nilai_kualitas = $this->nilai_kualitas;
            }
        }else{
            $this->nilai_kualitas = 0;
        }

        $this->nilai = round( (($this->nilai_kuantitas + $this->nilai_kualitas + $this->nilai_waktu) / 3), 4);
        $this->save();
    }

    public function proyek()
    {
        return $this->hasOne(Jurnal_Harian_Proyek::class, 'id', 'jurnal_harian_proyek_id');
    }

    public function berkas()
    {
        return $this->hasMany(Jurnal_Harian_Berkas::class, 'jurnal_harian_item_id', 'id');
    }

    public function pelaksana_user(){
        return $this->hasOne(User::class, 'username', 'pelaksana_username');
    }

    public function pelaksana_user_nama(){
        return $this->pelaksana_user()->select('username', 'nama');
    }

}
