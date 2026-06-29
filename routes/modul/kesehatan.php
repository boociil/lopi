<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\Kesehatan_Controller;

Route::group(
[
    'prefix' => 'kesehatan',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
], function () {
    Route::get('/', [Kesehatan_Controller::class, 'index'])->name('kesehatan-index');
    Route::post('/tambah', [Kesehatan_Controller::class, 'tambah'])->name('kesehatan-tambah');
    Route::post('/{kesehatan}/hapus', [Kesehatan_Controller::class, 'hapus'])->name('kesehatan-hapus');
});
