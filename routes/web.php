<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SSO_Controller;
use App\Http\Controllers\Umum\Beranda_Controller;
use App\Http\Controllers\Lopi\Notif_Controller;

use App\Http\Controllers\WebController;
use App\Model\Umum\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
// Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
// Route::get('/logout', [LoginController::class, 'logout'])->middleware('guest')->name('logout');

Route::get('/login', [SSO_Controller::class, 'showLoginForm'])->name('login');
Route::get('/sso', [SSO_Controller::class, 'sso'])->name('sso');
Route::get('/logout', [SSO_Controller::class, 'logout'])->name('logout');

// Route::get('/login_dev', function(){
//     Auth::logout();
//     Auth::login(User::where('username', 'anisa.rahma')->first(),true);
//     session()->put('tahun_active', env("TAHUN_DEFAULT"));
//     return redirect('/');
// });

Route::get('/maintenance', function(){
    return view("maintenance");
})->middleware('auth');


Route::get('/', [Beranda_Controller::class, 'redirect_func'])->middleware('auth');

Route::get('tahun/{tahun}/ubah', function ($tahun) {
    $tahun_array = explode(",", env("TAHUN_ARRAY"));
    if(in_array($tahun, $tahun_array)){
        session()->put('tahun_active', $tahun);
    }
    redirect()->route('zoom-index');
})->middleware('auth');

Route::group(
[
    'prefix' => 'notif',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::post('/count', [Notif_Controller::class, 'get_menu_count'])->name('notif-count');
});

//beranda
Route::group(
[
    'prefix' => 'beranda',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Beranda_Controller::class, 'beranda']);
});

require __DIR__.'/modul/admin.php';
// require __DIR__.'/modul/rumah_link.php';
require __DIR__.'/modul/appreciate.php';
require __DIR__.'/modul/cek_cuti.php';
// require __DIR__.'/modul/it-valet.php';
require __DIR__.'/modul/jadwal.php';
require __DIR__.'/modul/kesehatan.php';
require __DIR__.'/modul/kms.php';
require __DIR__.'/modul/peran.php';
require __DIR__.'/modul/pinjam.php';
// require __DIR__.'/modul/timeline.php';
