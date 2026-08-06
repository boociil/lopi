<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use App\Observers\PinjamZoomObserver;
use App\Services\Zoom_Service;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Exception;

#[ObservedBy([PinjamZoomObserver::class])]
class Pinjam_Zoom extends Model
{
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
        Zoom_Service $zoom_service
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
                $zoom_service->zoom_meta_createMeeting($zoom_akun, $pinjam_zoom);
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
        Zoom_Service $zoom_service
    )
    {

        $berubahInti = true;
        if($this->tipe == $tipe &&  $this->tanggal == $tanggal
            && $this->berulang_tanggal_awal == $tanggal_awal
            && $this->berulang_tanggal_akhir == $tanggal_akhir
            && $this->waktu_awal == $waktu_awal
            && $this->waktu_akhir == $waktu_akhir){
             $berubahInti = false;
        }

        $this->kegiatan = $kegiatah;
        $this->tipe = $tipe;
        $this->password = $password;
        if($tipe == 'sekali'){
            $this->tanggal = $tanggal;
            $this->berulang_tanggal_awal = null;
            $this->berulang_tanggal_akhir = null;
            $this->waktu_awal = $waktu_awal;
            $this->waktu_akhir = $waktu_akhir;
        }else if($tipe == 'berulang'){
            $this->tanggal = null;
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
                $zoom_service->zoom_meta_createMeeting($zoom_akun, $this);
            }else if($this->zoom_meeting_id && $this->zoom_akun != $zoom_akun){
                $zoom_service->zoom_meta_deleteMeeting($this->zoom_akun, $this);
                $zoom_service->zoom_meta_createMeeting($zoom_akun, $this);
            }else if($this->zoom_meeting_id && $this->zoom_akun == $zoom_akun){
                $zoom_service->zoom_meta_updateMeeting($zoom_akun, $this);
            }
        }else{
            if($this->zoom_meeting_id){
                if($berubahInti){
                    $zoom_service->zoom_meta_deleteMeeting($this->zoom_akun, $this);
                }else{
                     $zoom_service->zoom_meta_updateMeeting($zoom_akun, $this);
                }
            }
        }
        $this->save();
    }

    public function helper_hapus($have_zoom_peran, Zoom_Service $zoom_service){
        if(! ($have_zoom_peran || $this->pembuat_username == Auth::user()->username) ){
            throw new Exception('Gagal menghapus peminjaman zoom!');
        }

        if($this->zoom_meeting_id){
            $zoom_service->zoom_meta_deleteMeeting($this->zoom_akun, $this);
        }

        $this->delete();
    }

    public function helper_approval($status, $zoom_akun, Zoom_Service $zoom_service){
        if($status == 'ditolak'){
            if($this->zoom_meeting_id){
                $zoom_service->zoom_meta_deleteMeeting($this->zoom_akun, $this);
            }
        }else if($status == 'disetujui'){
            if(!$this->zoom_meeting_id){
                $zoom_service->zoom_meta_createMeeting($zoom_akun, $this);
            }else {
                if($this->zoom_akun != $zoom_akun){
                    $zoom_service->zoom_meta_deleteMeeting($this->zoom_akun, $this);
                    $zoom_service->zoom_meta_createMeeting($zoom_akun, $this);
                }
            }
        }

        $this->status = $status;
        $this->confirm_username = Auth::user()->username;
        $this->save();
    }

    public function checkClash($zoom_akun){
        if($this->tipe == 'sekali'){
            return Pinjam_Zoom::staticCheckClash($zoom_akun, $this->tanggal, $this->tanggal, $this->waktu_awal, $this->waktu_akhir);
        }else if($this->tipe == 'berulang'){
            return Pinjam_Zoom::staticCheckClash($zoom_akun, $this->berulang_tanggal_awal, $this->berulang_tanggal_akhir, $this->waktu_awal, $this->waktu_akhir);
        }
    }

    public static function staticCheckClash($zoom_akun, $tanggal_awal, $tanggal_akhir, $waktu_awal, $waktu_akhir, $ignore_id = null){
        $jadwal_daftar = Pinjam_Zoom::where('zoom_akun', $zoom_akun)
                ->where('status', 'disetujui')
                ->where(function ($query0) use($tanggal_awal, $tanggal_akhir) {
                    $query0->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir){
                                $query->where('tipe', 'sekali')
                                    ->whereBetween('tanggal', [$tanggal_awal, $tanggal_akhir]);
                            })
                            ->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir) {
                                $query->where('tipe', 'berulang')
                                    ->where('berulang_tanggal_awal', '<=', $tanggal_akhir)
                                    ->where('berulang_tanggal_akhir', '>=', $tanggal_awal);
                            });
                })
                ->when($ignore_id, function($query) use ($ignore_id) {
                    return $query->where('id', '!=', $ignore_id);
                })
                ->get();

        $input_waktu_awal = Carbon::createFromFormat('H:i:s', $waktu_awal);
        $input_waktu_akhir = Carbon::createFromFormat('H:i:s', $waktu_akhir);

        $isClash = false;

        foreach($jadwal_daftar as $item){

            $item_waktu_awal = Carbon::createFromFormat('H:i:s', $item->waktu_awal);
            $item_waktu_akhir = Carbon::createFromFormat('H:i:s', $item->waktu_akhir);

            if ($input_waktu_awal < $item_waktu_akhir && $input_waktu_akhir > $item_waktu_awal) {
                $isClash = true;
                break;
            }
        }

        return $isClash;
    }

    public function getMeeting(Zoom_Service $zoom_service){
        if ($this->zoom_meeting_id){
            return $zoom_service->zoom_getMeeting($this->zoom_akun, $this->zoom_meeting_id);
        }else{
            return [
                'status' => false,
            ];
        }
    }

}
