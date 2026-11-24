<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\HandleLopiPrefix::class);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            \App\Http\Middleware\HandleAppearance::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,

        ]);
        //  $middleware->api(append: [
        //     \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        //     \Illuminate\Routing\Middleware\SubstituteBindings::class,
        //     'useapiguard',
        //     'api_persiapan',

        // ]);
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'useapiguard' => \App\Http\Middleware\UseApiGuard::class,
            'auth_admin' => \App\Http\Middleware\Auth_Admin::class,
            'statistik' => \App\Http\Middleware\Statistik::class,
            'auth_satuan_kerja_tahun' => \App\Http\Middleware\Auth_Satuan_Kerja_Tahun::class,
            'auth_tim' => \App\Http\Middleware\Auth_Satuan_Kerja_Tahun::class,
            'auth_user' => \App\Http\Middleware\Auth_User::class,
            'redirect_guard' => \App\Http\Middleware\Redirect_Guard::class,
            'check_maintenance' => \App\Http\Middleware\Check_Maintenance::class,
            'api_persiapan' => \App\Http\Middleware\API_Persiapan::class,
            'check_satuan_kerja' => \App\Http\Middleware\Check_Satuan_Kerja_Tahun::class,
            'auth_admin_buku' => \App\Http\Middleware\Auth_Admin_Buku::class,
            'auth_super_admin_buku' => \App\Http\Middleware\Auth_Super_Admin_Buku::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {


    })->create();
