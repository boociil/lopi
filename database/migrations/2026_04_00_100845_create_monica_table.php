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

            $table->string('request_no')->unique(); // No
            $table->string('deskripsi', 1024);

            $table->string('beban_akun'); // Beban akun

            $table->date('tanggal_pelaksanaan'); // tanggal pelaksanaan
            $table->string('tujuan'); // tujuan

            $table->string('pelaksana_spd');

            $table->date('tanggal_pengajuan');

            $table->foreignId('executor_id')->constrained('users'); // pelaksana SPD
            $table->foreignId('approval_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('status', [
                'entry_bos',
                'terbit_st',
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

        Schema::create('siakip2_lembur', function (Blueprint $table) {
            $table->id();

            $table->string('request_no')->unique();
            $table->text('description');

            $table->date('execution_date');

            $table->foreignId('executor_id')->constrained('users'); // pelaksana lembur
            $table->foreignId('approval_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('status', [
                'entry_bos',
                'nota_dinas',
                'daftar_hadir',
                'laporan_spkl',
                'sptjm',
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

        Schema::create('siakip2_rapat', function (Blueprint $table) {
            $table->id();

            $table->string('request_no')->unique();
            $table->text('description');

            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();

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
