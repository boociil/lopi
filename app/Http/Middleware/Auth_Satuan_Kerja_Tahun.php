<?php

namespace App\Http\Middleware;

use Closure;
use App\Model\Umum\Peran;
use Illuminate\Support\Facades\Auth;

class Auth_Satuan_Kerja_Tahun
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $role_string_array)
    {
        $role_array = explode(",", $role_string_array);
        $satuan_kerja_tahun_id = $request->route('satuan_kerja_tahun_id');
        if(Peran::check_user_have_role('satuan_kerja_tahun', $satuan_kerja_tahun_id, Auth::user()->username, $role_array)){
            return $next($request);
        }else{
            return abort(403, 'Forbidden.');
        }
    }
}
