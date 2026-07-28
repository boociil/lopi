<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siakip2_one_tap_pages', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('pembuat_username');
            $table->string('satuan_kerja_tahun_id', 36)->nullable();

            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();

            $table->enum('akses', ['public', 'private'])->default('private');
            $table->enum('tema', ['orange', 'blue', 'green'])->default('orange');

            $table->text('foto_url')->nullable();
            $table->string('warna_latar')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('pembuat_username');
            $table->index('satuan_kerja_tahun_id');
            $table->index('akses');
        });

        Schema::create('siakip2_one_tap_buttons', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('one_tap_page_id', 36);

            $table->enum('tipe', ['link', 'divider'])->default('link');
            $table->string('judul');
            $table->text('url')->nullable();
            $table->string('ikon')->nullable();
            $table->string('warna')->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('urutan')->default(1);

            $table->timestamps();

            $table->foreign('one_tap_page_id')
                ->references('id')
                ->on('siakip2_one_tap_pages')
                ->cascadeOnDelete();

            $table->index(['one_tap_page_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siakip2_one_tap_buttons');
        Schema::dropIfExists('siakip2_one_tap_pages');
    }
};
