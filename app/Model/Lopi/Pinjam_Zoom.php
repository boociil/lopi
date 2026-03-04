<?php

namespace App\Model\Lopi;

use App\Http\Traits\Zoom_Traits;
use App\Model\Umum\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Pinjam_Zoom extends Model
{
    use Zoom_Traits;
    use SoftDeletes;

    protected $table = 'siakip2_pinjam_zoom';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'kegiatan',
        'password',
        'tipe',
        'tanggal',
        'berulang_tanggal_awal',
        'berulang_tanggal_akhir',
        'waktu_awal',
        'waktu_akhir',
        'status',
        'pembuat_username',
        'confirm_username',
        'zoom_akun',
        'zoom_meeting_id',
        'zoom_start_url',
        'zoom_join_url',
        'jadwal_id',
    ];

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'pembuat_username', 'username');
    }

    public function confirm()
    {
        return $this->belongsTo(User::class, 'confirm_username', 'username');
    }

    public static function helper_tambah(
        $kegiatah,
        $tipe,
        $password,
        $tanggal,
        $tanggal_awal,
        $tanggal_akhir,
        $waktu_awal,
        $waktu_akhir,
        $have_zoom_peran,
        $zoom_akun,
    )
    {
        $pinjam_zoom = new Pinjam_Zoom();
        $pinjam_zoom->id = Str::uuid();
        $pinjam_zoom->kegiatan = $kegiatah;
        $pinjam_zoom->tipe = $tipe;
        $pinjam_zoom->password = $password;

        if($tipe == 'sekali'){
            $pinjam_zoom->tanggal = $tanggal;
            $pinjam_zoom->waktu_awal = $waktu_awal;
            $pinjam_zoom->waktu_akhir = $waktu_akhir;
        }else if($tipe == 'berulang'){
            $pinjam_zoom->berulang_tanggal_awal = $tanggal_awal;
            $pinjam_zoom->berulang_tanggal_akhir = $tanggal_akhir;
            $pinjam_zoom->waktu_awal = $waktu_awal;
            $pinjam_zoom->waktu_akhir = $waktu_akhir;
        }

        $pinjam_zoom->status = 'menunggu';
        $pinjam_zoom->pembuat_username = Auth::user()->username;

        if($have_zoom_peran && in_array($zoom_akun, ['statsulbar', 'sp2020'])){
            $pinjam_zoom->status = 'disetujui';
            $pinjam_zoom->confirm_username = Auth::user()->username;

            if(in_array($zoom_akun, ['statsulbar', 'sp2020'])){
                $pinjam_zoom->define_config();
                $pinjam_zoom->zoom_meta_createMeeting($zoom_akun, $pinjam_zoom);
            }else{
                throw new Exception('Gagal membuat zoom');
            }
        }
        $pinjam_zoom->save();

        return $pinjam_zoom;
    }

    public function helper_ubah(
        $kegiatah,
        $tipe,
        $password,
        $tanggal,
        $tanggal_awal,
        $tanggal_akhir,
        $waktu_awal,
        $waktu_akhir,
        $have_zoom_peran,
        $zoom_akun,
    )
    {
        $this->define_config();
        $this->kegiatan = $kegiatah;
        $this->tipe = $tipe;
        $this->password = $password;
        if($tipe == 'sekali'){
            $this->tanggal = $tanggal;
            $this->waktu_awal = $waktu_awal;
            $this->waktu_akhir = $waktu_akhir;
        }else if($tipe == 'berulang'){
            $this->berulang_tanggal_awal = $tanggal_awal;
            $this->berulang_tanggal_akhir = $tanggal_akhir;
            $this->waktu_awal = $waktu_awal;
            $this->waktu_akhir = $waktu_akhir;
        }
        $this->status = 'menunggu';
        $this->pembuat_username = Auth::user()->username;

        if($have_zoom_peran && in_array($zoom_akun, ['statsulbar', 'sp2020'])){
            $this->status = 'disetujui';
            $this->confirm_username = Auth::user()->username;
            // if($this->zoom_meeting_id){
            //     $this->zoom_meta_updateMeeting($request->input('zoom_akun'), $pinjamZoom);
            // }else
            if(!$this->zoom_meeting_id){
                $this->zoom_meta_createMeeting($zoom_akun, $this);
            }else if($this->zoom_meeting_id && $this->zoom_akun != $zoom_akun){
                $this->zoom_meta_deleteMeeting($this->zoom_akun, $this);
                $this->zoom_meta_createMeeting($zoom_akun, $this);
            }else if($this->zoom_meeting_id && $this->zoom_akun == $zoom_akun){
                $this->zoom_meta_updateMeeting($zoom_akun, $this);
            }
        }else{
            if($this->zoom_meeting_id){
                $this->zoom_meta_deleteMeeting($this->zoom_akun, $this);
            }
        }
        $this->save();
    }

    public function helper_hapus($have_zoom_peran){
        $this->define_config();
        if(! ($have_zoom_peran || $this->pembuat_username == Auth::user()->username) ){
            throw new Exception('Gagal menghapus peminjaman zoom!');
        }

        if($this->zoom_meeting_id){
            $this->zoom_meta_deleteMeeting($this->zoom_akun, $this);
        }

        $this->delete();
    }

    public function helper_approval($status, $zoom_akun){
        $this->define_config();
        if($status == 'ditolak'){
            if($this->zoom_meeting_id){
                $this->zoom_meta_deleteMeeting($this->zoom_akun, $this);
            }
        }else if($status == 'disetujui'){
            if(!$this->zoom_meeting_id){
                $this->zoom_meta_createMeeting($zoom_akun, $this);
            }else {
                if($this->zoom_akun != $zoom_akun){
                    $this->zoom_meta_deleteMeeting($this->zoom_akun, $this);
                    $this->zoom_meta_createMeeting($zoom_akun, $this);
                }
            }
        }

        $this->status = $status;
        $this->confirm_username = Auth::user()->username;
        $this->save();
    }

}
