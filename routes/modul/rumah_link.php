<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\Rumah_Link_Controller;

Route::group(
[
    'prefix' => 'rumah_link',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
], function () {
    Route::get('/', [Rumah_Link_Controller::class, 'index'])->name('rumah_link-index');
    Route::post('/tambah', [Rumah_Link_Controller::class, 'tambah'])->name('rumah_link-tambah');
    Route::post('/{rumah_link}/ubah', [Rumah_Link_Controller::class, 'ubah'])->name('rumah_link-ubah');
    Route::post('/{rumah_link}/hapus', [Rumah_Link_Controller::class, 'hapus'])->name('rumah_link-hapus');

    Route::post('/setting', [Rumah_Link_Controller::class, 'setting'])->name('rumah_link-setting');
});
