<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Umum\Beranda_Controller;
use App\Http\Controllers\Jadwal\Jadwal_Controller;
use App\Http\Controllers\Jadwal\Ruang_Controller;
use App\Http\Controllers\Jadwal\Zoom_Controller;
use App\Model\Umum\Peran;
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
Route::post('/login', [LoginController::class, 'login']);
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

// Route::get('/login_sso', [SSO_Controller::class, 'showLoginForm'])->name('login');
// Route::get('/sso', [SSO_Controller::class, 'sso'])->name('sso');
// Route::get('/logout', [SSO_Controller::class, 'logout'])->name('logout');

Route::get('/maintenance', function(){
    return view("maintenance");
})->middleware('auth');


Route::get('/', [Beranda_Controller::class, 'redirect_func'])->middleware('auth');
Route::get('/tampilan', function () {
    return Inertia::render('tampilan');
})->name('tampilan')->middleware('auth');

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
    'prefix' => 'jadwal',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Jadwal_Controller::class, 'jadwal-index']);
    Route::get('/tambah', [Jadwal_Controller::class, 'jadwal-tambah']);
    Route::get('/{jadwal_id}/ubah', [Jadwal_Controller::class, 'jadwal-ubah']);
    Route::get('/{jadwal_id}/hapus', [Jadwal_Controller::class, 'jadwal-hapus']);

});

 Route::group(
[
    'prefix' => 'ruang',
], function () {
    Route::get('/', [Ruang_Controller::class, 'index'])->name('ruang-index');
    Route::post('/tambah', [Ruang_Controller::class, 'tambah'])->name('ruang-tambah');
    Route::post('/{ruang_id}/ubah', [Ruang_Controller::class, 'ubah'])->name('ruang-ubah');
    Route::post('/{ruang_id}/hapus', [Ruang_Controller::class, 'hapus'])->name('ruang-hapus');
    Route::post('/{ruang_id}/approval', [Ruang_Controller::class, 'approval'])->name('ruang-approval');
});

Route::group(
[
    'prefix' => 'zoom',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Zoom_Controller::class, 'index'])->name('zoom-index');
    Route::post('/tambah', [Zoom_Controller::class, 'tambah'])->name('zoom-tambah');
    Route::post('/{jadwal_id}/ubah', [Zoom_Controller::class, 'ubah'])->name('zoom-ubah');
    Route::post('/{jadwal_id}/hapus', [Zoom_Controller::class, 'hapus'])->name('zoom-hapus');
    Route::post('/{jadwal_id}/approval', [Jadwal_Controller::class, 'approval'])->name('zoom-approval');
});

