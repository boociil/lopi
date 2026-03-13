<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Appreciate_Data;
use App\Model\Lopi\Appreciate_Master;
use App\Model\Lopi\Jadwal;
use App\Model\Lopi\Pinjam_Mobil;
use App\Model\Lopi\Pinjam_Ruang;
use App\Model\Lopi\Pinjam_Zoom;
use App\Model\Umum\Peran;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Notif_Controller extends Controller
{
    public function get_menu_count()
    {
        $nip_lama = Auth::user()->nip_lama;
        Cache::flexible('cuti_'.$nip_lama, [2*60*60, 6*60*60], function () use ($nip_lama) {
            $client = new Client();
            $response = $client->get('https://sipecut.bps.go.id/api/v1/cuti_rwusul/bynip/id/'.$nip_lama);

            if ($response->getStatusCode() == 200) {
                $return_data = [];
                $data = json_decode($response->getBody(), true);
                foreach($data['data'] as $item){
                    array_push($return_data, [
                        'p_niplama' => $item['p_niplama'],
                        'p_nmcuti' => $item['p_nmcuti'],
                        'p_jmlcuti' => $item['p_jmlcuti'],
                        'p_tglmulai' => $item['p_tglmulai'],
                        'p_tglselesai' => $item['p_tglselesai'],
                        'p_statuslabel' => $item['p_statuslabel']
                    ]);
                }
                return $return_data;
            }
        });

        $satuan_kerja_tahun_kode = [];
        $peran_satuan_kerja = Peran::get_my_peran_satuan_kerja_kerja_tahun();

        foreach($peran_satuan_kerja as $item){
            if(!in_array($item->parent_id, $satuan_kerja_tahun_kode)){
                array_push($satuan_kerja_tahun_kode, $item->parent_id);
            }
        }

        $peran = Peran::get_my_peran();

        $count_appreciate = 0;
        $count_jadwal = 0;
        $count_pinjam_ruang = 0;
        $count_pinjam_zoom = 0;
        $count_pinjam_mobil = 0;

        if($peran->contains('peran', 'appreciate')){
            $satuan_kerja_tahun_kode = [];
            $peran_satuan_kerja = Peran::get_my_peran_satuan_kerja_kerja_tahun();

            foreach($peran_satuan_kerja as $item){
                if(!in_array($item->parent_id, $satuan_kerja_tahun_kode)){
                    array_push($satuan_kerja_tahun_kode, $item->parent_id);
                }
            }

            $master_id = [];
            $master = Appreciate_Master::where('isi_awal', "<=", Carbon::today()->format('Y-m-d'))
                    ->where('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)
                    ->orderBy('isi_awal', 'desc')
                    ->limit(1)
                    ->get();
            foreach($master as $item){
                array_push($master_id, $item->id);
            }
            $count_appreciate = Appreciate_Data::whereIn('master_id', $master_id)->where('status', '0')->count();
        }
        if($peran->contains('peran', 'ruang') || $peran->contains('peran', 'zoom')){
            $count_jadwal = Jadwal::where('status', 'menunggu')->count();
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
