<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\Jadwal_Controller;
use App\Http\Controllers\Lopi\Rumah_Link_Controller;


Route::group(
[
    'prefix' => 'jadwal',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
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
    'prefix' => 'petugas',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
], function () {
    Route::get('/', [Rumah_Link_Controller::class, 'index'])->name('rumah_link-index');
    Route::post('/tambah', [Rumah_Link_Controller::class, 'tambah'])->name('rumah_link-tambah');
    Route::post('/{petugas}/ubah', [Rumah_Link_Controller::class, 'ubah'])->name('rumah_link-ubah');
    Route::post('/{petugas}/hapus', [Rumah_Link_Controller::class, 'hapus'])->name('rumah_link-hapus');
});
