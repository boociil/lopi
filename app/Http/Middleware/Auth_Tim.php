<?php

namespace App\Http\Middleware;

use Closure;
use App\Model\Umum\Peran;
use App\Model\Umum\Tim;
use Illuminate\Support\Facades\Auth;

class Auth_Tim
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
        $tim_id = $request->route('tim_id');

        $tim = Tim::where('id', $tim_id)->first();
        if(Peran::check_user_have_role('satuan_kerja_tahun', $tim->satuan_kerja_tahun_id, Auth::user()->username, ['admin', 'pimpinan'])
            || Peran::check_user_have_role('tim', $tim_id, Auth::user()->username, $role_array)){
            return $next($request);
        }else{
            return abort(403, 'Forbidden.');
        }
    }
}
