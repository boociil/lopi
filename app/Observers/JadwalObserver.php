<?php

namespace App\Observers;

use App\Model\Lopi\Jadwal;
use App\Model\Lopi\User_Setting;
use App\Model\Umum\Peran;
use App\Services\Wablas_Service;
use Illuminate\Support\Facades\Auth;

class JadwalObserver
{
    public function created(Jadwal $jadwal): void
    {
        $wa_service =  new Wablas_Service();
        $tanggal = $jadwal->tipe === 'berulang'
            ? $jadwal->berulang_tanggal_awal . ' s.d. ' . $jadwal->berulang_tanggal_akhir
            : $jadwal->tanggal;


        if($jadwal->ruang_status == 'menunggu'){
            $nama_pengaju = Auth::user()->nama;

            $wa_teks = "Pesan Otomatis – Sistem Lopi Pinjam Ruang

Terdapat pengajuan peminjaman ruang yang memerlukan persetujuan.
*Detail Pengajuan*
• Kegiatan : {$jadwal->kegiatan}
• Tipe : {$jadwal->tipe}
• Tanggal : {$tanggal}
• Waktu : {$jadwal->waktu_awal} - {$jadwal->waktu_akhir}
• Runag : {$jadwal->ruang}
• Status : Menunggu Approval
• Diajukan Oleh : {$nama_pengaju}

Silakan lakukan approval melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/jadwal

Terima kasih.";

            $peranRuang = Peran::where('peran', 'ruang')->get()->pluck('pemilik_username')->toArray();

            $peranRuang = array_unique($peranRuang);

            $no_wa_send =  User_Setting::whereIn('username', $peranRuang)->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();

            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }

                if($jadwal->ruang_status == 'disetujui' && Auth::user()->username != $jadwal->pembuat_username){
            $nama_pengaju = Auth::user()->nama;

            $wa_teks = "Pesan Otomatis – Sistem Lopi Jadwal

Pengajuan Jadwal telah disetujui.
*Detail Pengajuan*
• Kegiatan : {$jadwal->kegiatan}
• Tipe : {$jadwal->tipe}
• Tanggal : {$tanggal}
• Waktu : {$jadwal->waktu_awal} - {$jadwal->waktu_akhir}
• Runag : {$jadwal->ruang}
• Status : Disetujui (Zoom dan Ruang Jika ada)
• Diajukan Oleh : {$nama_pengaju}

Jadwal  bisa diakses melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/jadwal

Terima kasih.";

            $no_wa_send =  User_Setting::whereIn('username', [$jadwal->pembuat_username])->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();
            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }
    }

    public function updated(Jadwal $jadwal): void
    {
        $wa_service =  new Wablas_Service();
        $tanggal = $jadwal->tipe === 'berulang'
            ? $jadwal->berulang_tanggal_awal . ' s.d. ' . $jadwal->berulang_tanggal_akhir
            : $jadwal->tanggal;


        if($jadwal->ruang_status == 'menunggu'){
            $nama_pengaju = Auth::user()->nama;

            $wa_teks = "Pesan Otomatis – Sistem Lopi Pinjam Ruang

Terdapat pengajuan peminjaman ruang yang memerlukan persetujuan.
*Detail Pengajuan*
• Kegiatan : {$jadwal->kegiatan}
• Tipe : {$jadwal->tipe}
• Tanggal : {$tanggal}
• Waktu : {$jadwal->waktu_awal} - {$jadwal->waktu_akhir}
• Runag : {$jadwal->ruang}
• Status : Menunggu Approval
• Diajukan Oleh : {$nama_pengaju}

Silakan lakukan approval melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/jadwal

Terima kasih.";

            $peranRuang = Peran::where('peran', 'ruang')->get()->pluck('pemilik_username')->toArray();

            $peranRuang = array_unique($peranRuang);

            $no_wa_send =  User_Setting::whereIn('username', $peranRuang)->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();

            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }


        if($jadwal->ruang_status == 'disetujui' && Auth::user()->username != $jadwal->pembuat_username){
            $nama_pengaju = Auth::user()->nama;

            $wa_teks = "Pesan Otomatis – Sistem Lopi Jadwal

Pengajuan Jadwal telah disetujui.
*Detail Pengajuan*
• Kegiatan : {$jadwal->kegiatan}
• Tipe : {$jadwal->tipe}
• Tanggal : {$tanggal}
• Waktu : {$jadwal->waktu_awal} - {$jadwal->waktu_akhir}
• Runag : {$jadwal->ruang}
• Status : Disetujui (Zoom dan Ruang Jika ada)
• Diajukan Oleh : {$nama_pengaju}

Jadwal  bisa diakses melalui tautan berikut:
https://sulbar.web.bps.go.id/lopi/jadwal

Terima kasih.";

            $no_wa_send =  User_Setting::whereIn('username', [$jadwal->pembuat_username])->where('category', 'profile')->where('key', 'no_wa')->get()->pluck('data')->toArray();
            foreach($no_wa_send as $itemWa){
                $wa_service->sendSimpleText($itemWa, $wa_teks);
            }
        }
    }

}
