<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */

    public function handle($request, Closure $next, ...$guards)
    {
        if(($request->method() == "POST" || $request->method() == "GET")
            && !$request->session()->has('tahun_active')){
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        if(Auth::check()){
            if(Auth::user()->active != 1){
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        $this->authenticate($request, $guards);
        return $next($request);
    }

    protected function redirectTo(Request $request)
    {
        return $request->expectsJson() ? null : route('login');
    }
}
