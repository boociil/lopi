<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('siakip2_buku', 'jumlah_halaman')) {
            Schema::table('siakip2_buku', function (Blueprint $table) {
                $table->unsignedInteger('jumlah_halaman')->nullable()->after('pengarang');
            });
        }

        if (!Schema::hasColumn('siakip2_buku', 'bahasa')) {
            Schema::table('siakip2_buku', function (Blueprint $table) {
                $table->string('bahasa', 50)->nullable()->after('tahun_terbit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('siakip2_buku', 'jumlah_halaman')) {
            Schema::table('siakip2_buku', function (Blueprint $table) {
                $table->dropColumn('jumlah_halaman');
            });
        }

        if (Schema::hasColumn('siakip2_buku', 'bahasa')) {
            Schema::table('siakip2_buku', function (Blueprint $table) {
                $table->dropColumn('bahasa');
            });
        }
    }
};
