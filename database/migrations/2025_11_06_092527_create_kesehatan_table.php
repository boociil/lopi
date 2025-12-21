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
        Schema::create('siakip2_kesehatan', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->dateTime('tanggal');
            $table->unsignedSmallInteger('tinggi_badan')->nullable();
            $table->unsignedSmallInteger('berat_badan')->nullable();
            $table->unsignedSmallInteger('linkar_perut')->nullable();
            $table->string('tekanan_darah', 10)->nullable();
            $table->unsignedSmallInteger('asam_urat')->nullable();
            $table->unsignedSmallInteger('gula_darah')->nullable();
            $table->enum('gula_darah_tipe', ['dgp', 'gds'])->nullable();

            $table->foreign('username', 'kesehatan_fk_username')->references('username')->on('siakip2_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kesehatan');
    }
};
