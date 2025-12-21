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
        Schema::create('klasifikasi_arsip', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique(); // contoh: KP.02
            $table->string('nama');               // Kepegawaian
            $table->timestamps();
        });

        Schema::create('unit_cipta_arsip', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique(); // contoh: TU
            $table->string('nama');               // Tata Usaha
            $table->timestamps();
        });

        Schema::create('tingkat_perkembangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique(); // Asli, Salinan, Copy
            $table->timestamps();
        });

        Schema::create('media_arsip', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique(); // Kertas, Digital, Foto
            $table->timestamps();
        });

        Schema::create('kondisi_arsip', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique(); // Baik, Rusak ringan, Rusak berat
            $table->timestamps();
        });

        Schema::create('arsip', function (Blueprint $table) {
            $table->id();

            // RELASI PILIHAN
            $table->foreignId('klasifikasi_arsip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_cipta_arsip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tingkat_perkembangan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_arsip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kondisi_arsip_id')->constrained()->cascadeOnDelete();

            // ISIAN BEBAS
            $table->text('uraian_arsip');
            $table->date('kurun_waktu_awal')->nullable();
            $table->date('kurun_waktu_akhir')->nullable();
            $table->string('jumlah', 50);          // 1 berkas / 3 lembar
            $table->string('lokasi_simpan', 255);  // Lemari 2 – Rak B – Boks 05

            $table->timestamps();
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
