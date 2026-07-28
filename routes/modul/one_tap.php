<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Lopi\OneTap_Controller;


Route::group(
[
    'prefix' => 'onetap',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
], function () {
    Route::get('/', [OneTap_Controller::class, 'index'])
        ->name('onetap.index');

    Route::post('/', [OneTap_Controller::class, 'store'])
        ->name('onetap.store');

    Route::put('/{id}', [OneTap_Controller::class, 'update'])
        ->name('onetap.update');

    Route::delete('/{id}', [OneTap_Controller::class, 'destroy'])
        ->name('onetap.destroy');

    Route::patch('/trash/{id}/restore', [OneTap_Controller::class, 'restore'])
        ->name('onetap.restore');

    Route::delete('/trash/{id}/force-delete', [OneTap_Controller::class, 'forceDelete'])
        ->name('onetap.force-delete');
});


// Jangan pasang middleware auth pada route ini.
// Controller akan membiarkan public terbuka untuk semua orang,
// dan mengarahkan private ke login bila belum memiliki sesi.
Route::get('/ot/{slug}', [OneTap_Controller::class, 'publicShow'])
    ->name('onetap.public');

