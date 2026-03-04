<?php

use App\Http\Controllers\Admin\Dashboard_Controller;
use App\Http\Controllers\Admin\Lopi_Controller;
use App\Http\Controllers\Admin\Satuan_Kerja_Controller;
use App\Http\Controllers\Admin\Satuan_Kerja_Tahun_Controller;
use App\Http\Controllers\Admin\Tim_Controller;
use App\Http\Controllers\Admin\User_Controller;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\SSO_Controller;
use App\Http\Controllers\Lopi\Appreciate_Admin_Controller;
use App\Http\Controllers\Umum\Beranda_Controller;
use App\Http\Controllers\Lopi\Appreciate_Controller;
use App\Http\Controllers\Lopi\Jadwal_Controller;
use App\Http\Controllers\Lopi\Kesehatan_Controller;
use App\Http\Controllers\Lopi\Notif_Controller;
use App\Http\Controllers\Lopi\Pinjam_Mobil_Controller;
use App\Http\Controllers\Lopi\Pinjam_Zoom_Controller;
use App\Http\Controllers\WebController;
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

Route::get('/maintenance', function(){
    return view("maintenance");
})->middleware('auth');


Route::get('/', [Beranda_Controller::class, 'redirect_func'])->middleware('auth');

Route::group([
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
    'prefix' => 'admin'
], function ($router) {
    Route::group(
    [
        'prefix' => 'pengguna',
        'middleware' => ['auth_admin:global'],
    ], function () {
        Route::get('/', [User_Controller::class, 'daftar'])->name('admin-pengguna-index');
        Route::post('/', [User_Controller::class, 'daftar'])->name('admin-pengguna-index');
        // Route::post('tambah', 'Admin\User_Controller@tambah');

        Route::post('format/unduh', [User_Controller::class, 'format_unduh'])->name('admin-pengguna-format-unduh');
        Route::post('format/unggah', [User_Controller::class, 'format_unggah'])->name('admin-pengguna-format-unggah');
        Route::post('{username}/hapus', [User_Controller::class, 'hapus'])->name('admin-pengguna-hapus-unggah');

    });

    Route::group(
    [
        'prefix' => 'satuan_kerja',
        'middleware' => ['auth_admin:global'],
    ], function () {
        Route::post('daftar', [Satuan_Kerja_Controller::class, 'daftar']);
        Route::post('tambah', [Satuan_Kerja_Controller::class, 'tambah']);
        Route::post('{satuan_kerja_id}/lihat', [Satuan_Kerja_Controller::class, 'lihat']);
        Route::post('{satuan_kerja_id}/ubah', [Satuan_Kerja_Controller::class, 'ubah']);
    });

    Route::group(
    [
        'prefix' => 'satuan_kerja_tahun',
    ], function () {
        Route::post('daftar', [Satuan_Kerja_Tahun_Controller::class, 'daftar'])->middleware('auth_admin:global|satuan_kerja_tahun');
        Route::post('tambah', [Satuan_Kerja_Tahun_Controller::class, 'tambah'])->middleware('auth_admin:global');
        Route::post('{satuan_kerja_tahun_id}/ubah', [Satuan_Kerja_Tahun_Controller::class, 'ubah'])->middleware('auth_admin:global');
        Route::post('{satuan_kerja_tahun_id}/hapus', [Satuan_Kerja_Tahun_Controller::class, 'hapus'])->middleware('auth_admin:global');
        Route::post('{satuan_kerja_tahun_id}/lihat', [Satuan_Kerja_Tahun_Controller::class, 'lihat'])->middleware('auth_admin:global|satuan_kerja_tahun');

        Route::post('{satuan_kerja_tahun_id}/tim/daftar', [Satuan_Kerja_Tahun_Controller::class, 'tim_daftar'])->middleware('auth_admin:global|satuan_kerja_tahun');

        Route::group(
        [
            'prefix' => '{satuan_kerja_tahun_id}/peran',
            'middleware' => ['auth_admin:global|satuan_kerja_tahun'],
        ], function () {
            Route::post('daftar', [Satuan_Kerja_Tahun_Controller::class, 'peran_daftar']);
            Route::post('format/unduh', [Satuan_Kerja_Tahun_Controller::class, 'peran_format_unduh']);
            Route::post('format/unggah', [Satuan_Kerja_Tahun_Controller::class, 'peran_format_unggah']);
            Route::post('{peran_id}/hapus', [Satuan_Kerja_Tahun_Controller::class, 'peran_hapus']);
        });
    });

    Route::group(
    [
        'prefix' => 'tim',
    ], function () {
        Route::post('daftar', [Tim_Controller::class, 'daftar'])->middleware('auth_admin:global|satuan_kerja_tahun|tim');
        Route::post('tambah', [Tim_Controller::class, 'tambah'])->middleware('auth_admin:global|satuan_kerja_tahun');
        Route::post('{tim_id}/lihat', [Tim_Controller::class, 'lihat'])->middleware('auth_admin:global|satuan_kerja_tahun|tim');
        Route::post('{tim_id}/ubah', [Tim_Controller::class, 'ubah'])->middleware('auth_admin:global|satuan_kerja_tahun');
        Route::post('{tim_id}/hapus', [Tim_Controller::class, 'hapus'])->middleware('auth_admin:global|satuan_kerja_tahun');

        Route::group(
        [
            'prefix' => '{tim_id}/peran',
            'middleware' => ['auth_admin:global|satuan_kerja_tahun|tim'],
        ], function () {
            Route::post('daftar', [Tim_Controller::class, 'peran_daftar']);
            Route::post('format/unduh', [Tim_Controller::class, 'peran_format_unduh']);
            Route::post('format/unggah', [Tim_Controller::class, 'peran_format_unggah']);
            Route::post('{peran_id}/hapus', [Tim_Controller::class, 'peran_hapus']);
        });
    });

});

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
    'prefix' => 'appreciate',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Appreciate_Controller::class, 'index'])->name('appreciation-index');
    Route::post('/tambah', [Appreciate_Controller::class, 'tambah'])->name('appreciation-tambah');
    // Route::post('/{appreciation}/ubah', [Appreciate_Controller::class, 'ubah'])->name('appreciation-ubah');
    // Route::post('/{appreciation}/hapus', [Appreciate_Controller::class, 'hapus'])->name('appreciation-hapus');

    Route::get('/buka', [Appreciate_Controller::class, 'buka'])->name('appreciation-buka');
    Route::get('/baca', [Appreciate_Admin_Controller::class, 'baca'])->name('appreciation-buka');

    Route::group(
    [
        'prefix' => 'master',
    ], function () {
        Route::get('/', [Appreciate_Admin_Controller::class, 'index'])->name('appreciation-master-index');
        Route::post('/tambah', [Appreciate_Admin_Controller::class, 'tambah'])->name('appreciation-master-tambah');
        Route::post('/{master}/ubah', [Appreciate_Admin_Controller::class, 'ubah'])->name('appreciation-master-ubah');
        Route::post('/{master}/hapus', [Appreciate_Admin_Controller::class, 'hapus'])->name('appreciation-master-hapus');
    });

    Route::group(
    [
        'prefix' => 'aproval',
    ], function () {
        Route::get('/', [Appreciate_Admin_Controller::class, 'aproval_index'])->name('appreciation-aproval-index');
        Route::post('/{appreciation}/approval', [Appreciate_Admin_Controller::class, 'aproval_data'])->name('appreciation-aproval-ubah');
    });

});

Route::group(
[
    'prefix' => 'jadwal',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Jadwal_Controller::class, 'index'])->name('jadwal-index');
    Route::post('/', [Jadwal_Controller::class, 'index'])->name('jadwal-index');
    Route::post('/tambah', [Jadwal_Controller::class, 'tambah'])->name('jadwal-tambah');
    Route::post('/{jadwal}/ubah', [Jadwal_Controller::class, 'ubah'])->name('jadwal-ubah');
    Route::post('/{jadwal}/hapus', [Jadwal_Controller::class, 'hapus'])->name('jadwal-hapus');
    Route::post('/{jadwal}/approval/ruang', [Jadwal_Controller::class, 'approval_ruang'])->name('jadwal-approval-ruang');
    Route::post('/{jadwal}/approval/zoom', [Jadwal_Controller::class, 'approval_zoom'])->name('jadwal-approval-zoom');
});

Route::group(
[
    'prefix' => 'kesehatan',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {
    Route::get('/', [Kesehatan_Controller::class, 'index'])->name('kesehatan-index');
    Route::post('/tambah', [Kesehatan_Controller::class, 'tambah'])->name('kesehatan-tambah');
    Route::post('/{kesehatan}/hapus', [Kesehatan_Controller::class, 'hapus'])->name('kesehatan-hapus');
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
        Route::post('/tambah', [Pinjam_Mobil_Controller::class, 'tambah'])->name('pinjam-mobil-tambah');
        Route::post('/{pinjamMobil}/ubah', [Pinjam_Mobil_Controller::class, 'ubah'])->name('pinjam-mobil-ubah');
        Route::post('/{pinjamMobil}/hapus', [Pinjam_Mobil_Controller::class, 'hapus'])->name('pinjam-mobil-hapus');
        Route::post('/{pinjamMobil}/approval', [Pinjam_Mobil_Controller::class, 'approval'])->name('pinjam-mobil-approval');
    });

    Route::group(
    [
        'prefix' => 'zoom',
    ], function () {
        Route::get('/', [Pinjam_Zoom_Controller::class, 'index'])->name('pinjam-zoom-index');
        Route::post('/', [Pinjam_Zoom_Controller::class, 'index'])->name('pinjam-zoom-index');
        Route::post('/tambah', [Pinjam_Zoom_Controller::class, 'tambah'])->name('pinjam-zoom-tambah');
        Route::post('/{pinjamZoom}/ubah', [Pinjam_Zoom_Controller::class, 'ubah'])->name('pinjam-zoom-ubah');
        Route::post('/{pinjamZoom}/hapus', [Pinjam_Zoom_Controller::class, 'hapus'])->name('pinjam-zoom-hapus');
        Route::post('/{pinjamZoom}/approval', [Pinjam_Zoom_Controller::class, 'approval'])->name('pinjam-zoom-approval');
    });
});




