<?php

use App\Http\Controllers\Admin\Lopi_Controller;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Umum\Beranda_Controller;
use App\Http\Controllers\Lopi\Jadwal_Controller;
use App\Http\Controllers\Lopi\Pinjam_Mobil_Controller;
use App\Http\Controllers\Lopi\Pinjam_Ruang_Controller;
use App\Http\Controllers\Lopi\Pinjam_Zoom_Controller;
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


Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::get('/logout', [LoginController::class, 'logout'])->middleware('guest')->name('logout');

// Route::get('/login_sso', [SSO_Controller::class, 'showLoginForm'])->name('login');
// Route::get('/sso', [SSO_Controller::class, 'sso'])->name('sso');
// Route::get('/logout', [SSO_Controller::class, 'logout'])->name('logout');

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

Route::get('/tampilan', function () {
    return Inertia::render('tampilan');
})->name('tampilan')->middleware(['auth', 'check_maintenance', 'statistik']);

//beranda
Route::group(
[
    'prefix' => 'beranda',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Beranda_Controller::class, 'beranda']);
});

Route::group(
[
    'prefix' => 'peran',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Lopi_Controller::class, 'index'])->name('peran-index');
    Route::post('/tambah', [Lopi_Controller::class, 'tambah'])->name('peran-tambah');
    Route::post('/{peran_id}/hapus', [Lopi_Controller::class, 'hapus'])->name('peran-hapus');
});

Route::group(
[
    'prefix' => 'jadwal',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Jadwal_Controller::class, 'index'])->name('jadwal-index');
    Route::get('/tambah', [Jadwal_Controller::class, 'index'])->name('jadwal-tambah');
    Route::get('/{jadwal_id}/ubah', [Jadwal_Controller::class, 'index'])->name('jadwal-ubah');
    Route::get('/{jadwal_id}/hapus', [Jadwal_Controller::class, 'index'])->name('jadwal-hapus');
});



Route::group(
[
    'prefix' => 'pinjam',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::group(
    [
        'prefix' => 'mobil',
    ], function () {
        Route::get('/', [Pinjam_Mobil_Controller::class, 'index'])->name('pinjam-mobil-index');
        Route::post('/', [Pinjam_Mobil_Controller::class, 'index'])->name('pinjam-mobil-index');
        Route::get('/tambah', [Pinjam_Mobil_Controller::class, 'tambah'])->name('pinjam-mobil-tambah');
        Route::get('/{pinjamMobil}/ubah', [Pinjam_Mobil_Controller::class, 'ubah'])->name('pinjam-mobil-ubah');
        Route::get('/{pinjamMobil}/hapus', [Pinjam_Mobil_Controller::class, 'hapus'])->name('pinjam-mobil-hapus');
        Route::get('/{pinjamMobil}/approval', [Pinjam_Mobil_Controller::class, 'approval'])->name('pinjam-mobil-approval');
    });

    Route::group(
    [
        'prefix' => 'zoom',
    ], function () {
        Route::get('/', [Pinjam_Zoom_Controller::class, 'index'])->name('pinjam-zoom-index');
        Route::post('/', [Pinjam_Zoom_Controller::class, 'index'])->name('pinjam-zoom-index');
        Route::get('/tambah', [Pinjam_Zoom_Controller::class, 'tambah'])->name('pinjam-zoom-tambah');
        Route::get('/{zoom_id}/ubah', [Pinjam_Zoom_Controller::class, 'ubah'])->name('pinjam-zoom-ubah');
        Route::get('/{zoom_id}/hapus', [Pinjam_Zoom_Controller::class, 'hapus'])->name('pinjam-zoom-hapus');
        Route::get('/{zoom_id}/approval', [Pinjam_Zoom_Controller::class, 'approval'])->name('pinjam-zoom-approval');
    });

    Route::group(
    [
        'prefix' => 'ruang',
    ], function () {
        Route::get('/', [Pinjam_Ruang_Controller::class, 'index'])->name('pinjam-ruang-index');
        Route::post('/', [Pinjam_Ruang_Controller::class, 'index'])->name('pinjam-ruang-index');
        Route::get('/{ruang_id}/approval', [Pinjam_Ruang_Controller::class, 'approval'])->name('pinjam-ruang-approval');
    });

    Route::group(
    [
        'prefix' => 'peran',
        'middleware' => ['auth', 'check_maintenance', 'statistik'],
    ], function () {
        Route::get('/', [Lopi_Controller::class, 'index'])->name('peran-index');
        Route::post('/tambah', [Lopi_Controller::class, 'tambah'])->name('peran-tambah');
        Route::post('/{peran_id}/hapus', [Lopi_Controller::class, 'hapus'])->name('peran-hapus');
    });
});




