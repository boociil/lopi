<?php

namespace App\Http\Middleware;

use App\Model\Jurnal_Harian\Jurnal_Harian_Proyek;
use Closure;
use App\Model\Umum\Peran;
use App\Model\Umum\Tim;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Auth_Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $level)
    {
        // level : global, satuan_kerja_tahun, fungsi, tim, proyek
        $level_array = explode("|", $level);

        $ada_satuan_kerja_tahun_id = !is_null($request->route('satuan_kerja_tahun_id'));
        $ada_tim_id = !is_null($request->route('tim_id'));
        $ada_jurnal_harian_proyek_id = !is_null($request->route('jurnal_harian_proyek_id'));

        $satuan_kerja_tahun_id = [];
        $tim_id = [];
        $jurnal_harian_proyek_id = [];

        if($ada_jurnal_harian_proyek_id){
            if($ada_satuan_kerja_tahun_id && $ada_tim_id){
                $satuan_kerja_tahun_id = [$request->route('satuan_kerja_tahun_id')];
                $tim_id = [$request->route('tim_id')];
                $jurnal_harian_proyek_id = [$request->route('jurnal_harian_proyek_id')];
            }else{
                $jurnal_proyek = Jurnal_Harian_Proyek::where('id', $request->route('jurnal_harian_proyek_id'))->first();
                if($jurnal_proyek){
                    $satuan_kerja_tahun_id = [$jurnal_proyek->satuan_kerja_tahun_id];
                    $tim_id = [$jurnal_proyek->tim_id];
                }
            }
        }else if($ada_tim_id){
            if($ada_satuan_kerja_tahun_id){
                $satuan_kerja_tahun_id = [$request->route('satuan_kerja_tahun_id')];
                $tim_id = [$request->route('tim_id')];
            }else{
                $tim = Tim::where('id', $request->route('tim_id'))->first();
                if($tim){
                    $satuan_kerja_tahun_id = [$tim->satuan_kerja_tahun_id];
                }
            }
        }else if($ada_satuan_kerja_tahun_id){
            $satuan_kerja_tahun_id = [$request->route('satuan_kerja_tahun_id')];
        }

        $query_admin = Peran::query();
        $query_admin->where('pemilik_username', Auth::user()->username);
        $query_admin->where(function($query_0) use ($level_array, $satuan_kerja_tahun_id, $tim_id, $jurnal_harian_proyek_id){
            foreach($level_array as $item_level){
                if($item_level == 'global'){
                    $query_0->orWhere(function($query_1){
                        $query_1->where('tipe_peran', 'global');
                        $query_1->where('peran', 'admin');
                        $query_1->where('tahun', null);
                    });
                }else if (in_array($item_level, ['satuan_kerja_tahun', 'tim', 'proyek'])){
                    $query_0->orWhere(function($query_1) use ($item_level, $satuan_kerja_tahun_id, $tim_id, $jurnal_harian_proyek_id){
                        $query_1->where('tipe_peran', $item_level);
                        $query_1->where('peran', 'admin');
                        $query_1->where('tahun', session('tahun_active'));

                        if($item_level == 'satuan_kerja_tahun' && count($satuan_kerja_tahun_id) > 0){
                            $query_1->whereIn('parent_id', $satuan_kerja_tahun_id);
                        }else if($item_level == 'tim' && count($tim_id) > 0){
                            $query_1->whereIn('parent_id', $tim_id);
                        }else if($item_level == 'proyek' && count($jurnal_harian_proyek_id) > 0){
                            $query_1->whereIn('parent_id', $jurnal_harian_proyek_id);
                        }
                    });
                }
            }
        });

        if($query_admin->count() > 0){
            return $next($request);
        }else{
            return abort(403, 'Forbidden.');
        }
    }
}
