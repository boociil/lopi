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
            $table->date('tanggal');
            $table->decimal('tinggi_badan', total: 5, places: 2)->nullable();
            $table->decimal('berat_badan', total: 5, places: 2)->nullable();
            $table->decimal('linkar_perut', total: 5, places: 2)->nullable();
            $table->string('tekanan_darah', 10)->nullable();
            $table->decimal('asam_urat', total: 5, places: 2)->nullable();
            $table->decimal('gula_darah', total: 5, places: 2)->nullable();
            $table->enum('gula_darah_tipe', ['gdp', 'gds'])->nullable();

            $table->softDeletes();
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
