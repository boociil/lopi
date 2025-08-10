<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Umum\Profil_Controller;
use App\Http\Controllers\Umum\Beranda_Controller;
use App\Http\Controllers\Jadwal\Jadwal_Controller;
use App\Http\Controllers\Jadwal\Ruang_Controller;
use App\Http\Controllers\Jadwal\Zoom_Controller;

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
    Route::get('/', [Jadwal_Controller::class, 'index']);
    Route::get('/tambah', [Jadwal_Controller::class, 'tambah']);
    Route::get('/{jadwal_id}/ubah', [Jadwal_Controller::class, 'ubah']);
    Route::get('/{jadwal_id}/ubah', [Jadwal_Controller::class, 'ubah']);
    Route::get('/{jadwal_id}/hapus', [Jadwal_Controller::class, 'hapus']);
});

Route::group(
[
    'prefix' => 'zoom',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Zoom_Controller::class, 'index']);
    Route::get('/tambah', [Zoom_Controller::class, 'tambah']);
    Route::get('/{jadwal_id}/ubah', [Zoom_Controller::class, 'ubah']);
    Route::get('/{jadwal_id}/hapus', [Zoom_Controller::class, 'hapus']);
});


Route::group(
[
    'prefix' => 'ruang',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Jadwal_Controller::class, 'index']);
    Route::get('/{ruang}/approve', [Jadwal_Controller::class, 'approve']);
    Route::get('/{ruang}/reject', [Jadwal_Controller::class, 'reject']);
});

