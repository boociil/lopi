<?php

namespace App\Http\Middleware;

use Closure;
use App\Model\Umum\Statistik as UmumStatistik;

class Statistik
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
        UmumStatistik::helper_tambah($request);
        return $next($request);
    }
}
