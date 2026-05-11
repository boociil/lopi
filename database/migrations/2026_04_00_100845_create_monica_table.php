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
        Schema::create('siakip2_perjalanan_dinas', function (Blueprint $table) {
            $table->id();

            $table->date('pengajuan_tanggal');
            $table->string('pengajuan_username', 100);
            $table->string('tim_id', 36);

            $table->string('deskripsi', 1024);
            $table->string('beban_akun'); // Beban akun

            $table->boolean('approval_status')->default(false);
            $table->string('approval_username');

            $table->boolean('status_entry_bos')->default(false);
            $table->boolean('status_terbit_st')->default(false);

            $table->date('tanggal_terbit_st')->nullable();
            $table->date('tanggal_setor_adm')->nullable();
            $table->date('tanggal_cair')->nullable();

            $table->string('tanggal_gap', 255)->nullable();

            $table->enum('status_keterangan', [
                'Revisi POK',
                'Aprove PJ',
                'Aprove PPK',
                'Proses ST',
                'Terbit ST',
                'Pelaksanaan SPD',
                'Setor Adm',
                'Verifikasi',
                'Proses SPJ',
                'Terbit SP2D',
                'Cair',
                'PengajuanBOS',
            ])->default('Revisi POK');

            $table->enum('PIC', [
                'Pimpinan',
                'KetuaTim',
                'PPK',
                'TimPerencana',
                'TimKeu',
                'PelaksanaSPD',
            ])->default('PelaksanaSPD');

            $table->string('nomor_fp')->nullable();
            $table->string('nomor_sp2d')->nullable();
            $table->decimal('nilai_sp2d', 18,2)->nullable();
            $table->date('tanggal_pencarian')->nullable();

            $table->text('notes')->nullable();

            $table->foreign('pengajuan_username', 'perjalanan_dinas_fk_pengajuan_username')->references('username')->on('siakip2_users');
            $table->foreign('tim_id', 'perjalanan_dinas_fk_tim_id')->references('id')->on('siakip2_tim');
        });

        Schema::create('siakip2_perjalanan_dinas_pelaksana', function (Blueprint $table) {
            $table->id();

            $table->string('perjalanan_dinas_id')->unique(); // No

            $table->date('tanggal'); // tanggal pelaksanaan
            $table->string('tujuan', 255); // tujuan
            $table->string('pelaksana', 255); // tanggal pelaksanaan\

            $table->foreign('perjalanan_dinas_id', 'pd_pelaksana_fk_perjalanan_dinas_id')->references('id')->on('siakip2_perjalanan_dinas');
        });

        Schema::create('siakip2_lembur', function (Blueprint $table) {
            $table->id();

            $table->date('pengajuan_tanggal');
            $table->string('pengajuan_username', 100);
            $table->string('tim_id', 36);

            $table->string('deskripsi', 1024);

            $table->string('tanggal_pelaksanaan', 120); // tanggal pelaksanaan

            $table->boolean('approval_status')->default(false);
            $table->string('approval_username');

            $table->boolean('status_entry_bos')->default(false);
            $table->boolean('status_nota_dinas')->default(false);
            $table->boolean('status_daftar_hadir')->default(false);
            $table->boolean('status_laporan')->default(false);
            $table->boolean('status_spkl')->default(false);
            $table->boolean('status_sptjm')->default(false);

            $table->date('tanggal_entry_bos')->nullable();
            $table->date('tanggal_nota_dinas')->nullable();
            $table->date('tanggal_daftar_hadir')->nullable();
            $table->date('tanggal_laporan')->nullable();
            $table->date('tanggal_spkl')->nullable();
            $table->date('tanggal_sptjm')->nullable();
            $table->date('tanggal_cair')->nullable();

            $table->string('tanggal_gap', 255)->nullable();

            $table->enum('status_keterangan', [
                'Revisi POK',
                'Aprove PJ',
                'Aprove PPK',
                'Proses ST',
                'Terbit ST',
                'Pelaksanaan Lembur',
                'Setor Adm',
                'Verifikasi',
                'Proses SPJ',
                'Terbit SP2D',
                'Cair',
                'PengajuanBOS',
            ])->default('Revisi POK');

            $table->enum('PIC', [
                'Pimpinan',
                'KetuaTim',
                'PPK',
                'TimPerencana',
                'TimKeu',
                'PelaksanaLembur',
            ])->default('PelaksanaSPD');

            $table->string('nomor_fp')->nullable();
            $table->string('nomor_sp2d')->nullable();
            $table->decimal('nilai_sp2d', 18,2)->nullable();
            $table->date('tanggal_pencarian')->nullable();

            $table->text('notes')->nullable();

            $table->foreign('pengajuan_username', 'perjalanan_dinas_fk_pengajuan_username')->references('username')->on('siakip2_users');
        });

        Schema::create('siakip2_rapat', function (Blueprint $table) {
            $table->id();

            $table->text('description');

            $table->string('beban_akun'); // Beban akun

            $table->boolean('approval_status')->default(false);
            $table->string('approval_username');

            $table->date('execution_date');
            $table->time('execution_time'); // WITA
            $table->string('location');

            $table->integer('committee_count')->default(0);
            $table->integer('participant_count')->default(0);

            $table->string('meeting_type');
            $table->string('responsible_team');

            $table->string('banner_design_link')->nullable();
            $table->string('banner_size')->nullable();

            $table->foreignId('approval_id')->nullable()->constrained()->nullOnDelete();

            $table->string('committee_names')->nullable();

            $table->enum('status', [
                'entry_bos',
                'undangan',
                'daftar_hadir',
                'notulen',
                'dokumentasi',
                'nota',
                'keterangan'
            ])->default('entry_bos');

            $table->foreignId('pic_id')->constrained('users');

            $table->string('fp_number')->nullable();
            $table->string('sp2d_number')->nullable();
            $table->decimal('sp2d_value', 18,2)->nullable();
            $table->date('disbursement_date')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monica');
        Schema::dropIfExists('monica');
        Schema::dropIfExists('monica');
    }
};
