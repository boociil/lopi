<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\ITValet_Controller;

Route::group(
[
    'prefix' => 'it-valet',
    'middleware' => ['auth', 'check_maintenance', 'statistik', 'check_satuan_kerja:7600'],
], function () {
     Route::prefix('/master/')->group(function () {
        Route::get('aset-berwujud', TangibleList::class)->name('master.tangible.list');
        Route::get('aset-berwujud/baru', TangibleCreate::class)->name('master.tangible.create');
        Route::get('aset-berwujud/{masterId}', TangibleEdit::class)->name('master.tangible.edit');

        Route::get('aset-tidak-berwujud', IntangibleList::class)->name('master.intangible.list');
        Route::get('aset-tidak-berwujud/baru', IntangibleCreate::class)->name('master.intangible.create');
        Route::get('aset-tidak-berwujud/{masterId}', IntangibleEdit::class)->name('master.intangible.edit');

        Route::get('ruangan', RoomList::class)->name('master.room.list');
    });

    Route::prefix('/aktivitas/')->group(function () {
        Route::get('alokasi/{masterId?}', AllocationList::class)->name('activity.allocation.list');
        Route::get('alokasi/detail/{assetId}', ItemShow::class)->name('activity.allocation.item-show');

        Route::get('pemeliharaan/{itemListId?}', MaintenanceList::class)->name('activity.maintenance.list');
    });
});



