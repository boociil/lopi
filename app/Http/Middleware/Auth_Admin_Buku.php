<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Auth_Admin_Buku
{
    /**
     * Hanya admin buku & super admin buku yang boleh lewat.
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if ($user && in_array($user->role_buku, ['admin_buku', 'super_admin'], true)) {
            return $next($request);
        }

        return abort(403, 'Forbidden.');
    }
}
