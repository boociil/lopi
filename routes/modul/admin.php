<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Dashboard_Controller;
use App\Http\Controllers\Admin\Lopi_Controller;
use App\Http\Controllers\Admin\Satuan_Kerja_Controller;
use App\Http\Controllers\Admin\Satuan_Kerja_Tahun_Controller;
use App\Http\Controllers\Admin\Tim_Controller;
use App\Http\Controllers\Admin\User_Controller;

Route::group([
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'auth_admin:global'],
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
