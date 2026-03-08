<?php

namespace App\Http\Middleware;

use Closure;
use App\Model\Umum\Peran;
use Illuminate\Support\Facades\Auth;

use Illuminate\Database\Eloquent\Builder;

class Check_Satuan_Kerja_Tahun
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
        $kode_kab = explode("|", $level);

        $query = Peran::query()
            ->where('tipe_peran', 'satuan_kerja_tahun')
            ->where('tahun', session('tahun_active'))
            ->where('pemilik_username', Auth::user()->username)
            ->whereHas('parent_satuan_kerja_tahu', function (Builder $query) use ($kode_kab) {
                $query->whereIn('kode_satuan_kerja', $kode_kab);
            });

        if($query->count() > 0){
            return $next($request);
        }else{
            return abort(403, 'Forbidden.');
        }
    }
}
