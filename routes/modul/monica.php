<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\Monica_Controller;


Route::group(
[
    'prefix' => 'monica',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
], function () {
    Route::get('/', [Monica_Controller::class, 'index'])->name('monica-index');
    Route::post('/', [Monica_Controller::class, 'index'])->name('monica-index');
    Route::post('/tambah', [Monica_Controller::class, 'tambah'])->name('monica-tambah');
    Route::post('/{monica}/ubah', [Monica_Controller::class, 'ubah'])->name('monica-ubah');
    Route::post('/{monica}/hapus', [Monica_Controller::class, 'hapus'])->name('monica-hapus');
});
