<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lopi\Buku_Controller;
use App\Http\Controllers\Lopi\Buku_Admin_Controller;
use App\Http\Controllers\Lopi\Buku_Manajemen_Admin_Controller;
use App\Http\Controllers\Lopi\Buku_Notif_Controller;

Route::group(
[
    'prefix' => 'buku',
    'middleware' => ['auth', 'check_maintenance', 'statistik'],
], function () {

    // Katalog untuk semua pegawai
    Route::get('/', [Buku_Controller::class, 'index'])->name('buku-list');

    // Badge sidebar Buku (ringan, terpisah dari /notif/count)
    Route::post('/notif-count', [Buku_Notif_Controller::class, 'count'])->name('buku-notif-count');

    Route::post('/{buku}/pinjam', [Buku_Controller::class, 'pinjam'])->name('buku-pinjam');
    Route::post('/pinjam/{pinjam}/batal', [Buku_Controller::class, 'batal'])->name('buku-batal');
    Route::get('/cover/{buku}', [Buku_Controller::class, 'cover'])->name('buku-cover');

    // Admin buku & super admin
    Route::group(['middleware' => ['auth_admin_buku']], function () {
        Route::get('/master', [Buku_Admin_Controller::class, 'master'])->name('buku-master');
        Route::post('/master/{pinjam}/setujui', [Buku_Admin_Controller::class, 'setujui'])->name('buku-setujui');
        Route::post('/master/{pinjam}/tolak', [Buku_Admin_Controller::class, 'tolak'])->name('buku-tolak');
        Route::post('/master/{pinjam}/kembali', [Buku_Admin_Controller::class, 'kembali'])->name('buku-kembali');

        Route::get('/manajemen', [Buku_Admin_Controller::class, 'manajemen'])->name('buku-manajemen');
        Route::post('/manajemen/tambah', [Buku_Admin_Controller::class, 'tambah'])->name('buku-tambah');
        Route::post('/manajemen/{buku}/ubah', [Buku_Admin_Controller::class, 'ubah'])->name('buku-ubah');
        Route::post('/manajemen/{buku}/hapus', [Buku_Admin_Controller::class, 'hapus'])->name('buku-hapus');

        Route::get('/admin', [Buku_Manajemen_Admin_Controller::class, 'index'])->name('buku-admin');
        Route::get('/admin/logs', [Buku_Manajemen_Admin_Controller::class, 'logs'])->name('buku-admin-logs');
        Route::post('/admin/logs/{id}/hapus', [Buku_Manajemen_Admin_Controller::class, 'hapus'])->name('buku-admin-logs-hapus');
    });

    // Khusus super admin
    Route::group(['middleware' => ['auth_super_admin_buku']], function () {
        Route::post('/admin/jadikan', [Buku_Manajemen_Admin_Controller::class, 'jadikan'])->name('buku-admin-jadikan');
        Route::post('/admin/jadikan-super', [Buku_Manajemen_Admin_Controller::class, 'jadikanSuperAdmin'])->name('buku-admin-jadikan-super');
        Route::post('/admin/cabut', [Buku_Manajemen_Admin_Controller::class, 'cabut'])->name('buku-admin-cabut');
        Route::post('/admin/turunkan-super-admin', [Buku_Manajemen_Admin_Controller::class, 'turunkanSuperAdmin'])->name('buku-admin-turunkan-super');
    });

    // Detail buku (diletakkan terakhir agar tidak menabrak rute admin di atas)
    Route::get('/{buku}', [Buku_Controller::class, 'detail'])->name('buku-detail');
});
