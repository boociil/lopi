<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\Cek_Cuti_Controller;

Route::group(
[
    'prefix' => 'cek_cuti',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
], function () {
    Route::get('/', [Cek_Cuti_Controller::class, 'index'])->name('cek-cuti-index');
    Route::post('/', [Cek_Cuti_Controller::class, 'index'])->name('cek-cuti-index');
});
