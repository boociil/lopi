<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Appreciate_Data;
use App\Model\Lopi\Jadwal;
use App\Model\Lopi\Pinjam_Mobil;
use App\Model\Lopi\Pinjam_Ruang;
use App\Model\Lopi\Pinjam_Zoom;
use App\Model\Umum\Peran;

class Notif_Controller extends Controller
{
    public function get_menu_count()
    {
        $peran = Peran::get_my_peran();

        $count_appreciate = 0;
        $count_jadwal = 0;
        $count_pinjam_ruang = 0;
        $count_pinjam_zoom = 0;
        $count_pinjam_mobil = 0;

        if($peran->contains('peran', 'appreciate')){
             $count_appreciate = Appreciate_Data::where('status', '0')->count();
        }
        if($peran->contains('peran', 'ruang') || $peran->contains('peran', 'zoom')){
            $count_jadwal = Jadwal::where('status', 'menunggu')->count();
        }
        if($peran->contains('peran', 'ruang')){
            $count_pinjam_ruang = Pinjam_Ruang::where('status', 'menunggu')->count();
        }
        if($peran->contains('peran', 'zoom')){
            $count_pinjam_zoom = Pinjam_Zoom::where('status', 'menunggu')->count();
        }
        if($peran->contains('peran', 'mobil')){
            $count_pinjam_mobil = Pinjam_Mobil::where('status', 'menunggu')->count();
        }

        return[
           '-lopi-appreciate-aproval' => $count_appreciate,
           '-lopi-pinjam-jadwal' => $count_jadwal,
           '-lopi-pinjam-mobil' => $count_pinjam_mobil,
           '-lopi-pinjam-ruang' => $count_pinjam_ruang,
           '-lopi-pinjam-zoom' => $count_pinjam_zoom,
        ];
    }

}
