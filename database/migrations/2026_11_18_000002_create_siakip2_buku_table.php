<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siakip2_buku')) {
            return;
        }

        Schema::create('siakip2_buku', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('pengarang')->nullable();
            $table->string('penerbit')->nullable();
            $table->unsignedSmallInteger('tahun_terbit')->nullable();
            $table->string('isbn')->nullable();
            $table->string('kategori')->nullable();
            $table->text('sinopsis')->nullable();
            $table->unsignedInteger('durasi_pinjam')->default(7);
            $table->unsignedInteger('stok')->default(1);
            $table->string('lokasi_rak')->nullable();
            $table->string('cover_path')->nullable();
            $table->timestamps();

            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siakip2_buku');
    }
};
