<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\Pinjam_Mobil_Controller;
use App\Http\Controllers\Lopi\Pinjam_Zoom_Controller;

Route::group(
[
    'prefix' => 'pinjam',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
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
        Route::post('/get_mmeting', [Pinjam_Zoom_Controller::class, 'get_mmeting'])->name('pinjam-zoom-get-mmeting');
    });

    Route::group(
    [
        'prefix' => 'non_bmn',
    ], function () {
        Route::get('/', [Pinjam_Zoom_Controller::class, 'index'])->name('non-bmn-index');
        Route::post('/', [Pinjam_Zoom_Controller::class, 'index'])->name('non-bmn-index');
        Route::post('/tambah', [Pinjam_Zoom_Controller::class, 'tambah'])->name('non-bmn-tambah');
        Route::post('/{nonBmn}/ubah', [Pinjam_Zoom_Controller::class, 'ubah'])->name('non-bmn-ubah');
        Route::post('/{nonBmn}/hapus', [Pinjam_Zoom_Controller::class, 'hapus'])->name('non-bmn-hapus');
        Route::post('/{nonBmn}/approval', [Pinjam_Zoom_Controller::class, 'approval'])->name('non-bmn-approval');
    });
});
