<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\KMS_Controller;

Route::group(
[
    'prefix' => 'kms',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
], function () {
    Route::get('/', [KMS_Controller::class, 'index'])->name('kms-index');
    Route::post('/', [KMS_Controller::class, 'index'])->name('kms-index');
    Route::post('/tambah', [KMS_Controller::class, 'tambah'])->name('kms-tambah');
    Route::post('/{kms}/ubah', [KMS_Controller::class, 'ubah'])->name('kms-ubah');
    Route::post('/{kms}/hapus', [KMS_Controller::class, 'hapus'])->name('kms-hapus');
});
