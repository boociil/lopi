<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\Timeline_Controller;

Route::group(
[
    'prefix' => 'timeline',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
], function () {
    Route::get('/', [Timeline_Controller::class, 'index'])->name('rumah_link-index');
    Route::post('/tambah', [Timeline_Controller::class, 'tambah'])->name('rumah_link-tambah');
    Route::post('/{timeline}/ubah', [Timeline_Controller::class, 'ubah'])->name('rumah_link-ubah');
    Route::post('/{timeline}/hapus', [Timeline_Controller::class, 'hapus'])->name('rumah_link-hapus');

    Route::post('/setting', [Timeline_Controller::class, 'setting'])->name('rumah_link-setting');
});
