<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Umum\Profil_Controller;
use App\Http\Controllers\Umum\Beranda_Controller;

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
