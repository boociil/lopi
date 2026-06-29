<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\Appreciate_Controller;
use App\Http\Controllers\Lopi\Appreciate_Admin_Controller;

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

    Route::get('/rekap', [Appreciate_Admin_Controller::class, 'rekap'])->name('appreciation-buka');

});
