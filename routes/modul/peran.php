<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Lopi_Controller;

Route::group(
[
    'prefix' => 'peran',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'auth_admin:global'],
], function () {
    Route::get('/', [Lopi_Controller::class, 'index'])->name('peran-index');
    Route::post('/tambah', [Lopi_Controller::class, 'tambah'])->name('peran-tambah');
    Route::post('/{peran_id}/hapus', [Lopi_Controller::class, 'hapus'])->name('peran-hapus');
});
