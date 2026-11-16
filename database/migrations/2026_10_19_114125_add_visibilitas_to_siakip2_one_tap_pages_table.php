<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('siakip2_one_tap_pages', 'visibilitas')) {
            Schema::table('siakip2_one_tap_pages', function (Blueprint $table) {
                $table->enum('visibilitas', ['publik', 'internal', 'privat'])->default('privat');
            });
        }

           if (! Schema::hasTable('siakip2_one_tap_pengguna')) {
            Schema::create('siakip2_one_tap_pengguna', function (Blueprint $table) {
                $table->id();

                $table->string('page_id', 36);

                $table->foreign('page_id')
                    ->references('id')
                    ->on('siakip2_one_tap_pages')
                    ->cascadeOnDelete();

                $table->string('pengguna_username', 100);

                $table->timestamps(); // opsional

                // Satu pengguna tidak boleh terdaftar dua kali di page yang sama
                $table->unique(['page_id', 'pengguna_username']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('siakip2_one_tap_pengguna');

        if (Schema::hasColumn('siakip2_one_tap_pages', 'visibilitas')) {
            Schema::table('siakip2_one_tap_pages', function (Blueprint $table) {
                $table->dropColumn('visibilitas');
            });
        }
    }
};