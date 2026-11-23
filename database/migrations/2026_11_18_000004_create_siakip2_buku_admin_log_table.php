<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siakip2_buku_admin_log')) {
            return;
        }

        Schema::create('siakip2_buku_admin_log', function (Blueprint $table) {
            $table->id();
            $table->string('actor_username', 100);
            $table->string('target_username', 100);
            $table->string('aksi', 50);
            $table->string('keterangan')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('target_username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siakip2_buku_admin_log');
    }
};
