<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('siakip2_arsip_klasifikasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique(); // contoh: KP.02
            $table->string('nama');               // Kepegawaian
            $table->timestamps();
        });

        Schema::create('siakip2_arsip', function (Blueprint $table) {
            $table->id();
            // RELASI PILIHAN
            $table->unsignedBigInteger('klasifikasi_arsip_id')->nullable();
            $table->string('unit_cipta_arsip', 36)->nullable();
            $table->enum('tingkat_perkembangan', ['Asli', 'Salinan', 'Copy'])->nullable();
            $table->enum('media_arsip', ['Digital', 'Kertas', 'Foto'])->nullable();

            $table->enum('kondisi_arsip', ['baik', 'kurang_baik'])->default('baik');
            // ISIAN BEBAS
            $table->text('uraian_arsip');
            $table->year('kurun_waktu_awal');
            $table->year('kurun_waktu_akhir')->nullable();
            $table->string('jumlah', 50)->nullable();          // 1 berkas / 3 lembar
            $table->string('lokasi_simpan', 255)->nullable();  // Lemari 2 – Rak B – Boks 05

            $table->timestamps();

            $table->foreign('klasifikasi_arsip_id', 'arsip_fk_klasifikasi_arsip_id')->references('id')->on('siakip2_arsip_klasifikasi')->constrained()->cascadeOnDelete();
            $table->foreign('unit_cipta_arsip', 'arsip_fk_unit_cipta_arsip')->references('id')->on('siakip2_satuan_kerja_tahun');
        });

        Schema::create('siakip2_arsip_akses', function (Blueprint $table) {
            $table->id();

            // RELASI PILIHAN
            $table->unsignedBigInteger('arsip_id');
            $table->string('username', 100);
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');

            $table->foreign('arsip_id', 'arsip_akses_fk_arsip_id')->references('id')->on('siakip2_arsip')->constrained()->cascadeOnDelete();
            $table->foreign('username', 'arsip_akses_fk_username')->references('username')->on('siakip2_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arsip');
    }
};
