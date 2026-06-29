<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\ITValet_Controller;

Route::group(
[
    'prefix' => 'it-valet',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
], function () {
    Route::get('/', [ITValet_Controller::class, 'index'])->name('itvalet-index');
    Route::post('/', [ITValet_Controller::class, 'index'])->name('itvalet-index');
    Route::post('/tambah', [ITValet_Controller::class, 'tambah'])->name('itvalet-tambah');
    Route::post('/{monica}/ubah', [ITValet_Controller::class, 'ubah'])->name('itvalet-ubah');
    Route::post('/{monica}/hapus', [ITValet_Controller::class, 'hapus'])->name('itvalet-hapus');
});
