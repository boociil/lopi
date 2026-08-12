<?php

namespace App\Observers;

use App\Model\Lopi\Pinjam_Zoom;
use App\Model\Lopi\User_Setting;
use App\Model\Umum\Peran;
use App\Services\Wablas_Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PinjamZoomObserver
{
    /**
     * Handle the Pinjam_Zoom "created" event.
     */
    public function created(Pinjam_Zoom $pinjamZoom): void
    {
        $wa_service =  new Wablas_Service();
        $tanggal = $pinjamZoom->tipe === 'berulang'
            ? $pinjamZoom->berulang_tanggal_awal . ' s.d. ' . $pinjamZoom->berulang_tanggal_akhir
            : $pinjamZoom->tanggal;


        if($pinjamZoom->status == 'menunggu'){
            $nama_pengaju = Auth::user()->nama;

            $wa_teks = "Pesan Otomatis – Sistem Lopi Pinjam Zoom

Terdapat pengajuan peminjaman akun Zoom yang memerlukan persetujuan.
*Detail Pengajuan*
• Kegiatan : {$pinjamZoom->kegiatan}
• Tipe : {$pinjamZoom->tipe}
• Tanggal : {$tanggal}
• Waktu : {$pinjamZoom->waktu_awal} - {$pinjamZoom->waktu_akhir}
• Status : Menunggu Approval
• Diajukan Oleh : {$nama_pengaju}

Silakan lakukan approval melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/pinjam/zoom

Terima kasih.";

            $peranZoom = Peran::where('peran', 'zoom')->get()->pluck('pemilik_username')->toArray();

            $peranZoom = array_unique($peranZoom);

            $no_wa_send =  User_Setting::whereIn('username', $peranZoom)->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();

            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }else if($pinjamZoom->status == 'disetujui'){
            $nama_pengaju = Auth::user()->nama;
            $wa_teks = "Pesan Otomatis – Sistem Lopi Pinjam Zoom

Pengajuan peminjaman akun Zoom telah disetujui.
*Detail Pengajuan*
• Kegiatan : {$pinjamZoom->kegiatan}
• Tipe : {$pinjamZoom->tipe}
• Tanggal : {$tanggal}
• Waktu : {$pinjamZoom->waktu_awal} - {$pinjamZoom->waktu_akhir}
• Status : Disetujui
• Diajukan Oleh : {$nama_pengaju}

Peminjaman akun zoom bisa diakses melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/pinjam/zoom

Terima kasih.";

            $no_wa_send =  User_Setting::whereIn('username', [$pinjamZoom->pembuat_username])->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();
            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }
    }

    /**
     * Handle the Pinjam_Zoom "updated" event.
     */
    public function updated(Pinjam_Zoom $pinjamZoom): void
    {
        $wa_service =  new Wablas_Service();
        $tanggal = $pinjamZoom->tipe === 'berulang'
            ? $pinjamZoom->berulang_tanggal_awal . ' s.d. ' . $pinjamZoom->berulang_tanggal_akhir
            : $pinjamZoom->tanggal;


        if($pinjamZoom->status == 'menunggu'){
            $nama_pengaju = Auth::user()->nama;
            $wa_teks = "Pesan Otomatis – Sistem Lopi Pinjam Zoom

Terdapat pengajuan peminjaman akun Zoom yang memerlukan persetujuan.
*Detail Pengajuan*
• Kegiatan : {$pinjamZoom->kegiatan}
• Tipe : {$pinjamZoom->tipe}
• Tanggal : {$tanggal}
• Waktu : {$pinjamZoom->waktu_awal} - {$pinjamZoom->waktu_akhir}
• Status : Menunggu disetujui
• Diajukan Oleh : {$nama_pengaju}

Silakan lakukan approval melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/pinjam/zoom

Terima kasih.";
            $peranZoom = Peran::where('peran', 'zoom')->get()->pluck('pemilik_username')->toArray();

            $peranZoom = array_unique($peranZoom);

            $no_wa_send =  User_Setting::whereIn('username', $peranZoom)->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();
            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }else if($pinjamZoom->status == 'disetujui'){
            $nama_pengaju = Auth::user()->nama;
            $wa_teks = "Pesan Otomatis – Sistem Lopi Pinjam Zoom

Pengajuan peminjaman akun Zoom telah disetujui.
*Detail Pengajuan*
• Kegiatan : {$pinjamZoom->kegiatan}
• Tipe : {$pinjamZoom->tipe}
• Tanggal : {$tanggal}
• Waktu : {$pinjamZoom->waktu_awal} - {$pinjamZoom->waktu_akhir}
• Status : Disetujui
• Diajukan Oleh : {$nama_pengaju}

Peminjaman akun zoom bisa diakses melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/pinjam/zoom

Terima kasih.";

            $no_wa_send =  User_Setting::whereIn('username', [$pinjamZoom->pembuat_username])->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();
            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }
    }
}
