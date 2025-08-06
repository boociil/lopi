<?php

use App\Model\Umum\Peran;
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
        Schema::table('siakip2_peran', function (Blueprint $table) {
            $table->unsignedTinyInteger('peran_number')->default(0)->after('peran');
        });

        Peran::where('tipe_peran', 'global')->where('peran', 'pimpinan')->update(['peran_number' => 1]);
        Peran::where('tipe_peran', 'global')->where('peran', 'pj')->update(['peran_number' => 2]);
        Peran::where('tipe_peran', 'global')->where('peran', 'ketua')->update(['peran_number' => 3]);
        Peran::where('tipe_peran', 'global')->where('peran', 'admin')->update(['peran_number' => 4]);
        Peran::where('tipe_peran', 'global')->where('peran', 'anggota')->update(['peran_number' => 5]);

        Peran::where('tipe_peran', 'satuan_kerja_tahun')->where('peran', 'pimpinan')->update(['peran_number' => 11]);
        Peran::where('tipe_peran', 'satuan_kerja_tahun')->where('peran', 'pj')->update(['peran_number' => 12]);
        Peran::where('tipe_peran', 'satuan_kerja_tahun')->where('peran', 'ketua')->update(['peran_number' => 13]);
        Peran::where('tipe_peran', 'satuan_kerja_tahun')->where('peran', 'admin')->update(['peran_number' => 14]);
        Peran::where('tipe_peran', 'satuan_kerja_tahun')->where('peran', 'anggota')->update(['peran_number' => 15]);

        Peran::where('tipe_peran', 'fungsi')->where('peran', 'pimpinan')->update(['peran_number' => 21]);
        Peran::where('tipe_peran', 'fungsi')->where('peran', 'pj')->update(['peran_number' => 22]);
        Peran::where('tipe_peran', 'fungsi')->where('peran', 'ketua')->update(['peran_number' => 23]);
        Peran::where('tipe_peran', 'fungsi')->where('peran', 'admin')->update(['peran_number' => 24]);
        Peran::where('tipe_peran', 'fungsi')->where('peran', 'anggota')->update(['peran_number' => 25]);

        Peran::where('tipe_peran', 'tim')->where('peran', 'pimpinan')->update(['peran_number' => 31]);
        Peran::where('tipe_peran', 'tim')->where('peran', 'pj')->update(['peran_number' => 32]);
        Peran::where('tipe_peran', 'tim')->where('peran', 'ketua')->update(['peran_number' => 33]);
        Peran::where('tipe_peran', 'tim')->where('peran', 'admin')->update(['peran_number' => 34]);
        Peran::where('tipe_peran', 'tim')->where('peran', 'anggota')->update(['peran_number' => 35]);

        Peran::where('tipe_peran', 'proyek')->where('peran', 'pimpinan')->update(['peran_number' => 41]);
        Peran::where('tipe_peran', 'proyek')->where('peran', 'pj')->update(['peran_number' => 42]);
        Peran::where('tipe_peran', 'proyek')->where('peran', 'ketua')->update(['peran_number' => 43]);
        Peran::where('tipe_peran', 'proyek')->where('peran', 'admin')->update(['peran_number' => 44]);
        Peran::where('tipe_peran', 'proyek')->where('peran', 'anggota')->update(['peran_number' => 45]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
