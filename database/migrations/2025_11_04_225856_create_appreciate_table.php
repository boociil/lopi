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

         Schema::create('siakip2_appreciate_master', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->dateTime('isi_awal');
            $table->dateTime('isi_akhir');
        });

        Schema::create('siakip2_appreciate_target', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id');
            $table->string('username', 100);

            $table->foreign('username', 'appreciate_fk_username')->references('username')->on('siakip2_users');
        });

        Schema::create('siakip2_appreciate_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id');
            $table->string('dari', 100)->nullable();
            $table->string('tujuan', 100)->nullable();
            $table->enum('status', ['0', '1', '2'])->default('0');
            $table->text('pesan');

            $table->foreign('dari', 'appreciate_fk_dari')->nullable()->references('username')->on('siakip2_users');
            $table->foreign('tujuan', 'appreciate_fk_tujuan')->nullable()->references('username')->on('siakip2_users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appreciate');
    }
};
