<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siakip2_buku_pinjam')) {
            return;
        }

        Schema::create('siakip2_buku_pinjam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buku_id')->constrained('siakip2_buku')->cascadeOnDelete();
            $table->string('peminjam_username', 100);
            $table->dateTime('tanggal_pengajuan');
            $table->dateTime('tanggal_persetujuan')->nullable();
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->date('tanggal_kembali')->nullable();
            $table->enum('status', ['menunggu', 'dipinjam', 'ditolak', 'dikembalikan'])
                ->default('menunggu');
            $table->text('catatan_admin')->nullable();
            $table->string('diproses_oleh', 100)->nullable();
            $table->timestamps();

            $table->index('peminjam_username');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siakip2_buku_pinjam');
    }
};
