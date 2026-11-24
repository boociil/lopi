<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('siakip2_buku_pinjam', 'durasi_pinjam')) {
            Schema::table('siakip2_buku_pinjam', function (Blueprint $table) {
                $table->unsignedSmallInteger('durasi_pinjam')->nullable()->after('tanggal_pengajuan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('siakip2_buku_pinjam', 'durasi_pinjam')) {
            Schema::table('siakip2_buku_pinjam', function (Blueprint $table) {
                $table->dropColumn('durasi_pinjam');
            });
        }
    }
};
