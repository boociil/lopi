<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Umum\Peran;
use App\Model\Umum\User;
use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class Cek_Cuti_Controller extends Controller
{

    public function index(Request $request)
    {
        if($request->isMethod('get')){
             return Inertia::render('cek_cuti', [ "user" => User::all()]);
        }else if($request->isMethod('post')){
            return [ 'cuti_daftar' => $this->get_data_cuti_satuan_kerja()];
        }
    }

    private function get_data_cuti_satuan_kerja()
    {
        $satuan_kerja_tahun_kode = [];
        $peran_satuan_kerja = Peran::get_my_peran_satuan_kerja_kerja_tahun();

        foreach($peran_satuan_kerja as $item){
            if(!in_array($item->parent_id, $satuan_kerja_tahun_kode)){
                array_push($satuan_kerja_tahun_kode, $item->parent_id);
            }
        }

        $all_user = Peran::with(['user'])->select('pemilik_username')
            ->whereIn('parent_id', $satuan_kerja_tahun_kode)
            ->distinct()
            ->get();


        $return_data = [];

        foreach($all_user as $item_user){
            // Cache::flexible('users', [5, 10], function () {
            //     return DB::table('users')->get();
            // });
            $item_data = $this->get_data_cuti_pegawai($item_user->user->nip_lama);
            $return_data = array_merge($return_data, $item_data);
        }

        return $return_data;
    }

    private function get_data_cuti_pegawai($nip_lama, $try=0)
    {
        try {
            return Cache::flexible('cuti_'.$nip_lama, [2*60*60, 6*60*60], function () use ($nip_lama) {
                $client = new Client();
                $response = $client->get('https://sipecut.bps.go.id/api/v1/cuti_rwusul/bynip/id/'.$nip_lama.'/thn/2026');

                if ($response->getStatusCode() == 200) {
                    $return_data = [];
                    $data = json_decode($response->getBody(), true);
                    foreach($data['data'] as $item){
                        array_push($return_data, [
                            'p_id' => $item['p_id'],
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
        } catch (Exception $e) {
            if($try <= 1){
                return $this->get_data_cuti_pegawai($nip_lama, $try + 1);
            }else{
                return [];
            }
        }
    }


}
