<?php

namespace App\Http\Middleware;

use App\Model\Umum\Peran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Closure;

class Redirect_Guard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $satuan_kerja_tahun_type)
    {
        $is_admin_count = Peran::where(function($query){
                $query->where('tahun', session('tahun_active'))
                    ->orWhere('tahun', null);
                })
            ->where('tipe_peran', 'global')
            ->where('peran', 'admin')
            ->where('pemilik_username', Auth::user()->username)
            ->count();

        if( $is_admin_count ){
            return $next($request);
        }
        $count_peran_prov = DB::table('siakip2_peran')
            ->join('siakip2_satuan_kerja_tahun', 'siakip2_peran.parent_id', '=', 'siakip2_satuan_kerja_tahun.id')
            ->where('siakip2_satuan_kerja_tahun.type', $satuan_kerja_tahun_type)
            ->where('siakip2_peran.tahun', session('tahun_active'))
            ->where('siakip2_peran.pemilik_username', Auth::user()->username)
            ->count();
        if( $count_peran_prov ){
            return $next($request);
        }else{
            return redirect('/');
        }
    }
}
