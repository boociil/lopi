<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Auth_Super_Admin_Buku
{
    /**
     * Hanya super admin buku yang boleh lewat.
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->role_buku === 'super_admin') {
            return $next($request);
        }

        return abort(403, 'Forbidden.');
    }
}
