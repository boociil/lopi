<?php

namespace App\Observers;

use App\Model\Lopi\Pinjam_Mobil;
use App\Model\Lopi\User_Setting;
use App\Model\Umum\Peran;
use App\Services\Wablas_Service;
use Illuminate\Support\Facades\Auth;

class PinjamMobilObserver
{
    /**
     * Handle the Pinjam_Mobil "created" event.
     */
    public function created(Pinjam_Mobil $pinjamMobil): void
    {
        $wa_service =  new Wablas_Service();
        $tanggal = $pinjamMobil->tipe === 'berulang'
            ? $pinjamMobil->berulang_tanggal_awal . ' s.d. ' . $pinjamMobil->berulang_tanggal_akhir
            : $pinjamMobil->tanggal;


        if($pinjamMobil->status == 'menunggu'){
            $nama_pengaju = Auth::user()->nama;

            $wa_teks = "Pesan Otomatis – Sistem Lopi Pinjam Mobil

Terdapat pengajuan peminjaman mobil yang memerlukan persetujuan.
*Detail Pengajuan*
• Kegiatan : {$pinjamMobil->kegiatan}
• Tipe : {$pinjamMobil->tipe}
• Tanggal : {$tanggal}
• Waktu : {$pinjamMobil->waktu_awal} - {$pinjamMobil->waktu_akhir}
• Status : Menunggu Approval
• Diajukan Oleh : {$nama_pengaju}

Silakan lakukan approval melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/pinjam/mobil

Terima kasih.";

            $peranMobil = Peran::where('peran', 'mobil')->get()->pluck('pemilik_username')->toArray();

            $peranMobil = array_unique($peranMobil);

            $no_wa_send =  User_Setting::whereIn('username', $peranMobil)->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();

            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }else if($pinjamMobil->status == 'disetujui'){
            $nama_pengaju = Auth::user()->nama;
            $wa_teks = "Pesan Otomatis – Sistem Lopi Pinjam Mobil

Pengajuan peminjaman mobil telah disetujui.
*Detail Pengajuan*
• Kegiatan : {$pinjamMobil->kegiatan}
• Tipe : {$pinjamMobil->tipe}
• Tanggal : {$tanggal}
• Waktu : {$pinjamMobil->waktu_awal} - {$pinjamMobil->waktu_akhir}
• Status : Disetujui
• Diajukan Oleh : {$nama_pengaju}

Peminjaman mobil bisa diakses melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/pinjam/mobil

Terima kasih.";

            $no_wa_send =  User_Setting::whereIn('username', [$pinjamMobil->pembuat_username])->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();
            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }
    }

    /**
     * Handle the Pinjam_Mobil "updated" event.
     */
    public function updated(Pinjam_Mobil $pinjamMobil): void
    {
                $wa_service =  new Wablas_Service();
        $tanggal = $pinjamMobil->tipe === 'berulang'
            ? $pinjamMobil->berulang_tanggal_awal . ' s.d. ' . $pinjamMobil->berulang_tanggal_akhir
            : $pinjamMobil->tanggal;


        if($pinjamMobil->status == 'menunggu'){
            $nama_pengaju = Auth::user()->nama;
            $wa_teks = "Pesan Otomatis – Sistem Lopi Pinjam Mobil

Terdapat pengajuan peminjaman mobil yang memerlukan persetujuan.
*Detail Pengajuan*
• Kegiatan : {$pinjamMobil->kegiatan}
• Tipe : {$pinjamMobil->tipe}
• Tanggal : {$tanggal}
• Waktu : {$pinjamMobil->waktu_awal} - {$pinjamMobil->waktu_akhir}
• Status : Menunggu disetujui
• Diajukan Oleh : {$nama_pengaju}

Silakan lakukan approval melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/pinjam/mobil

Terima kasih.";
            $peranMobil = Peran::where('peran', 'mobil')->get()->pluck('pemilik_username')->toArray();

            $peranMobil = array_unique($peranMobil);

            $no_wa_send =  User_Setting::whereIn('username', $peranMobil)->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();
            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }else if($pinjamMobil->status == 'disetujui'){
            $nama_pengaju = Auth::user()->nama;
            $wa_teks = "Pesan Otomatis – Sistem Lopi Pinjam Mobil

Pengajuan peminjaman akun Mobil telah disetujui.
*Detail Pengajuan*
• Kegiatan : {$pinjamMobil->kegiatan}
• Tipe : {$pinjamMobil->tipe}
• Tanggal : {$tanggal}
• Waktu : {$pinjamMobil->waktu_awal} - {$pinjamMobil->waktu_akhir}
• Status : Disetujui
• Diajukan Oleh : {$nama_pengaju}

Peminjaman mobil bisa diakses melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/pinjam/mobil

Terima kasih.";

            $no_wa_send =  User_Setting::whereIn('username', [$pinjamMobil->pembuat_username])->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();
            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }
    }
}
