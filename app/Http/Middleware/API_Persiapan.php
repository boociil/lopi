<?php

namespace App\Http\Middleware;

use Closure;


class API_Persiapan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $tahun_active = $request->header('tahun-active');
        session()->put('tahun_active', $tahun_active);

        $kode_auth = $request->header('authorization');

        $kode_kab = [];
        if($kode_auth == '26fab353-0c06-447e-9831-e8869fea9fcc'){
             $kode_kab = ['7601'];
        }

        session()->put('kode_kab', $kode_kab);

        return $next($request);
    }
}
